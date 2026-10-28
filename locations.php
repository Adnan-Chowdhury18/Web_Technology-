<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/includes/admin-auth.php';
$pdo = getDB();
require_admin_login();

$active = 'locations';
$pageTitle = 'Manage Locations';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require_or_die();
    $formAction = $_POST['form_action'] ?? '';

    if ($formAction === 'add') {
        $name = trim($_POST['area_name'] ?? '');
        $lat = trim($_POST['latitude'] ?? '');
        $lng = trim($_POST['longitude'] ?? '');

        if ($name === '' || mb_strlen($name) < 2) {
            $errors[] = 'Area name must be at least 2 characters.';
        }
        if ($lat !== '' && (!is_numeric($lat) || $lat < -90 || $lat > 90)) {
            $errors[] = 'Latitude must be a number between -90 and 90.';
        }
        if ($lng !== '' && (!is_numeric($lng) || $lng < -180 || $lng > 180)) {
            $errors[] = 'Longitude must be a number between -180 and 180.';
        }

        if (empty($errors)) {
            $checkStmt = $pdo->prepare("SELECT area_id FROM areas WHERE area_name = :name");
            $checkStmt->execute([':name' => $name]);
            if ($checkStmt->fetch()) {
                $errors[] = 'An area with this name already exists.';
            } else {
                $stmt = $pdo->prepare(
                    "INSERT INTO areas (area_name, latitude, longitude, is_active, created_at)
                     VALUES (:name, :lat, :lng, 1, NOW())"
                );
                $stmt->execute([
                    ':name' => $name,
                    ':lat' => $lat !== '' ? $lat : null,
                    ':lng' => $lng !== '' ? $lng : null,
                ]);
                setFlash('success', 'Area added successfully.');
                header('Location: ' . BASE_URL . 'admin/locations.php');
                exit;
            }
        }
    } elseif ($formAction === 'edit') {
        $areaId = (int)($_POST['area_id'] ?? 0);
        $name = trim($_POST['area_name'] ?? '');
        $lat = trim($_POST['latitude'] ?? '');
        $lng = trim($_POST['longitude'] ?? '');

        if ($areaId > 0 && $name !== '') {
            $stmt = $pdo->prepare(
                "UPDATE areas SET area_name = :name, latitude = :lat, longitude = :lng WHERE area_id = :id"
            );
            $stmt->execute([
                ':name' => $name,
                ':lat' => $lat !== '' ? $lat : null,
                ':lng' => $lng !== '' ? $lng : null,
                ':id' => $areaId,
            ]);
            setFlash('success', 'Area updated successfully.');
        }
        header('Location: ' . BASE_URL . 'admin/locations.php');
        exit;
    } elseif ($formAction === 'toggle') {
        $areaId = (int)($_POST['area_id'] ?? 0);
        if ($areaId > 0) {
            $stmt = $pdo->prepare("UPDATE areas SET is_active = 1 - is_active WHERE area_id = :id");
            $stmt->execute([':id' => $areaId]);
            setFlash('success', 'Area status toggled.');
        }
        header('Location: ' . BASE_URL . 'admin/locations.php');
        exit;
    }
}

$areas = $pdo->query(
    "SELECT a.*, (SELECT COUNT(*) FROM products p WHERE p.area_id = a.area_id) AS product_count
     FROM areas a ORDER BY a.area_name ASC"
)->fetchAll();

require __DIR__ . '/includes/admin-header.php';
require __DIR__ . '/includes/admin-navbar.php';
?>

<h3 class="fw-bold mb-4">Manage Locations (Areas)</h3>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger">
    <?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?>
  </div>
<?php endif; ?>

<div class="card shadow-sm mb-4">
  <div class="card-header fw-bold">Add New Area</div>
  <div class="card-body">
    <form method="post" class="row g-2 align-items-end">
      <?= csrf_field() ?>
      <input type="hidden" name="form_action" value="add">
      <div class="col-md-4">
        <label class="form-label small">Area Name</label>
        <input type="text" name="area_name" class="form-control" required>
      </div>
      <div class="col-md-3">
        <label class="form-label small">Latitude</label>
        <input type="text" name="latitude" class="form-control" placeholder="e.g. 22.3652">
      </div>
      <div class="col-md-3">
        <label class="form-label small">Longitude</label>
        <input type="text" name="longitude" class="form-control" placeholder="e.g. 91.8231">
      </div>
      <div class="col-md-2">
        <button type="submit" class="btn cs-btn-primary w-100">Add</button>
      </div>
    </form>
    <div class="form-text mt-2">
      Look up real coordinates on Google Maps (right-click a location — the numbers shown are latitude, longitude). Never guess — leave blank if unsure, but distance features will not work for this area until coordinates are set.
    </div>
  </div>
</div>

<div class="card shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
      <thead class="table-light">
        <tr>
          <th>Area Details</th>
          <th>Products</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($areas as $a): ?>
          <tr>
            <td style="min-width:420px;">
              <form method="post" class="d-flex gap-2 flex-wrap align-items-center">
                <?= csrf_field() ?>
                <input type="hidden" name="form_action" value="edit">
                <input type="hidden" name="area_id" value="<?= (int)$a['area_id'] ?>">
                <input type="text" name="area_name" class="form-control form-control-sm" value="<?= e($a['area_name']) ?>" style="width:140px;" placeholder="Area name">
                <input type="text" name="latitude" class="form-control form-control-sm" value="<?= e($a['latitude']) ?>" style="width:110px;" placeholder="Latitude">
                <input type="text" name="longitude" class="form-control form-control-sm" value="<?= e($a['longitude']) ?>" style="width:110px;" placeholder="Longitude">
                <button type="submit" class="btn btn-sm btn-outline-primary">Save</button>
              </form>
            </td>
            <td><?= (int)$a['product_count'] ?></td>
            <td><span class="badge <?= $a['is_active'] ? 'bg-success' : 'bg-secondary' ?>"><?= $a['is_active'] ? 'Active' : 'Inactive' ?></span></td>
            <td>
              <form method="post" class="d-inline">
                <?= csrf_field() ?>
                <input type="hidden" name="form_action" value="toggle">
                <input type="hidden" name="area_id" value="<?= (int)$a['area_id'] ?>">
                <button type="submit" class="btn btn-sm btn-outline-<?= $a['is_active'] ? 'warning' : 'success' ?>" data-confirm="<?= $a['is_active'] ? 'Deactivate' : 'Activate' ?> this area?">
                  <?= $a['is_active'] ? 'Deactivate' : 'Activate' ?>
                </button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/includes/admin-footer.php'; ?>