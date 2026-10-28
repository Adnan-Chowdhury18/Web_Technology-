<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/includes/admin-auth.php';
$pdo = getDB();
require_admin_login();

$active = 'dashboard';
$pageTitle = 'Dashboard';

$totalUsers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE status != 'deleted'")->fetchColumn();
$totalProducts = (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$availableProducts = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE status = 'Available'")->fetchColumn();
$soldProducts = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE status = 'Sold'")->fetchColumn();
$totalCategories = (int)$pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$totalAreas = (int)$pdo->query("SELECT COUNT(*) FROM areas")->fetchColumn();
$totalInquiries = (int)$pdo->query("SELECT COUNT(*) FROM inquiries")->fetchColumn();
$pendingReports = (int)$pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'pending'")->fetchColumn();

$recentUsers = $pdo->query("SELECT user_id, full_name, email, created_at FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll();
$recentProducts = $pdo->query("SELECT product_id, title, price, status, created_at FROM products ORDER BY created_at DESC LIMIT 5")->fetchAll();
$recentReports = $pdo->query(
    "SELECT r.report_id, r.reason_category, r.status, r.created_at, p.title AS product_title
     FROM reports r JOIN products p ON r.product_id = p.product_id
     ORDER BY r.created_at DESC LIMIT 5"
)->fetchAll();

require __DIR__ . '/includes/admin-header.php';
require __DIR__ . '/includes/admin-navbar.php';
?>

<h3 class="fw-bold mb-4">Dashboard Overview</h3>

<div class="row g-3 mb-4">
  <div class="col-md-3 col-6">
    <div class="cs-stat-card">
      <div class="stat-number"><?= $totalUsers ?></div>
      <div class="stat-label"><i class="bi bi-people-fill"></i> Total Users</div>
    </div>
  </div>
  <div class="col-md-3 col-6">
    <div class="cs-stat-card">
      <div class="stat-number"><?= $totalProducts ?></div>
      <div class="stat-label"><i class="bi bi-box-seam-fill"></i> Total Products</div>
    </div>
  </div>
  <div class="col-md-3 col-6">
    <div class="cs-stat-card">
      <div class="stat-number"><?= $availableProducts ?></div>
      <div class="stat-label"><i class="bi bi-check-circle-fill"></i> Available Listings</div>
    </div>
  </div>
  <div class="col-md-3 col-6">
    <div class="cs-stat-card">
      <div class="stat-number"><?= $soldProducts ?></div>
      <div class="stat-label"><i class="bi bi-bag-check-fill"></i> Sold Items</div>
    </div>
  </div>
  <div class="col-md-3 col-6">
    <div class="cs-stat-card">
      <div class="stat-number"><?= $totalCategories ?></div>
      <div class="stat-label"><i class="bi bi-tags-fill"></i> Categories</div>
    </div>
  </div>
  <div class="col-md-3 col-6">
    <div class="cs-stat-card">
      <div class="stat-number"><?= $totalAreas ?></div>
      <div class="stat-label"><i class="bi bi-geo-alt-fill"></i> Areas</div>
    </div>
  </div>
  <div class="col-md-3 col-6">
    <div class="cs-stat-card">
      <div class="stat-number"><?= $totalInquiries ?></div>
      <div class="stat-label"><i class="bi bi-chat-dots-fill"></i> Inquiries Sent</div>
    </div>
  </div>
  <div class="col-md-3 col-6">
    <div class="cs-stat-card" style="border-left-color:<?= $pendingReports > 0 ? '#dc3545' : 'var(--cs-teal)' ?>;">
      <div class="stat-number"><?= $pendingReports ?></div>
      <div class="stat-label"><i class="bi bi-flag-fill"></i> Pending Reports</div>
    </div>
  </div>
</div>

<div class="row g-4">
  <div class="col-lg-4">
    <div class="card shadow-sm h-100">
      <div class="card-header fw-bold">Recent Users</div>
      <ul class="list-group list-group-flush">
        <?php if (empty($recentUsers)): ?>
          <li class="list-group-item text-muted">No users yet.</li>
        <?php else: foreach ($recentUsers as $u): ?>
          <li class="list-group-item">
            <div class="fw-semibold"><?= e($u['full_name']) ?></div>
            <div class="small text-muted"><?= e($u['email']) ?> &middot; <?= formatDate($u['created_at']) ?></div>
          </li>
        <?php endforeach; endif; ?>
      </ul>
      <div class="card-footer text-end">
        <a href="<?= BASE_URL ?>admin/users.php" class="small">Manage Users &rarr;</a>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card shadow-sm h-100">
      <div class="card-header fw-bold">Recent Listings</div>
      <ul class="list-group list-group-flush">
        <?php if (empty($recentProducts)): ?>
          <li class="list-group-item text-muted">No products yet.</li>
        <?php else: foreach ($recentProducts as $p): ?>
          <li class="list-group-item d-flex justify-content-between align-items-center">
            <div>
              <div class="fw-semibold"><?= e($p['title']) ?></div>
              <div class="small text-muted"><?= formatPrice($p['price']) ?> &middot; <?= formatDate($p['created_at']) ?></div>
            </div>
            <span class="badge <?= statusBadgeClass($p['status']) ?>"><?= e($p['status']) ?></span>
          </li>
        <?php endforeach; endif; ?>
      </ul>
      <div class="card-footer text-end">
        <a href="<?= BASE_URL ?>admin/products.php" class="small">Manage Products &rarr;</a>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card shadow-sm h-100">
      <div class="card-header fw-bold">Recent Reports</div>
      <ul class="list-group list-group-flush">
        <?php if (empty($recentReports)): ?>
          <li class="list-group-item text-muted">No reports yet.</li>
        <?php else: foreach ($recentReports as $r): ?>
          <li class="list-group-item">
            <div class="fw-semibold"><?= e($r['product_title']) ?></div>
            <div class="small text-muted"><?= e($r['reason_category']) ?> &middot; <?= formatDate($r['created_at']) ?></div>
            <span class="badge bg-secondary"><?= e($r['status']) ?></span>
          </li>
        <?php endforeach; endif; ?>
      </ul>
      <div class="card-footer text-end">
        <a href="<?= BASE_URL ?>admin/reports.php" class="small">Manage Reports &rarr;</a>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/admin-footer.php'; ?>