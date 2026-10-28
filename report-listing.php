<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/auth.php';
$pdo = getDB();
require_login();

$productId = (int)($_GET['id'] ?? $_POST['product_id'] ?? 0);

$stmt = $pdo->prepare("SELECT product_id, title, user_id FROM products WHERE product_id = :id");
$stmt->execute([':id' => $productId]);
$product = $stmt->fetch();

if (!$product) {
    setFlash('error', 'Product not found.');
    header('Location: ' . BASE_URL . 'products.php');
    exit;
}

if ((int)$product['user_id'] === (int)current_user_id()) {
    setFlash('error', 'You cannot report your own listing.');
    header('Location: ' . BASE_URL . 'product-details.php?id=' . $productId);
    exit;
}

$errors = [];
$validReasons = ['Spam', 'Fraud', 'Prohibited Item', 'Inappropriate', 'Other'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require_or_die();
    $reason = $_POST['reason_category'] ?? '';
    $details = trim($_POST['details'] ?? '');

    if (!in_array($reason, $validReasons, true)) {
        $errors[] = 'Please select a valid reason.';
    }
    if (mb_strlen($details) > 2000) {
        $errors[] = 'Details must be under 2000 characters.';
    }

    if (empty($errors)) {
        $insertStmt = $pdo->prepare(
            "INSERT INTO reports (product_id, reported_by, reason_category, details, status, created_at)
             VALUES (:pid, :uid, :reason, :details, 'pending', NOW())"
        );
        $insertStmt->execute([
            ':pid' => $productId,
            ':uid' => current_user_id(),
            ':reason' => $reason,
            ':details' => $details !== '' ? $details : null,
        ]);
        setFlash('success', 'Thank you. This listing has been reported to our moderators.');
        header('Location: ' . BASE_URL . 'product-details.php?id=' . $productId);
        exit;
    }
}

$pageTitle = 'Report Listing';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>

<div class="container py-4" style="max-width: 600px;">
  <h3 class="fw-bold mb-3">Report Listing</h3>
  <p class="text-muted">You are reporting: <strong><?= e($product['title']) ?></strong></p>

  <?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
      <?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?>
    </div>
  <?php endif; ?>

  <form method="post" action="">
    <?= csrf_field() ?>
    <input type="hidden" name="product_id" value="<?= (int)$productId ?>">
    <div class="mb-3">
      <label class="form-label">Reason</label>
      <select name="reason_category" class="form-select" required>
        <option value="">-- Select a reason --</option>
        <?php foreach ($validReasons as $r): ?>
          <option value="<?= e($r) ?>" <?= (($_POST['reason_category'] ?? '') === $r) ? 'selected' : '' ?>><?= e($r) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="mb-3">
      <label class="form-label">Additional Details (optional)</label>
      <textarea name="details" class="form-control" rows="4" maxlength="2000"><?= e($_POST['details'] ?? '') ?></textarea>
    </div>
    <div class="d-flex gap-2">
      <button type="submit" class="btn btn-danger"><i class="bi bi-flag"></i> Submit Report</button>
      <a href="<?= BASE_URL ?>product-details.php?id=<?= (int)$productId ?>" class="btn btn-outline-secondary">Cancel</a>
    </div>
  </form>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>