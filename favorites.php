<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/auth.php';

$pdo = getDB();

require_login();
$userId = current_user_id();

// Handle toggle action (add/remove favorite)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle') {
    csrf_require_or_die();
    $productId = isset($_POST['product_id']) && is_numeric($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $redirectTo = $_POST['redirect_to'] ?? 'favorites.php';

    if ($productId > 0) {
        $check = $pdo->prepare("SELECT favorite_id FROM favorites WHERE user_id = ? AND product_id = ?");
        $check->execute([$userId, $productId]);
        $existing = $check->fetch();

        if ($existing) {
            $del = $pdo->prepare("DELETE FROM favorites WHERE favorite_id = ?");
            $del->execute([$existing['favorite_id']]);
            setFlash('success', 'Removed from favorites.');
        } else {
            $ins = $pdo->prepare("INSERT INTO favorites (user_id, product_id, created_at) VALUES (?, ?, NOW())");
            try {
                $ins->execute([$userId, $productId]);
                setFlash('success', 'Added to favorites.');
            } catch (Exception $e) {
                setFlash('error', 'Could not update favorites.');
            }
        }
    }

    if (!preg_match('#^[a-zA-Z0-9_\-/.]+\.php(\?.*)?$#', $redirectTo)) {
        $redirectTo = 'favorites.php';
    }
    header('Location: ' . $redirectTo);
    exit;
}

$pageTitle = 'My Favorites';

$stmt = $pdo->prepare("
    SELECT p.product_id, p.title, p.price, p.item_condition, p.status, p.created_at,
           c.category_name, a.area_name, pi.image_path
    FROM favorites f
    JOIN products p ON f.product_id = p.product_id
    LEFT JOIN categories c ON p.category_id = c.category_id
    LEFT JOIN areas a ON p.area_id = a.area_id
    LEFT JOIN product_images pi ON pi.product_id = p.product_id AND pi.is_main = 1
    WHERE f.user_id = ?
    ORDER BY f.created_at DESC
");
$stmt->execute([$userId]);
$favorites = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<section class="py-4">
    <div class="container">
        <h2 class="cs-heading mb-1">My Favorites</h2>
        <p class="text-muted mb-4">Items you've saved for later.</p>

        <?php if (!empty($favorites)): ?>
            <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-4">
                <?php foreach ($favorites as $p): ?>
                    <div class="col">
                        <div class="card h-100 border-0 shadow-sm position-relative">
                            <div class="fav-btn-wrap">
                                <form method="POST" action="favorites.php">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="product_id" value="<?php echo (int)$p['product_id']; ?>">
                                    <input type="hidden" name="redirect_to" value="favorites.php">
                                    <button type="submit" class="btn btn-light shadow-sm" title="Remove from favorites">
                                        <i class="bi bi-heart-fill text-danger"></i>
                                    </button>
                                </form>
                            </div>
                            <?php $mainImage = getProductMainImage($p['image_path'] ?? null); ?>
                            <a href="product-details.php?id=<?php echo (int)$p['product_id']; ?>" class="text-decoration-none">
                                <img src="<?php echo e($mainImage); ?>" alt="<?php echo e($p['title']); ?>" class="card-img-top" style="height: 220px; object-fit: cover;">
                                <div class="card-body d-flex flex-column">
                                    <h6 class="card-title fw-semibold text-dark mb-1"><?php echo e($p['title']); ?></h6>
                                    <small class="text-muted d-block mb-2"><?php echo e($p['category_name'] ?? 'Uncategorized'); ?> • <?php echo e($p['area_name'] ?? 'Unknown Area'); ?></small>
                                    <div class="d-flex justify-content-between align-items-start mt-auto">
                                        <span class="fw-bold fs-5 text-primary"><?php echo formatPrice($p['price']); ?></span>
                                        <span class="badge bg-<?php echo statusBadgeClass($p['status']); ?>"><?php echo e($p['status']); ?></span>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-heart fs-1 text-muted"></i>
                <p class="text-muted mt-2">You haven't saved any favorites yet.</p>
                <a href="products.php" class="btn btn-primary">Browse Products</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>