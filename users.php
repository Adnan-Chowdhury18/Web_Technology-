<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/includes/admin-auth.php';
$pdo = getDB();
require_admin_login();

$active = 'users';
$pageTitle = 'Manage Users';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require_or_die();
    $userId = (int)($_POST['user_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($userId > 0 && in_array($action, ['suspend', 'activate', 'delete'], true)) {
        $newStatus = $action === 'suspend' ? 'suspended' : ($action === 'activate' ? 'active' : 'deleted');
        $stmt = $pdo->prepare("UPDATE users SET status = :status, updated_at = NOW() WHERE user_id = :id");
        $stmt->execute([':status' => $newStatus, ':id' => $userId]);
        setFlash('success', 'User status updated successfully.');
    }
    header('Location: ' . BASE_URL . 'admin/users.php' . (isset($_GET['q']) ? '?q=' . urlencode($_GET['q']) : ''));
    exit;
}

$q = trim($_GET['q'] ?? '');
$params = [];
$where = '1=1';
if ($q !== '') {
    $where = "(full_name LIKE :kw OR email LIKE :kw)";
    $params[':kw'] = '%' . $q . '%';
}

$stmt = $pdo->prepare(
    "SELECT u.*, a.area_name,
     (SELECT COUNT(*) FROM products p WHERE p.user_id = u.user_id) AS product_count
     FROM users u LEFT JOIN areas a ON u.area_id = a.area_id
     WHERE {$where} ORDER BY u.created_at DESC"
);
$stmt->execute($params);
$users = $stmt->fetchAll();

require __DIR__ . '/includes/admin-header.php';
require __DIR__ . '/includes/admin-navbar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h3 class="fw-bold mb-0">Manage Users</h3>
  <form method="get" class="d-flex gap-2">
    <input type="text" name="q" class="form-control form-control-sm" placeholder="Search name or email" value="<?= e($q) ?>">
    <button class="btn btn-sm cs-btn-primary" type="submit"><i class="bi bi-search"></i></button>
  </form>
</div>

<div class="card shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
      <thead class="table-light">
        <tr>
          <th>Name</th>
          <th>Email</th>
          <th>University</th>
          <th>Area</th>
          <th>Listings</th>
          <th>Status</th>
          <th>Joined</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($users)): ?>
          <tr><td colspan="8" class="text-center text-muted py-4">No users found.</td></tr>
        <?php else: foreach ($users as $u): ?>
          <tr>
            <td><?= e($u['full_name']) ?></td>
            <td><?= e($u['email']) ?></td>
            <td><?= e($u['university'] ?: '—') ?></td>
            <td><?= e($u['area_name'] ?: '—') ?></td>
            <td><?= (int)$u['product_count'] ?></td>
            <td>
              <span class="badge <?= $u['status'] === 'active' ? 'bg-success' : ($u['status'] === 'suspended' ? 'bg-warning text-dark' : 'bg-secondary') ?>">
                <?= e(ucfirst($u['status'])) ?>
              </span>
            </td>
            <td class="small text-muted"><?= formatDate($u['created_at']) ?></td>
            <td>
              <div class="d-flex gap-1 flex-wrap">
                <?php if ($u['status'] !== 'active'): ?>
                  <form method="post" class="d-inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">
                    <input type="hidden" name="action" value="activate">
                    <button type="submit" class="btn btn-sm btn-outline-success" data-confirm="Activate this user?">Activate</button>
                  </form>
                <?php endif; ?>
                <?php if ($u['status'] !== 'suspended'): ?>
                  <form method="post" class="d-inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">
                    <input type="hidden" name="action" value="suspend">
                    <button type="submit" class="btn btn-sm btn-outline-warning" data-confirm="Suspend this user? They will not be able to log in.">Suspend</button>
                  </form>
                <?php endif; ?>
                <?php if ($u['status'] !== 'deleted'): ?>
                  <form method="post" class="d-inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">
                    <input type="hidden" name="action" value="delete">
                    <button type="submit" class="btn btn-sm btn-outline-danger" data-confirm="Mark this user as deleted? This cannot be easily undone.">Delete</button>
                  </form>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/includes/admin-footer.php'; ?>