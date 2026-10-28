<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/includes/admin-auth.php';
$pdo = getDB();

if (is_admin_logged_in()) {
    header('Location: ' . BASE_URL . 'admin/dashboard.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require_or_die();
    $email = trim(strtolower($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $errors[] = 'Please enter both email and password.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $admin = $stmt->fetch();

        if (!$admin || !password_verify($password, $admin['password_hash'])) {
            $errors[] = 'Invalid email or password.';
        } else {
            admin_login_user($admin);
            header('Location: ' . BASE_URL . 'admin/dashboard.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login - <?= e(SITE_NAME) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body class="cs-admin-body d-flex align-items-center" style="min-height:100vh;">
<div class="container" style="max-width: 420px;">
  <div class="text-center mb-4">
    <i class="bi bi-shield-lock-fill display-4 text-dark"></i>
    <h3 class="fw-bold mt-2">ClassSwap Admin</h3>
  </div>
  <div class="card shadow-sm">
    <div class="card-body p-4">
      <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
          <?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?>
        </div>
      <?php endif; ?>
      <form method="post" action="">
        <?= csrf_field() ?>
        <div class="mb-3">
          <label class="form-label">Admin Email</label>
          <input type="email" name="email" class="form-control" required autofocus>
        </div>
        <div class="mb-3">
          <label class="form-label">Password</label>
          <input type="password" name="password" class="form-control" required>
        </div>
        <button type="submit" class="btn cs-btn-primary w-100">Login</button>
      </form>
    </div>
  </div>
  <p class="text-center mt-3"><a href="<?= BASE_URL ?>index.php" class="text-muted small">&larr; Back to ClassSwap</a></p>
</div>
</body>
</html>