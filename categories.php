<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/includes/admin-auth.php';
$pdo = getDB();
require_admin_login();

$active = 'categories';
$pageTitle = 'Manage Categories';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require_or_die();
    $formAction = $_POST['form_action'] ?? '';

    if ($formAction === 'add') {
        $name = trim($_POST['category_name'] ?? '');
        $icon = trim($_POST['icon'] ?? '');
        if ($name === '' || mb_strlen($name) < 2) {
            $errors[] = 'Category name must be at least 2 characters.';
        } else {
            $checkStmt = $pdo->prepare("SELECT category_id FROM categories WHERE category_name = :name");
            $checkStmt->execute([':name' => $name]);
            if ($checkStmt->fetch()) {
                $errors[] = 'A category with this name already exists.';
            } else {
                $stmt = $pdo->prepare("INSERT INTO categories (category_name, icon, is_active, created_at) VALUES (:name, :icon, 1, NOW())");
                $stmt->execute([':name' => $name, ':icon' => $icon ?: 'tag']);
                setFlash('success', 'Category added successfully.');
                header('Location: ' . BASE_URL . 'admin/categories.php');
                exit;
            }
        }
    } elseif ($formAction === 'edit') {
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $name = trim($_POST['category_name'] ?? '');
        $icon = trim($_POST['icon'] ?? '');
        if ($categoryId > 0 && $name !== '') {
            $stmt = $pdo->prepare("UPDATE categories SET category_name = :name, icon = :icon WHERE category_id = :id");
            $stmt->execute([':name' => $name, ':icon' => $icon ?: 'tag', ':id' => $categoryId]);
            setFlash('success', 'Category updated successfully.');
        }
        header('Location: ' . BASE_URL . 'admin/categories.php');
        exit;
    } elseif ($formAction === 'toggle') {
        $categoryId = (int)($_POST['category_id'] ?? 0);
        if ($categoryId > 0) {
            $stmt = $pdo->prepare("UPDATE categories SET is_active = 1 - is_active WHERE category_id = :id");
            $stmt->execute([':id' => $categoryId]);
            setFlash('success', 'Category status toggled.');
        }
        header('Location: ' . BASE_URL . 'admin/categories.php');
        exit;
    }
}

$categories = $pdo->query(
    "SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.category_id) AS product_count
     FROM categories c ORDER BY c.category_name ASC"
)->fetchAll();

require __DIR__ . '/includes/admin-header.php';
require __DIR__ . '/includes/admin-navbar.php';
?>

<h3 class="fw-bold mb-4">Manage Categories</h3>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger">
    <?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?>
  </div>
<?php endif; ?>

<div class="card shadow-sm mb-4">
  <div class="card-header fw-bold">Add New Category</div>
  <div class="card-body">
    <form method="post" class="row g-2 align-items-end">
      <?= csrf_field() ?>
      <input type="hidden" name="form_action" value="add">
      <div class="col-md-5">
        <label class="form-label small">Category Name</label>
        <input type="text" name="category_name" class="form-control" required>
      </div>
      <div class="col-md-5">
        <label class="form-label small">Bootstrap Icon Name (without "bi-")</label>
        <input type="text" name="icon" class="form-control" placeholder="e.g. book-half">
        <div class="form-text">Find icon names at <a href="https://icons.getbootstrap.com" target="_blank">icons.getbootstrap.com</a></div>
      </div>
      <div class="col-md-2">
        <button type="submit" class="btn cs-btn-primary w-100">Add</button>
      </div>
    </form>
  </div>
</div>

<div class="card shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
      <thead class="table-light">
        <tr>
          <th>Icon</th>
          <th>Name / Icon Name</th>
          <th>Products</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($categories as $c): ?>
          <tr>
            <td><i class="bi bi-<?= e($c['icon'] ?: 'tag') ?> fs-5"></i></td>
            <td>
              <form method="post" class="d-flex gap-2 align-items-center flex-wrap">
                <?= csrf_field() ?>
                <input type="hidden" name="form_action" value="edit">
                <input type="hidden" name="category_id" value="<?= (int)$c['category_id'] ?>">
                <input type="text" name="category_name" class="form-control form-control-sm" value="<?= e($c['category_name']) ?>" style="min-width:150px;">
                <input type="text" name="icon" class="form-control form-control-sm" value="<?= e($c['icon']) ?>" style="min-width:120px;" placeholder="icon name">
                <button type="submit" class="btn btn-sm btn-outline-primary">Save</button>
              </form>
            </td>
            <td><?= (int)$c['product_count'] ?></td>
            <td>
              <span class="badge <?= $c['is_active'] ? 'bg-success' : 'bg-secondary' ?>"><?= $c['is_active'] ? 'Active' : 'Inactive' ?></span>
            </td>
            <td>
              <form method="post" class="d-inline">
                <?= csrf_field() ?>
                <input type="hidden" name="form_action" value="toggle">
                <input type="hidden" name="category_id" value="<?= (int)$c['category_id'] ?>">
                <button type="submit" class="btn btn-sm btn-outline-<?= $c['is_active'] ? 'warning' : 'success' ?>" data-confirm="<?= $c['is_active'] ? 'Deactivate' : 'Activate' ?> this category?">
                  <?= $c['is_active'] ? 'Deactivate' : 'Activate' ?>
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