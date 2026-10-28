<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/auth.php';
$pdo = getDB();
http_response_code(404);

$pageTitle = 'Page Not Found';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>

<div class="container py-5 text-center">
  <i class="bi bi-signpost-2 display-1 text-muted"></i>
  <h1 class="fw-bold mt-3">404</h1>
  <p class="lead text-muted">Sorry, the page you're looking for doesn't exist or may have been moved.</p>
  <div class="d-flex justify-content-center gap-2 mt-4">
    <a href="<?= BASE_URL ?>index.php" class="btn cs-btn-primary"><i class="bi bi-house"></i> Go Home</a>
    <a href="<?= BASE_URL ?>products.php" class="btn btn-outline-secondary">Browse Products</a>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>