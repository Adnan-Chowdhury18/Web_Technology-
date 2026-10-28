<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/auth.php';

$pdo = getDB();
require_login();
$userId = current_user_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require_or_die();
    $action = $_POST['action'] ?? '';
    $productId = isset($_POST['product_id']) && is_numeric($_POST['product_id']) ? (int)$_POST['product_id'] : 0;

    if ($productId > 0) {
        $check = $pdo->prepare("SELECT product_id, user_id, status FROM products WHERE product_id = ?");
        $check->execute([$productId]);
        $prod = $check->fetch();

        if ($prod && (int)$prod['user_id'] === $userId) {
            if ($action === 'hide' && $prod['status'] !== 'Hidden') {
                $upd = $pdo->prepare("UPDATE products SET status = 'Hidden', updated_at = NOW() WHERE product_id = ?");
                $upd->execute([$productId]);
                setFlash('success', 'Listing hidden.');
            } elseif ($action === 'unhide' && $prod['status'] === 'Hidden') {
                $upd = $pdo->prepare("UPDATE products SET status = 'Available', updated_at = NOW() WHERE product_id = ?");
                $upd->execute([$productId]);
                setFlash('success', 'Listing is visible again.');
            } elseif ($action === 'delete') {
                $upd = $pdo->prepare("UPDATE products SET status = 'Hidden', updated_at = NOW() WHERE product_id = ?");
                $upd->execute([$productId]);
                setFlash('success', 'Listing deleted.');
            }
        } else {
            setFlash('error', 'You do not have permission to modify this listing.');
        }
    }
    header('Location: my-listings.php');
    exit;
}

$pageTitle = 'My Listings';

$stmt = $pdo->prepare("
    SELECT p.product_id, p.title, p.price, p.status, p.created_at,
           pi.image_path
    FROM products p
    LEFT JOIN product_images pi ON pi.product_id = p.product_id AND pi.is_main = 1
    WHERE p.user_id = ?
    ORDER BY p.created_at DESC
");
$stmt->execute([$userId]);
$myProducts = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<section class="py-4">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h2 class="cs-heading mb-1">My Listings</h2>
                <p class="text-muted mb-0">Manage the items you've posted on ClassSwap.</p>
            </div>
            <a href="add-product.php" class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i>Add New Item</a>
        </div>

        <?php if (!empty($myProducts)): ?>
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                <?php foreach ($myProducts as $p): ?>
                    <div class="col">
                        <div class="card h-100 border-0 shadow-sm">
                            <?php $mainImage = getProductMainImage($p['image_path'] ?? null); ?>
                            <img src="<?php echo e($mainImage); ?>" alt="<?php echo e($p['title']); ?>" class="card-img-top" style="height: 200px; object-fit: cover;">
                            <div class="card-body d-flex flex-column">
                                <h6 class="card-title fw-semibold mb-1"><?php echo e($p['title']); ?></h6>
                                <small class="text-muted mb-2">Posted <?php echo formatDate($p['created_at']); ?></small>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="fw-bold text-primary"><?php echo formatPrice($p['price']); ?></span>
                                    <span class="badge bg-<?php echo statusBadgeClass($p['status']); ?>"><?php echo e($p['status']); ?></span>
                                </div>

                                <div class="d-grid gap-2 mt-auto">
                                    <a href="product-details.php?id=<?php echo (int)$p['product_id']; ?>" class="btn btn-sm btn-outline-secondary">View</a>
                                    <a href="edit-product.php?id=<?php echo (int)$p['product_id']; ?>" class="btn btn-sm btn-outline-primary">Edit</a>

                                    <?php if ($p['status'] === 'Available'): ?>
                                        <a href="mark-sold.php?product_id=<?php echo (int)$p['product_id']; ?>" class="btn btn-sm btn-success">Mark as Sold</a>
                                        <form method="POST" action="my-listings.php" data-confirm="Hide this listing? It will no longer appear publicly.">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="action" value="hide">
                                            <input type="hidden" name="product_id" value="<?php echo (int)$p['product_id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-warning w-100">Hide</button>
                                        </form>
                                    <?php elseif ($p['status'] === 'Hidden'): ?>
                                        <form method="POST" action="my-listings.php">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="action" value="unhide">
                                            <input type="hidden" name="product_id" value="<?php echo (int)$p['product_id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-success w-100">Unhide</button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ($p['status'] !== 'Hidden'): ?>
                                        <form method="POST" action="my-listings.php" data-confirm="Delete this listing? This cannot be undone from your side.">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="product_id" value="<?php echo (int)$p['product_id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger w-100">Delete</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-box-seam fs-1 text-muted"></i>
                <p class="text-muted mt-2">You haven't listed any items yet.</p>
                <a href="add-product.php" class="btn btn-primary">List Your First Item</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>