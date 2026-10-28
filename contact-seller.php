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

$stmt = $pdo->prepare("
    SELECT p.product_id, p.title, p.user_id AS seller_id, p.status,
           u.full_name AS seller_name
    FROM products p
    LEFT JOIN users u ON p.user_id = u.user_id
    WHERE p.product_id = ?
");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    setFlash('error', 'Product not found.');
    header('Location: products.php');
    exit;
}

$buyerId = current_user_id();
$sellerId = (int)$product['seller_id'];

if ($buyerId === $sellerId) {
    setFlash('error', 'You cannot send an inquiry about your own listing.');
    header('Location: product-details.php?id=' . $productId);
    exit;
}

$errors = [];
$messageText = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require_or_die();
    $messageText = trim($_POST['message'] ?? '');

    if ($messageText === '' || mb_strlen($messageText) < 5) {
        $errors[] = 'Please write a message of at least 5 characters.';
    }
    if (mb_strlen($messageText) > 1000) {
        $errors[] = 'Message is too long (max 1000 characters).';
    }

    if (empty($errors)) {
        $ins = $pdo->prepare("INSERT INTO inquiries (product_id, buyer_id, seller_id, message, status, created_at) VALUES (?, ?, ?, ?, 'new', NOW())");
        $ins->execute([$productId, $buyerId, $sellerId, $messageText]);
        setFlash('success', 'Your message has been sent to the seller.');
        header('Location: product-details.php?id=' . $productId);
        exit;
    }
}

$pageTitle = 'Contact Seller';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-7 col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h4 class="mb-1">Contact Seller</h4>
                        <p class="text-muted mb-4">Regarding: <strong><?php echo e($product['title']); ?></strong> (Seller: <?php echo e($product['seller_name']); ?>)</p>

                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0 ps-3">
                                    <?php foreach ($errors as $err): ?>
                                        <li><?php echo e($err); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="contact-seller.php?product_id=<?php echo $productId; ?>">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="product_id" value="<?php echo $productId; ?>">
                            <div class="mb-3">
                                <label class="form-label">Your Message</label>
                                <textarea name="message" rows="5" class="form-control" placeholder="Hi, is this item still available?"><?php echo e($messageText); ?></textarea>
                            </div>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Send Message</button>
                                <a href="product-details.php?id=<?php echo $productId; ?>" class="btn btn-outline-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>