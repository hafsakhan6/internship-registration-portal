<?php
// config.php  – database connection (PDO)
define('DB_HOST', 'localhost');
define('DB_NAME', 'internship_portal');
define('DB_USER', 'root');
define('DB_PASS', '');          // change to your password

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,   // real prepared statements
        ]
    );
} catch (PDOException $e) {
    // Never expose DB errors to the user!
    error_log($e->getMessage());
    die(json_encode(['success' => false, 'message' => 'Database connection failed.']));
}
?>