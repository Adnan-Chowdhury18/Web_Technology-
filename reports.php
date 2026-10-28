<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/includes/admin-auth.php';
$pdo = getDB();
require_admin_login();

$active = 'reports';
$pageTitle = 'Manage Reports';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require_or_die();
    $reportId = (int)($_POST['report_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($reportId > 0 && in_array($action, ['reviewed', 'dismissed', 'action_taken'], true)) {
        $stmt = $pdo->prepare("UPDATE reports SET status = :status WHERE report_id = :id");
        $stmt->execute([':status' => $action, ':id' => $reportId]);
        setFlash('success', 'Report status updated.');
    } elseif ($reportId > 0 && $action === 'hide_product') {
        $prodStmt = $pdo->prepare("SELECT product_id FROM reports WHERE report_id = :id");
        $prodStmt->execute([':id' => $reportId]);
        $row = $prodStmt->fetch();
        if ($row) {
            $pdo->prepare("UPDATE products SET status = 'Hidden', updated_at = NOW() WHERE product_id = :pid")
                ->execute([':pid' => $row['product_id']]);
            $pdo->prepare("UPDATE reports SET status = 'action_taken' WHERE report_id = :id")
                ->execute([':id' => $reportId]);
            setFlash('success', 'Listing hidden and report marked as action taken.');
        }
    }
    header('Location: ' . BASE_URL . 'admin/reports.php' . (isset($_GET['status']) ? '?status=' . urlencode($_GET['status']) : ''));
    exit;
}

$statusFilter = $_GET['status'] ?? '';
$conditions = ['1=1'];
$params = [];
if (in_array($statusFilter, ['pending', 'reviewed', 'dismissed', 'action_taken'], true)) {
    $conditions[] = "r.status = :status";
    $params[':status'] = $statusFilter;
}
$whereSql = implode(' AND ', $conditions);

$stmt = $pdo->prepare(
    "SELECT r.*, p.title AS product_title, p.status AS product_status, u.full_name AS reporter_name
     FROM reports r
     JOIN products p ON r.product_id = p.product_id
     JOIN users u ON r.reported_by = u.user_id
     WHERE {$whereSql}
     ORDER BY FIELD(r.status, 'pending', 'reviewed', 'dismissed', 'action_taken'), r.created_at DESC"
);
$stmt->execute($params);
$reports = $stmt->fetchAll();

require __DIR__ . '/includes/admin-header.php';
require __DIR__ . '/includes/admin-navbar.php';
?>

<h3 class="fw-bold mb-4">Manage Reports</h3>

<form method="get" class="mb-4">
  <select name="status" class="form-select form-select-sm w-auto d-inline-block" onchange="this.form.submit()">
    <option value="">All Statuses</option>
    <?php foreach (['pending', 'reviewed', 'dismissed', 'action_taken'] as $s): ?>
      <option value="<?= $s ?>" <?= $statusFilter === $s ? 'selected' : '' ?>><?= e(ucwords(str_replace('_', ' ', $s))) ?></option>
    <?php endforeach; ?>
  </select>
</form>

<?php if (empty($reports)): ?>
  <div class="text-center text-muted py-5">
    <i class="bi bi-flag display-1"></i>
    <p class="mt-3">No reports found.</p>
  </div>
<?php else: ?>
  <div class="row g-3">
    <?php foreach ($reports as $r): ?>
      <div class="col-md-6">
        <div class="card shadow-sm h-100">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-2">
              <h6 class="mb-0">
                <a href="<?= BASE_URL ?>product-details.php?id=<?= (int)$r['product_id'] ?>" target="_blank"><?= e($r['product_title']) ?></a>
              </h6>
              <span class="badge <?= $r['status'] === 'pending' ? 'bg-warning text-dark' : ($r['status'] === 'action_taken' ? 'bg-danger' : ($r['status'] === 'reviewed' ? 'bg-info' : 'bg-secondary')) ?>">
                <?= e(ucwords(str_replace('_', ' ', $r['status']))) ?>
              </span>
            </div>
            <p class="small text-muted mb-1">Reported by: <?= e($r['reporter_name']) ?> &middot; <?= formatDate($r['created_at']) ?></p>
            <p class="mb-1"><strong>Reason:</strong> <?= e($r['reason_category']) ?></p>
            <?php if (!empty($r['details'])): ?>
              <p class="mb-2"><strong>Details:</strong> <?= nl2br(e($r['details'])) ?></p>
            <?php endif; ?>
            <p class="small mb-3">Listing status: <span class="badge <?= statusBadgeClass($r['product_status']) ?>"><?= e($r['product_status']) ?></span></p>

            <div class="d-flex gap-2 flex-wrap">
              <?php if ($r['status'] !== 'reviewed'): ?>
                <form method="post" class="d-inline">
                  <?= csrf_field() ?>
                  <input type="hidden" name="report_id" value="<?= (int)$r['report_id'] ?>">
                  <input type="hidden" name="action" value="reviewed">
                  <button type="submit" class="btn btn-sm btn-outline-info">Mark Reviewed</button>
                </form>
              <?php endif; ?>
              <?php if ($r['status'] !== 'dismissed'): ?>
                <form method="post" class="d-inline">
                  <?= csrf_field() ?>
                  <input type="hidden" name="report_id" value="<?= (int)$r['report_id'] ?>">
                  <input type="hidden" name="action" value="dismissed">
                  <button type="submit" class="btn btn-sm btn-outline-secondary" data-confirm="Dismiss this report?">Dismiss</button>
                </form>
              <?php endif; ?>
              <?php if ($r['product_status'] !== 'Hidden'): ?>
                <form method="post" class="d-inline">
                  <?= csrf_field() ?>
                  <input type="hidden" name="report_id" value="<?= (int)$r['report_id'] ?>">
                  <input type="hidden" name="action" value="hide_product">
                  <button type="submit" class="btn btn-sm btn-danger" data-confirm="Hide the reported listing AND mark this report as action taken?">Hide Listing</button>
                </form>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/admin-footer.php'; ?>