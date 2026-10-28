<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/auth.php';

$pdo = getDB();
require_login();

$productId = isset($_GET['product_id']) && is_numeric($_GET['product_id'])
    ? (int)$_GET['product_id']
    : (isset($_POST['product_id']) && is_numeric($_POST['product_id']) ? (int)$_POST['product_id'] : 0);

$product = require_product_owner($productId);

$buyerStmt = $pdo->prepare("
    SELECT DISTINCT u.user_id, u.full_name, u.email
    FROM inquiries i
    JOIN users u ON i.buyer_id = u.user_id
    WHERE i.product_id = ?
    ORDER BY u.full_name ASC
");
$buyerStmt->execute([$productId]);
$potentialBuyers = $buyerStmt->fetchAll();

$errors = [];
$selectedBuyerId = '';
$amount = $product['price'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require_or_die();
    $selectedBuyerId = $_POST['buyer_id'] ?? '';
    $amount = $_POST['amount'] ?? $product['price'];

    if (!is_numeric($amount) || (float)$amount < 0) {
        $errors[] = 'Please enter a valid amount.';
    }
    if ($selectedBuyerId !== '' && !is_numeric($selectedBuyerId)) {
        $errors[] = 'Invalid buyer selection.';
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $upd = $pdo->prepare("UPDATE products SET status = 'Sold', updated_at = NOW() WHERE product_id = ?");
            $upd->execute([$productId]);

            if ($selectedBuyerId !== '') {
                $ins = $pdo->prepare("
                    INSERT INTO transactions (product_id, buyer_id, seller_id, amount, payment_method, payment_status, transaction_status, transaction_date)
                    VALUES (?, ?, ?, ?, 'Cash on Meetup', 'Completed', 'Completed', NOW())
                ");
                $ins->execute([$productId, (int)$selectedBuyerId, current_user_id(), (float)$amount]);
            }

            $pdo->commit();
            setFlash('success', 'Listing marked as sold.' . ($selectedBuyerId !== '' ? ' Transaction recorded.' : ''));
            header('Location: my-listings.php');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Something went wrong. Please try again.';
        }
    }
}

$pageTitle = 'Mark as Sold';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-7 col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h4 class="mb-1">Mark as Sold</h4>
                        <p class="text-muted mb-4"><?php echo e($product['title']); ?></p>

                        <div class="alert alert-info small">
                            <i class="bi bi-info-circle me-1"></i>
                            ClassSwap only supports Cash on Meetup. If a buyer contacted you through this site, select them below to keep a record. If sold outside the site, leave the buyer field empty.
                        </div>

                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0 ps-3">
                                    <?php foreach ($errors as $err): ?>
                                        <li><?php echo e($err); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="mark-sold.php?product_id=<?php echo $productId; ?>">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="product_id" value="<?php echo $productId; ?>">

                            <div class="mb-3">
                                <label class="form-label">Buyer (optional)</label>
                                <select name="buyer_id" class="form-select">
                                    <option value="">No buyer on record</option>
                                    <?php foreach ($potentialBuyers as $b): ?>
                                        <option value="<?php echo (int)$b['user_id']; ?>" <?php echo ($selectedBuyerId == $b['user_id']) ? 'selected' : ''; ?>>
                                            <?php echo e($b['full_name']); ?> (<?php echo e($b['email']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (empty($potentialBuyers)): ?>
                                    <small class="text-muted">No one has sent an inquiry for this item yet.</small>
                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Final Sale Amount (৳)</label>
                                <input type="number" step="0.01" name="amount" class="form-control" value="<?php echo e($amount); ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Payment Method</label>
                                <input type="text" class="form-control" value="Cash on Meetup" disabled>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Confirm Sold</button>
                                <a href="my-listings.php" class="btn btn-outline-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>