<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/auth.php';
$pdo = getDB();
$user = current_user();

$productId = (int)($_GET['id'] ?? 0);
if ($productId <= 0) {
    http_response_code(404);
    $pageTitle = 'Not Found';
    require __DIR__ . '/includes/header.php';
    require __DIR__ . '/includes/navbar.php';
    echo '<div class="container py-5 text-center"><h2>Product Not Found</h2><p class="text-muted">This listing does not exist.</p><a href="' . BASE_URL . 'products.php" class="btn cs-btn-primary">Browse Products</a></div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$stmt = $pdo->prepare(
    "SELECT p.*, c.category_name, c.icon AS category_icon, a.area_name, a.latitude AS area_lat, a.longitude AS area_lng,
     u.user_id AS seller_id, u.full_name AS seller_name, u.whatsapp_number, u.profile_photo, u.area_id AS seller_area_id
     FROM products p
     JOIN categories c ON p.category_id = c.category_id
     JOIN areas a ON p.area_id = a.area_id
     JOIN users u ON p.user_id = u.user_id
     WHERE p.product_id = :id"
);
$stmt->execute([':id' => $productId]);
$product = $stmt->fetch();

$isOwner = $user && $product && (int)$product['user_id'] === (int)$user['user_id'];

if (!$product || ($product['status'] === 'Hidden' && !$isOwner)) {
    http_response_code(404);
    $pageTitle = 'Not Found';
    require __DIR__ . '/includes/header.php';
    require __DIR__ . '/includes/navbar.php';
    echo '<div class="container py-5 text-center"><h2>Product Not Found</h2><p class="text-muted">This listing is unavailable.</p><a href="' . BASE_URL . 'products.php" class="btn cs-btn-primary">Browse Products</a></div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$imagesStmt = $pdo->prepare("SELECT image_path, is_main FROM product_images WHERE product_id = :id ORDER BY is_main DESC, image_id ASC");
$imagesStmt->execute([':id' => $productId]);
$images = $imagesStmt->fetchAll();
if (empty($images)) {
    $images = [['image_path' => null, 'is_main' => 1]];
}

$isFavorited = false;
if ($user) {
    $favCheck = $pdo->prepare("SELECT favorite_id FROM favorites WHERE user_id = :uid AND product_id = :pid");
    $favCheck->execute([':uid' => $user['user_id'], ':pid' => $productId]);
    $isFavorited = (bool)$favCheck->fetch();
}

$distanceKm = null;
if ($user && !empty($user['area_id']) && !empty($product['area_id'])) {
    $areaStmt = $pdo->prepare("SELECT latitude, longitude FROM areas WHERE area_id = :id");
    $areaStmt->execute([':id' => $user['area_id']]);
    $userArea = $areaStmt->fetch();
    if ($userArea) {
        $distanceKm = haversineDistanceKm(
            $userArea['latitude'], $userArea['longitude'],
            $product['area_lat'], $product['area_lng']
        );
    }
}

$currentUrl = $_SERVER['REQUEST_URI'];
$pageTitle = $product['title'];
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>

<div class="container py-4">
  <div class="row g-4">
    <div class="col-lg-6">
      <div id="productCarousel" class="carousel slide shadow-sm rounded-3 overflow-hidden" data-bs-ride="false">
        <div class="carousel-inner bg-light">
          <?php foreach ($images as $i => $img): ?>
            <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>">
              <img src="<?= e(getProductMainImage($img['image_path'])) ?>" class="d-block w-100" style="max-height:420px; object-fit:cover;" alt="<?= e($product['title']) ?>">
            </div>
          <?php endforeach; ?>
        </div>
        <?php if (count($images) > 1): ?>
          <button class="carousel-control-prev" type="button" data-bs-target="#productCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon"></span>
          </button>
          <button class="carousel-control-next" type="button" data-bs-target="#productCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon"></span>
          </button>
        <?php endif; ?>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="d-flex justify-content-between align-items-start">
        <span class="badge bg-secondary-subtle text-secondary-emphasis mb-2"><i class="bi bi-<?= e($product['category_icon'] ?: 'tag') ?> me-1"></i><?= e($product['category_name']) ?></span>
        <?php if ($user): ?>
          <form method="post" action="<?= BASE_URL ?>favorites.php">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="toggle">
            <input type="hidden" name="product_id" value="<?= (int)$product['product_id'] ?>">
            <input type="hidden" name="redirect_to" value="<?= e($currentUrl) ?>">
            <?php if ($isFavorited): ?>
              <button type="submit" class="btn btn-danger btn-sm rounded-circle" title="Remove from favorites"><i class="bi bi-heart-fill"></i></button>
            <?php else: ?>
              <button type="submit" class="btn btn-outline-secondary btn-sm rounded-circle" title="Add to favorites"><i class="bi bi-heart"></i></button>
            <?php endif; ?>
          </form>
        <?php endif; ?>
      </div>

      <h2 class="fw-bold mb-1"><?= e($product['title']) ?></h2>
      <p class="cs-price fs-3 fw-bold mb-2"><?= formatPrice($product['price']) ?></p>
      <span class="badge <?= statusBadgeClass($product['status']) ?> mb-3"><?= e($product['status']) ?></span>
      <span class="badge bg-light text-dark border mb-3"><?= e($product['item_condition']) ?></span>

      <p class="text-muted mb-1"><i class="bi bi-geo-alt"></i> <?= e($product['area_name']) ?></p>
      <?php if ($distanceKm !== null): ?>
        <p class="text-info small mb-3"><i class="bi bi-signpost-2"></i> <?= e(formatDistance($distanceKm)) ?> from your area</p>
      <?php else: ?>
        <p class="mb-3"></p>
      <?php endif; ?>

      <h6 class="fw-bold">Description</h6>
      <p style="white-space: pre-wrap;"><?= e($product['description']) ?></p>

      <hr>

      <h6 class="fw-bold mb-2">Seller Information</h6>
      <div class="d-flex align-items-center gap-2 mb-3">
        <img src="<?= e(getProfileImage($product['profile_photo'])) ?>" class="cs-avatar-sm" alt="Seller">
        <span><?= e($product['seller_name']) ?></span>
      </div>

      <?php if (!$isOwner): ?>
        <div class="d-flex gap-2 flex-wrap mb-3">
          <?php if (!empty($product['whatsapp_number'])): ?>
            <a href="https://wa.me/<?= e(preg_replace('/\D/', '', $product['whatsapp_number'])) ?>?text=<?= urlencode('Hi, I am interested in your listing "' . $product['title'] . '" on ClassSwap.') ?>" target="_blank" class="btn btn-success">
              <i class="bi bi-whatsapp"></i> WhatsApp Seller
            </a>
          <?php endif; ?>
          <?php if ($user): ?>
            <a href="<?= BASE_URL ?>contact-seller.php?id=<?= (int)$product['product_id'] ?>" class="btn cs-btn-primary">
              <i class="bi bi-envelope"></i> Contact Seller
            </a>
            <a href="<?= BASE_URL ?>report-listing.php?id=<?= (int)$product['product_id'] ?>" class="btn btn-outline-danger">
              <i class="bi bi-flag"></i> Report Listing
            </a>
          <?php else: ?>
            <a href="<?= BASE_URL ?>login.php" class="btn cs-btn-primary">Login to Contact Seller</a>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <div class="d-flex gap-2 flex-wrap mb-3">
          <a href="<?= BASE_URL ?>edit-product.php?id=<?= (int)$product['product_id'] ?>" class="btn btn-outline-primary">
            <i class="bi bi-pencil"></i> Edit Listing
          </a>
          <?php if ($product['status'] === 'Available'): ?>
            <a href="<?= BASE_URL ?>mark-sold.php?id=<?= (int)$product['product_id'] ?>" class="btn btn-success">
              <i class="bi bi-bag-check"></i> Mark as Sold
            </a>
          <?php endif; ?>
          <?php if ($product['status'] !== 'Hidden'): ?>
            <form method="post" action="<?= BASE_URL ?>my-listings.php" onsubmit="return confirm('Hide this listing from the site?');">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="hide">
              <input type="hidden" name="product_id" value="<?= (int)$product['product_id'] ?>">
              <button type="submit" class="btn btn-outline-danger"><i class="bi bi-eye-slash"></i> Hide Listing</button>
            </form>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <p class="small text-muted">Listed on <?= formatDate($product['created_at']) ?></p>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>