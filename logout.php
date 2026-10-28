<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/admin-auth.php';

admin_logout_user();
header('Location: ' . BASE_URL . 'admin/login.php');
exit;