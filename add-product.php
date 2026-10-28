<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/validation.php';
require_once __DIR__ . '/includes/upload-helper.php';

require_login();

$db = getDB();
$userId = current_user_id();

$errors = [];
$old = [
    'title'       => '',
    'description' => '',
    'category_id' => '',
    'price'       => '',
    'condition'   => '',
    'area_id'     => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require_or_die();

    $old['title']       = trim($_POST['title'] ?? '');
    $old['description'] = trim($_POST['description'] ?? '');
    $old['category_id'] = $_POST['category_id'] ?? '';
    $old['price']       = trim($_POST['price'] ?? '');
    $old['condition']   = $_POST['condition'] ?? '';
    $old['area_id']     = $_POST['area_id'] ?? '';

    if ($err = validate_product_title($old['title'])) $errors[] = $err;
    if ($err = validate_product_description($old['description'])) $errors[] = $err;
    if ($err = validate_price($old['price'])) $errors[] = $err;
    if ($err = validate_condition($old['condition'])) $errors[] = $err;

    $categoryId = null;
    if ($old['category_id'] === '') {
        $errors[] = 'Please select a category.';
    } else {
        $stmt = $db->prepare("SELECT category_id FROM categories WHERE category_id = ? AND is_active = 1 LIMIT 1");
        $stmt->execute([$old['category_id']]);
        if (!$stmt->fetch()) {
            $errors[] = 'Please select a valid category.';
        } else {
            $categoryId = (int)$old['category_id'];
        }
    }

    $areaId = null;
    if ($old['area_id'] === '') {
        $errors[] = 'Please select a pickup area.';
    } else {
        $stmt = $db->prepare("SELECT area_id FROM areas WHERE area_id = ? AND is_active = 1 LIMIT 1");
        $stmt->execute([$old['area_id']]);
        if (!$stmt->fetch()) {
            $errors[] = 'Please select a valid pickup area.';
        } else {
            $areaId = (int)$old['area_id'];
        }
    }

    $uploadResult = ['success' => true, 'files' => [], 'error' => null];
    if (empty($errors)) {
        $uploadResult = handle_multiple_product_images('images', UPLOAD_DIR_PRODUCTS, MAX_PRODUCT_IMAGES);
        if (!$uploadResult['success']) {
            $errors[] = $uploadResult['error'];
        } elseif (empty($uploadResult['files'])) {
            $errors[] = 'Please upload at least one product image.';
        }
    }

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            $stmt = $db->prepare(
                "INSERT INTO products (user_id, category_id, area_id, title, description, price, item_condition, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 'Available')"
            );
            $stmt->execute([
                $userId,
                $categoryId,
                $areaId,
                $old['title'],
                $old['description'],
                (float)$old['price'],
                $old['condition'],
            ]);

            $productId = (int)$db->lastInsertId();

            $imgStmt = $db->prepare(
                "INSERT INTO product_images (product_id, image_path, is_main) VALUES (?, ?, ?)"
            );
            foreach ($uploadResult['files'] as $index => $filename) {
                $imgStmt->execute([
                    $productId,
                    'uploads/products/' . $filename,
                    $index === 0 ? 1 : 0,
                ]);
            }

            $db->commit();

            setFlash('success', 'Your listing has been posted successfully!');
            header('Location: ' . BASE_URL . 'my-listings.php');
            exit;
        } catch (Exception $e) {
            $db->rollBack();
            error_log('Add product failed: ' . $e->getMessage());
            $errors[] = 'Something went wrong while posting your listing. Please try again.';
        }
    }
}

$categories = $db->query("SELECT category_id, category_name FROM categories WHERE is_active = 1 ORDER BY category_name ASC")->fetchAll();
$areas      = $db->query("SELECT area_id, area_name FROM areas WHERE is_active = 1 ORDER BY area_name ASC")->fetchAll();

$pageTitle = 'Sell an Item';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="container my-5">
  <div class="row justify-content-center">
    <div class="col-lg-8">
      <div class="cs-form-card">
        <h2 class="cs-section-title mb-1"><i class="bi bi-plus-circle"></i> Sell an Item</h2>
        <p class="text-muted mb-4">Fill in the details below. Your listing will be visible to students near you.</p>

        <?php if (!empty($errors)): ?>
          <div class="alert alert-danger">
            <ul class="mb-0 ps-3">
              <?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <form method="post" action="<?= BASE_URL ?>add-product.php" enctype="multipart/form-data" novalidate>
          <?= csrf_field() ?>

          <div class="mb-3">
            <label for="title" class="form-label">Product Title *</label>
            <input type="text" class="form-control" id="title" name="title" value="<?= e($old['title']) ?>" required maxlength="150" placeholder="e.g. Casio fx-991EX Scientific Calculator">
          </div>

          <div class="mb-3">
            <label for="description" class="form-label">Description *</label>
            <textarea class="form-control" id="description" name="description" rows="5" required placeholder="Describe the item's condition, age, included accessories, reason for selling, etc."><?= e($old['description']) ?></textarea>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="category_id" class="form-label">Category *</label>
              <select class="form-select" id="category_id" name="category_id" required>
                <option value="">Select a category</option>
                <?php foreach ($categories as $cat): ?>
                  <option value="<?= (int)$cat['category_id'] ?>" <?= ((string)$old['category_id'] === (string)$cat['category_id']) ? 'selected' : '' ?>>
                    <?= e($cat['category_name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label for="condition" class="form-label">Condition *</label>
              <select class="form-select" id="condition" name="condition" required>
                <option value="">Select condition</option>
                <?php foreach (['New', 'Like New', 'Good', 'Fair'] as $cond): ?>
                  <option value="<?= e($cond) ?>" <?= ($old['condition'] === $cond) ? 'selected' : '' ?>><?= e($cond) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="price" class="form-label">Price (৳) *</label>
              <input type="number" step="0.01" min="0" class="form-control" id="price" name="price" value="<?= e($old['price']) ?>" required placeholder="e.g. 650">
            </div>
            <div class="col-md-6 mb-3">
              <label for="area_id" class="form-label">Pickup Area *</label>
              <select class="form-select" id="area_id" name="area_id" required>
                <option value="">Select pickup area</option>
                <?php foreach ($areas as $area): ?>
                  <option value="<?= (int)$area['area_id'] ?>" <?= ((string)$old['area_id'] === (string)$area['area_id']) ? 'selected' : '' ?>>
                    <?= e($area['area_name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="mb-4">
            <label for="images" class="form-label">Product Images * <span class="text-muted">(up to 5)</span></label>
            <input type="file" class="form-control" id="images" name="images[]" accept=".jpg,.jpeg,.png,.webp" multiple required>
            <div class="form-text">JPG, PNG or WEBP. Maximum 3MB per image. First image will be used as the main photo.</div>
          </div>

          <button type="submit" class="btn cs-btn-primary">Post Listing</button>
          <a href="<?= BASE_URL ?>my-listings.php" class="btn btn-link">Cancel</a>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>