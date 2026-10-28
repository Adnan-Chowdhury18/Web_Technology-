<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/validation.php';
require_once __DIR__ . '/includes/upload-helper.php';

require_login();

$db = getDB();
$productId = (int)($_GET['id'] ?? 0);

if ($productId <= 0) {
    http_response_code(404);
    die('Product not found.');
}

// Ownership check — this blocks editing another student's listing.
$product = require_product_owner($productId);

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require_or_die();

    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $categoryId  = $_POST['category_id'] ?? '';
    $price       = trim($_POST['price'] ?? '');
    $condition   = $_POST['condition'] ?? '';
    $areaId      = $_POST['area_id'] ?? '';
    $deleteImageIds = $_POST['delete_images'] ?? [];

    if ($err = validate_product_title($title)) $errors[] = $err;
    if ($err = validate_product_description($description)) $errors[] = $err;
    if ($err = validate_price($price)) $errors[] = $err;
    if ($err = validate_condition($condition)) $errors[] = $err;

    $categoryIdClean = null;
    $stmt = $db->prepare("SELECT category_id FROM categories WHERE category_id = ? AND is_active = 1 LIMIT 1");
    $stmt->execute([$categoryId]);
    if (!$stmt->fetch()) {
        $errors[] = 'Please select a valid category.';
    } else {
        $categoryIdClean = (int)$categoryId;
    }

    $areaIdClean = null;
    $stmt = $db->prepare("SELECT area_id FROM areas WHERE area_id = ? AND is_active = 1 LIMIT 1");
    $stmt->execute([$areaId]);
    if (!$stmt->fetch()) {
        $errors[] = 'Please select a valid pickup area.';
    } else {
        $areaIdClean = (int)$areaId;
    }

    // Count how many images will remain after deletions, to enforce the 5-image limit.
    $stmt = $db->prepare("SELECT image_id FROM product_images WHERE product_id = ?");
    $stmt->execute([$productId]);
    $existingImageIds = array_column($stmt->fetchAll(), 'image_id');
    $remainingCount = count(array_diff($existingImageIds, $deleteImageIds));

    $uploadResult = ['success' => true, 'files' => [], 'error' => null];
    if (empty($errors)) {
        $newFilesCount = isset($_FILES['images']['name']) ? count(array_filter($_FILES['images']['name'])) : 0;
        if ($remainingCount + $newFilesCount > MAX_PRODUCT_IMAGES) {
            $errors[] = 'A product can have a maximum of ' . MAX_PRODUCT_IMAGES . ' images total.';
        } elseif ($remainingCount + $newFilesCount === 0) {
            $errors[] = 'A product must have at least one image.';
        } else {
            $uploadResult = handle_multiple_product_images('images', UPLOAD_DIR_PRODUCTS, MAX_PRODUCT_IMAGES);
            if (!$uploadResult['success']) {
                $errors[] = $uploadResult['error'];
            }
        }
    }

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            $stmt = $db->prepare(
                "UPDATE products SET title = ?, description = ?, category_id = ?, price = ?, item_condition = ?, area_id = ?
                 WHERE product_id = ? AND user_id = ?"
            );
            $stmt->execute([
                $title, $description, $categoryIdClean, (float)$price, $condition, $areaIdClean,
                $productId, current_user_id(),
            ]);

            // Delete selected existing images (both DB row and physical file).
            if (!empty($deleteImageIds)) {
                $placeholders = implode(',', array_fill(0, count($deleteImageIds), '?'));
                $stmt = $db->prepare("SELECT image_id, image_path FROM product_images WHERE product_id = ? AND image_id IN ($placeholders)");
                $stmt->execute(array_merge([$productId], $deleteImageIds));
                $imagesToDelete = $stmt->fetchAll();

                $delStmt = $db->prepare("DELETE FROM product_images WHERE image_id = ?");
                foreach ($imagesToDelete as $img) {
                    $delStmt->execute([$img['image_id']]);
                    @unlink(__DIR__ . '/' . $img['image_path']);
                }
            }

            // Insert any newly uploaded images.
            if (!empty($uploadResult['files'])) {
                $imgStmt = $db->prepare("INSERT INTO product_images (product_id, image_path, is_main) VALUES (?, ?, 0)");
                foreach ($uploadResult['files'] as $filename) {
                    $imgStmt->execute([$productId, 'uploads/products/' . $filename]);
                }
            }

            // Ensure exactly one image is marked as main (pick the first remaining one if needed).
            $stmt = $db->prepare("SELECT image_id FROM product_images WHERE product_id = ? AND is_main = 1");
            $stmt->execute([$productId]);
            if (!$stmt->fetch()) {
                $stmt = $db->prepare("SELECT image_id FROM product_images WHERE product_id = ? ORDER BY image_id ASC LIMIT 1");
                $stmt->execute([$productId]);
                $firstImg = $stmt->fetch();
                if ($firstImg) {
                    $upd = $db->prepare("UPDATE product_images SET is_main = 1 WHERE image_id = ?");
                    $upd->execute([$firstImg['image_id']]);
                }
            }

            $db->commit();

            setFlash('success', 'Listing updated successfully.');
            header('Location: ' . BASE_URL . 'my-listings.php');
            exit;
        } catch (Exception $e) {
            $db->rollBack();
            error_log('Edit product failed: ' . $e->getMessage());
            $errors[] = 'Something went wrong while updating your listing. Please try again.';
        }
    }

    // Keep submitted values on screen if there were errors.
    $product['title']          = $title;
    $product['description']    = $description;
    $product['category_id']    = $categoryIdClean ?? $categoryId;
    $product['price']          = $price;
    $product['item_condition'] = $condition;
    $product['area_id']        = $areaIdClean ?? $areaId;
}

$categories = $db->query("SELECT category_id, category_name FROM categories WHERE is_active = 1 ORDER BY category_name ASC")->fetchAll();
$areas      = $db->query("SELECT area_id, area_name FROM areas WHERE is_active = 1 ORDER BY area_name ASC")->fetchAll();

$stmt = $db->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_main DESC, image_id ASC");
$stmt->execute([$productId]);
$images = $stmt->fetchAll();

$pageTitle = 'Edit Listing';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="container my-5">
  <div class="row justify-content-center">
    <div class="col-lg-8">
      <div class="cs-form-card">
        <h2 class="cs-section-title mb-1"><i class="bi bi-pencil-square"></i> Edit Listing</h2>
        <p class="text-muted mb-4">Update your listing details below.</p>

        <?php if (!empty($errors)): ?>
          <div class="alert alert-danger">
            <ul class="mb-0 ps-3">
              <?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <?php if ($product['status'] === 'Sold'): ?>
          <div class="alert alert-warning">This item is marked as Sold. You can still edit details, but consider whether that's necessary.</div>
        <?php endif; ?>

        <form method="post" action="<?= BASE_URL ?>edit-product.php?id=<?= $productId ?>" enctype="multipart/form-data" novalidate>
          <?= csrf_field() ?>

          <div class="mb-3">
            <label for="title" class="form-label">Product Title *</label>
            <input type="text" class="form-control" id="title" name="title" value="<?= e($product['title']) ?>" required maxlength="150">
          </div>

          <div class="mb-3">
            <label for="description" class="form-label">Description *</label>
            <textarea class="form-control" id="description" name="description" rows="5" required><?= e($product['description']) ?></textarea>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="category_id" class="form-label">Category *</label>
              <select class="form-select" id="category_id" name="category_id" required>
                <?php foreach ($categories as $cat): ?>
                  <option value="<?= (int)$cat['category_id'] ?>" <?= ((string)$product['category_id'] === (string)$cat['category_id']) ? 'selected' : '' ?>>
                    <?= e($cat['category_name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label for="condition" class="form-label">Condition *</label>
              <select class="form-select" id="condition" name="condition" required>
                <?php foreach (['New', 'Like New', 'Good', 'Fair'] as $cond): ?>
                  <option value="<?= e($cond) ?>" <?= ($product['item_condition'] === $cond) ? 'selected' : '' ?>><?= e($cond) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="price" class="form-label">Price (৳) *</label>
              <input type="number" step="0.01" min="0" class="form-control" id="price" name="price" value="<?= e((string)$product['price']) ?>" required>
            </div>
            <div class="col-md-6 mb-3">
              <label for="area_id" class="form-label">Pickup Area *</label>
              <select class="form-select" id="area_id" name="area_id" required>
                <?php foreach ($areas as $area): ?>
                  <option value="<?= (int)$area['area_id'] ?>" <?= ((string)$product['area_id'] === (string)$area['area_id']) ? 'selected' : '' ?>>
                    <?= e($area['area_name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Current Images</label>
            <div class="row g-2">
              <?php foreach ($images as $img): ?>
                <div class="col-4 col-md-3 text-center">
                  <img src="<?= BASE_URL . e($img['image_path']) ?>" class="img-fluid rounded mb-1" style="aspect-ratio:1/1;object-fit:cover;">
                  <div class="form-check d-flex align-items-center justify-content-center gap-1">
                    <input class="form-check-input" type="checkbox" name="delete_images[]" value="<?= (int)$img['image_id'] ?>" id="del<?= (int)$img['image_id'] ?>">
                    <label class="form-check-label small text-danger" for="del<?= (int)$img['image_id'] ?>">Remove</label>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="mb-4">
            <label for="images" class="form-label">Add More Images <span class="text-muted">(optional, max <?= MAX_PRODUCT_IMAGES ?> total)</span></label>
            <input type="file" class="form-control" id="images" name="images[]" accept=".jpg,.jpeg,.png,.webp" multiple>
          </div>

          <button type="submit" class="btn cs-btn-primary">Save Changes</button>
          <a href="<?= BASE_URL ?>my-listings.php" class="btn btn-link">Cancel</a>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>