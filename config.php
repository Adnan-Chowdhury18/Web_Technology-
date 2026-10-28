<?php
/**
 * config/config.php
 * Site-wide constants and bootstrap settings.
 * Must be included before any session/auth logic.
 */

// ---- Base URL (adjust if your folder name differs) ----
define('BASE_URL', '/classswap/');

// ---- Site identity ----
define('SITE_NAME', 'ClassSwap');
define('SITE_TAGLINE', 'Buy & Sell Student Essentials Near You.');

// ---- Upload settings ----
define('UPLOAD_DIR_PRODUCTS', __DIR__ . '/../uploads/products/');
define('UPLOAD_URL_PRODUCTS', BASE_URL . 'uploads/products/');
define('UPLOAD_DIR_PROFILES', __DIR__ . '/../uploads/profiles/');
define('UPLOAD_URL_PROFILES', BASE_URL . 'uploads/profiles/');
define('MAX_UPLOAD_SIZE_BYTES', 3 * 1024 * 1024); // 3MB per image
define('MAX_PRODUCT_IMAGES', 5);
define('ALLOWED_IMAGE_EXT', ['jpg', 'jpeg', 'png', 'webp']);
define('ALLOWED_IMAGE_MIME', ['image/jpeg', 'image/png', 'image/webp']);

// ---- Placeholder image shown when a product/profile has no photo ----
define('PLACEHOLDER_PRODUCT_IMAGE', BASE_URL . 'assets/images/placeholder-product.png');
define('PLACEHOLDER_PROFILE_IMAGE', BASE_URL . 'assets/images/placeholder-profile.png');

// ---- Secure session configuration ----
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => BASE_URL,
        'httponly' => true,
        'samesite' => 'Lax',
        // 'secure' => true, // enable this once served over HTTPS
    ]);
    session_start();
}

// ---- Error display: OFF for users, ON only in log files ----
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/database.php';