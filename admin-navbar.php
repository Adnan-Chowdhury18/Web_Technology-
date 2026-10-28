<?php $admin = current_admin(); ?>
<nav class="navbar navbar-expand-lg navbar-dark cs-admin-navbar">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="<?= BASE_URL ?>admin/dashboard.php">
      <i class="bi bi-shield-lock-fill"></i> ClassSwap Admin
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="adminNav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link <?= ($active ?? '') === 'dashboard' ? 'active' : '' ?>" href="<?= BASE_URL ?>admin/dashboard.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link <?= ($active ?? '') === 'users' ? 'active' : '' ?>" href="<?= BASE_URL ?>admin/users.php">Users</a></li>
        <li class="nav-item"><a class="nav-link <?= ($active ?? '') === 'products' ? 'active' : '' ?>" href="<?= BASE_URL ?>admin/products.php">Products</a></li>
        <li class="nav-item"><a class="nav-link <?= ($active ?? '') === 'categories' ? 'active' : '' ?>" href="<?= BASE_URL ?>admin/categories.php">Categories</a></li>
        <li class="nav-item"><a class="nav-link <?= ($active ?? '') === 'locations' ? 'active' : '' ?>" href="<?= BASE_URL ?>admin/locations.php">Locations</a></li>
        <li class="nav-item"><a class="nav-link <?= ($active ?? '') === 'reports' ? 'active' : '' ?>" href="<?= BASE_URL ?>admin/reports.php">Reports</a></li>
      </ul>
      <span class="navbar-text text-white me-3">
        <i class="bi bi-person-circle"></i> <?= e($admin['name'] ?? 'Admin') ?>
      </span>
      <a href="<?= BASE_URL ?>admin/logout.php" class="btn btn-sm btn-outline-light">
        <i class="bi bi-box-arrow-right"></i> Logout
      </a>
    </div>
  </div>
</nav>
<div class="container-fluid py-4">
<?php foreach (getFlashes() as $type => $messages): ?>
  <?php foreach ($messages as $msg): ?>
    <div class="alert alert-<?= $type === 'error' ? 'danger' : 'success' ?> alert-dismissible fade show" role="alert">
      <?= e($msg) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endforeach; ?>
<?php endforeach; ?>