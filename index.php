<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/auth.php';
$pdo = getDB();
$user = current_user();

$categories = $pdo->query(
    "SELECT category_id, category_name, icon FROM categories WHERE is_active = 1 ORDER BY category_name ASC"
)->fetchAll();

$recentProducts = $pdo->query(
    "SELECT p.*, c.category_name, c.icon AS category_icon, a.area_name,
     (SELECT pi.image_path FROM product_images pi WHERE pi.product_id = p.product_id ORDER BY pi.is_main DESC, pi.image_id ASC LIMIT 1) AS main_image
     FROM products p
     JOIN categories c ON p.category_id = c.category_id
     JOIN areas a ON p.area_id = a.area_id
     WHERE p.status = 'Available'
     ORDER BY p.created_at DESC
     LIMIT 8"
)->fetchAll();

$favoriteIds = [];
if ($user) {
    $favStmt = $pdo->prepare("SELECT product_id FROM favorites WHERE user_id = :uid");
    $favStmt->execute([':uid' => $user['user_id']]);
    $favoriteIds = array_map('intval', $favStmt->fetchAll(PDO::FETCH_COLUMN));
}

$pageTitle = 'Home';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>

<section class="cs-hero py-5 mb-5">
  <div class="container text-center">
    <h1 class="fw-bold mb-3">Buy and Sell With Fellow Students</h1>
    <p class="lead mb-4">Textbooks, electronics, hostel essentials and more — right in your campus area.</p>
    <form action="<?= BASE_URL ?>products.php" method="get" class="d-flex justify-content-center gap-2 flex-wrap">
      <input type="text" name="q" class="form-control w-auto" style="min-width:280px" placeholder="What are you looking for?">
      <button type="submit" class="btn cs-btn-primary"><i class="bi bi-search"></i> Search</button>
    </form>
    <?php if ($user): ?>
      <a href="<?= BASE_URL ?>add-product.php" class="btn btn-outline-light mt-3"><i class="bi bi-plus-circle"></i> Sell an Item</a>
    <?php else: ?>
      <a href="<?= BASE_URL ?>register.php" class="btn btn-outline-light mt-3">Join ClassSwap Free</a>
    <?php endif; ?>
  </div>
</section>

<div class="container">
  <section class="mb-5">
    <h3 class="fw-bold mb-4">Browse by Category</h3>
    <div class="row g-3">
      <?php foreach ($categories as $cat): ?>
        <div class="col-6 col-md-3 col-lg-2">
          <a href="<?= BASE_URL ?>products.php?category_id=<?= (int)$cat['category_id'] ?>" class="category-card-link">
            <div class="category-card">
              <div class="category-icon-wrap">
                <i class="bi bi-<?= e($cat['icon'] ?: 'tag') ?>"></i>
              </div>
              <div class="category-name"><?= e($cat['category_name']) ?></div>
            </div>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h3 class="fw-bold mb-0">Recently Listed</h3>
      <a href="<?= BASE_URL ?>products.php" class="btn btn-sm cs-btn-outline">View All <i class="bi bi-arrow-right"></i></a>
    </div>

    <?php if (empty($recentProducts)): ?>
      <p class="text-muted">No products listed yet. Be the first to <a href="<?= BASE_URL ?>add-product.php">sell an item</a>!</p>
    <?php else: ?>
      <div class="row">
        <?php foreach ($recentProducts as $p): ?>
          <div class="col-md-4 col-lg-3 mb-4">
            <div class="card cs-product-card h-100 position-relative">
              <?php if ($user): ?>
                <div class="fav-btn-wrap">
                  <form method="post" action="<?= BASE_URL ?>favorites.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="toggle">
                    <input type="hidden" name="product_id" value="<?= (int)$p['product_id'] ?>">
                    <input type="hidden" name="redirect_to" value="<?= e($_SERVER['REQUEST_URI']) ?>">
                    <?php if (in_array((int)$p['product_id'], $favoriteIds, true)): ?>
                      <button type="submit" class="btn btn-danger" title="Remove from favorites"><i class="bi bi-heart-fill"></i></button>
                    <?php else: ?>
                      <button type="submit" class="btn btn-outline-secondary" title="Add to favorites"><i class="bi bi-heart"></i></button>
                    <?php endif; ?>
                  </form>
                </div>
              <?php endif; ?>
              <a href="<?= BASE_URL ?>product-details.php?id=<?= (int)$p['product_id'] ?>">
                <img src="<?= e(getProductMainImage($p['main_image'])) ?>" class="card-img-top cs-product-img" alt="<?= e($p['title']) ?>">
              </a>
              <div class="card-body d-flex flex-column">
                <span class="badge bg-secondary-subtle text-secondary-emphasis mb-1"><i class="bi bi-<?= e($p['category_icon'] ?: 'tag') ?> me-1"></i><?= e($p['category_name']) ?></span>
                <h6 class="card-title mb-1">
                  <a href="<?= BASE_URL ?>product-details.php?id=<?= (int)$p['product_id'] ?>" class="text-decoration-none text-dark"><?= e($p['title']) ?></a>
                </h6>
                <p class="cs-price fw-bold mb-1"><?= formatPrice($p['price']) ?></p>
                <p class="small text-muted mb-2"><i class="bi bi-geo-alt"></i> <?= e($p['area_name']) ?></p>
                <span class="badge bg-light text-dark border mb-2"><?= e($p['item_condition']) ?></span>
                <a href="<?= BASE_URL ?>product-details.php?id=<?= (int)$p['product_id'] ?>" class="btn btn-sm cs-btn-primary mt-auto">View Details</a>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>