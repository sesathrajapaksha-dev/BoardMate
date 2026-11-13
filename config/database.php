<?php
/**
 * BoardMate - Database & application configuration
 * Default XAMPP: user "root", empty password.
 */

// ---------- Database settings ----------
define('DB_HOST', 'localhost');
define('DB_NAME', 'boardmate_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// ---------- Application settings ----------
// URL path of the project (no trailing slash). Change if you rename the folder.
define('BASE_URL', '/BoardMate');

// Filesystem path of the project root (no trailing slash)
define('ROOT_PATH', dirname(__DIR__));

// Image upload settings
define('UPLOAD_DIR', ROOT_PATH . '/uploads/boardings/');
define('UPLOAD_URL', BASE_URL . '/uploads/boardings/');
define('MAX_IMAGE_SIZE', 2 * 1024 * 1024); // 2 MB per image
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp']);
define('ALLOWED_IMAGE_EXT', ['jpg', 'jpeg', 'png', 'webp']);

/**
 * Returns a shared PDO connection (created once per request).
 */
function getDB(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false, // real prepared statements
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            die('<h2 style="font-family:sans-serif;color:#b91c1c">Database connection failed.</h2>'
              . '<p style="font-family:sans-serif">Make sure MySQL is running in XAMPP and that '
              . '<b>boardmate_db</b> has been imported. Check <code>config/database.php</code>.</p>');
        }
    }

    return $pdo;
}
