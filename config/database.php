<?php
// DB connection

define('DB_HOST', '127.0.0.1');
define('DB_PORT', 3307); // change to 3306 for default MySQL / XAMPP
define('DB_NAME', 'payben_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

function getDBConnection(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET . " COLLATE " . DB_CHARSET . "_unicode_ci"
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=UTF-8');
                http_response_code(500);
            }
            die(json_encode([
                'success' => false,
                'message' => 'Database connection failed. Please verify database configuration and ensure MySQL is running.'
            ]));
        }
    }
    return $pdo;
}
