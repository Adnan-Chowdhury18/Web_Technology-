<?php
/**
 * config/database.php
 * Central PDO database connection for ClassSwap.
 * Credentials are isolated here so they are never hardcoded
 * inside page logic. For a real deployment, move the constants
 * below into a non-web-accessible .env-style file.
 */

// ---- Local XAMPP defaults ----
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'classswap');
define('DB_USER', 'root');
define('DB_PASS', '');        // default XAMPP root password is empty
define('DB_CHARSET', 'utf8mb4');

function getDB(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Never leak raw DB errors to the browser in production.
            error_log('Database connection failed: ' . $e->getMessage());
            http_response_code(500);
            die('A server error occurred. Please try again later.');
        }
    }

    return $pdo;
}