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
    $inquiryId = isset($_POST['inquiry_id']) && is_numeric($_POST['inquiry_id']) ? (int)$_POST['inquiry_id'] : 0;

    if ($inquiryId > 0) {
        $check = $pdo->prepare("SELECT inquiry_id, seller_id, buyer_id FROM inquiries WHERE inquiry_id = ?");
        $check->execute([$inquiryId]);
        $inq = $check->fetch();

        if ($inq && ((int)$inq['seller_id'] === $userId || (int)$inq['buyer_id'] === $userId)) {
            if ($action === 'mark_read' && (int)$inq['seller_id'] === $userId) {
                $upd = $pdo->prepare("UPDATE inquiries SET status = 'read' WHERE inquiry_id = ?");
                $upd->execute([$inquiryId]);
            } elseif ($action === 'close') {
                $upd = $pdo->prepare("UPDATE inquiries SET status = 'closed' WHERE inquiry_id = ?");
                $upd->execute([$inquiryId]);
                setFlash('success', 'Inquiry closed.');
            }
        }
    }
    $tabParam = ($_GET['tab'] ?? 'received') === 'sent' ? 'sent' : 'received';
    header('Location: my-inquiries.php?tab=' . $tabParam);
    exit;
}

$tab = ($_GET['tab'] ?? 'received') === 'sent' ? 'sent' : 'received';

if ($tab === 'received') {
    $stmt = $pdo->prepare("
        SELECT i.inquiry_id, i.message, i.status, i.created_at,
               p.product_id, p.title AS product_title,
               u.full_name AS other_name, u.whatsapp_number AS other_whatsapp
        FROM inquiries i
        JOIN products p ON i.product_id = p.product_id
        JOIN users u ON i.buyer_id = u.user_id
        WHERE i.seller_id = ?
        ORDER BY i.created_at DESC
    ");
} else {
    $stmt = $pdo->prepare("
        SELECT i.inquiry_id, i.message, i.status, i.created_at,
               p.product_id, p.title AS product_title,
               u.full_name AS other_name, u.whatsapp_number AS other_whatsapp
        FROM inquiries i
        JOIN products p ON i.product_id = p.product_id
        JOIN users u ON i.seller_id = u.user_id
        WHERE i.buyer_id = ?
        ORDER BY i.created_at DESC
    ");
}
$stmt->execute([$userId]);
$inquiries = $stmt->fetchAll();

$pageTitle = 'My Inquiries';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<section class="py-4">
    <div class="container">
        <h2 class="cs-heading mb-1">My Inquiries</h2>
        <p class="text-muted mb-4">Messages between you and other students about listings.</p>

        <ul class="nav nav-tabs mb-4">
            <li class="nav-item">
                <a class="nav-link <?php echo $tab === 'received' ? 'active' : ''; ?>" href="my-inquiries.php?tab=received">Received</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $tab === 'sent' ? 'active' : ''; ?>" href="my-inquiries.php?tab=sent">Sent</a>
            </li>
        </ul>

        <?php if (!empty($inquiries)): ?>
            <?php foreach ($inquiries as $inq): ?>
                <div class="border rounded shadow-sm p-3 mb-3 bg-white">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                        <div>
                            <h6 class="mb-1">
                                <a href="product-details.php?id=<?php echo (int)$inq['product_id']; ?>"><?php echo e($inq['product_title']); ?></a>
                            </h6>
                            <small class="text-muted">
                                <?php echo $tab === 'received' ? 'From: ' : 'To: '; ?><?php echo e($inq['other_name']); ?> • <?php echo formatDate($inq['created_at']); ?>
                            </small>
                        </div>
                        <span class="badge bg-<?php echo $inq['status'] === 'new' ? 'warning' : ($inq['status'] === 'closed' ? 'secondary' : 'info'); ?> text-dark">
                            <?php echo e(ucfirst($inq['status'])); ?>
                        </span>
                    </div>
                    <p class="mt-2 mb-2"><?php echo nl2br(e($inq['message'])); ?></p>

                    <div class="d-flex gap-2 flex-wrap">
                        <?php if (!empty($inq['other_whatsapp'])): ?>
                            <?php $wa = preg_replace('/[^0-9]/', '', $inq['other_whatsapp']); ?>
                            <a href="https://wa.me/<?php echo e($wa); ?>" target="_blank" class="btn btn-sm btn-success">
                                <i class="bi bi-whatsapp"></i> Reply on WhatsApp
                            </a>
                        <?php endif; ?>

                        <?php if ($tab === 'received' && $inq['status'] === 'new'): ?>
                            <form method="POST" action="my-inquiries.php?tab=received">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="action" value="mark_read">
                                <input type="hidden" name="inquiry_id" value="<?php echo (int)$inq['inquiry_id']; ?>">
                                <button type="submit" class="btn btn-sm btn-outline-secondary">Mark as Read</button>
                            </form>
                        <?php endif; ?>

                        <?php if ($inq['status'] !== 'closed'): ?>
                            <form method="POST" action="my-inquiries.php?tab=<?php echo e($tab); ?>" data-confirm="Close this inquiry?">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="action" value="close">
                                <input type="hidden" name="inquiry_id" value="<?php echo (int)$inq['inquiry_id']; ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">Close</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-chat-dots fs-1 text-muted"></i>
                <p class="text-muted mt-2">No inquiries <?php echo $tab === 'received' ? 'received' : 'sent'; ?> yet.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>