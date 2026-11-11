<?php

require_once __DIR__ . '/config.php';

function getDBConnection(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $host = getenv('DB_HOST') ?: '127.0.0.1';
        $port = getenv('DB_PORT') ?: '3307';
        $dbname = getenv('DB_NAME') ?: 'payben_db';
        $user = getenv('DB_USER') ?: 'root';
        $pass = getenv('DB_PASS') ?: '';
        $charset = getenv('DB_CHARSET') ?: 'utf8mb4';

        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset} COLLATE {$charset}_unicode_ci"
        ];

        try {
            $pdo = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            // Attempt fallback to default port 3306 if 3307 failed
            if ($port === '3307') {
                try {
                    $dsnFallback = "mysql:host={$host};port=3306;dbname={$dbname};charset={$charset}";
                    $pdo = new PDO($dsnFallback, $user, $pass, $options);
                    return $pdo;
                } catch (PDOException $e2) {
                    // Ignore fallback error
                }
            }

            error_log("Database connection error: " . $e->getMessage());
            jsonResponse([
                'success' => false,
                'message' => 'Database connection failed. Please ensure MySQL is running.'
            ], 500);
        }
    }
    return $pdo;
}
