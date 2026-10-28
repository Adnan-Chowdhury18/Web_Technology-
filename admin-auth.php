<?php
/**
 * admin/includes/admin-auth.php
 * Completely separate authentication system for admins.
 * Uses $_SESSION['admin_id'] - never mixed with student $_SESSION['user_id'].
 */

function admin_login_user(array $admin): void
{
    session_regenerate_id(true);
    $_SESSION['admin_id'] = $admin['admin_id'];
    $_SESSION['admin_name'] = $admin['name'];
}

function is_admin_logged_in(): bool
{
    return isset($_SESSION['admin_id']);
}

function current_admin(): ?array
{
    static $cached = null;
    if (!is_admin_logged_in()) {
        return null;
    }
    if ($cached !== null) {
        return $cached;
    }
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT admin_id, name, email, created_at FROM admins WHERE admin_id = :id");
    $stmt->execute([':id' => $_SESSION['admin_id']]);
    $admin = $stmt->fetch();
    if (!$admin) {
        admin_logout_user();
        return null;
    }
    $cached = $admin;
    return $cached;
}

function require_admin_login(): void
{
    if (!is_admin_logged_in()) {
        header('Location: ' . BASE_URL . 'admin/login.php');
        exit;
    }
}

function admin_logout_user(): void
{
    unset($_SESSION['admin_id']);
    unset($_SESSION['admin_name']);
}