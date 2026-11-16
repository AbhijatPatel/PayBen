<?php

require_once __DIR__ . '/config.php';

function getDatabaseConfig(): array {
    $dbUrl = getenv('DATABASE_URL') ?: getenv('MYSQL_URL') ?: '';

    if (!empty($dbUrl)) {
        $parsed = parse_url($dbUrl);
        return [
            'host' => $parsed['host'] ?? '127.0.0.1',
            'port' => (string)($parsed['port'] ?? '3306'),
            'dbname' => ltrim($parsed['path'] ?? 'payben_db', '/'),
            'user' => $parsed['user'] ?? 'root',
            'password' => $parsed['pass'] ?? '',
            'charset' => getenv('DB_CHARSET') ?: 'utf8mb4',
        ];
    }

    $host = getenv('DB_HOST') ?: getenv('MYSQLHOST') ?: '127.0.0.1';
    $port = getenv('DB_PORT') ?: getenv('MYSQLPORT') ?: '3306';
    $dbname = getenv('DB_NAME') ?: getenv('MYSQLDATABASE') ?: 'payben_db';
    $user = getenv('DB_USER') ?: getenv('MYSQLUSER') ?: 'root';
    $password = getenv('DB_PASS');
    if ($password === false || $password === '') {
        $password = getenv('MYSQLPASSWORD') ?: '';
    }

    return [
        'host' => $host,
        'port' => (string)$port,
        'dbname' => $dbname,
        'user' => $user,
        'password' => $password,
        'charset' => getenv('DB_CHARSET') ?: 'utf8mb4',
    ];
}

function getDatabaseConnectionCandidates(): array {
    $config = getDatabaseConfig();
    $hosts = array_values(array_unique([$config['host'], '127.0.0.1', 'localhost', 'db', 'mysql']));
    $ports = array_values(array_unique([$config['port'], '3306', '3307']));
    $users = array_values(array_unique([$config['user'], 'root', 'mysql', 'payben']));
    $passwords = array_values(array_unique([
        $config['password'],
        '',
        getenv('MYSQL_ROOT_PASSWORD') ?: '',
        getenv('MYSQL_PASSWORD') ?: '',
        getenv('DB_PASS') ?: '',
        'root',
        'password',
    ]));
    $dbNames = array_values(array_unique([$config['dbname'], 'payben_db', 'mysql']));

    $candidates = [];
    foreach ($hosts as $host) {
        foreach ($ports as $port) {
            foreach ($users as $user) {
                foreach ($passwords as $password) {
                    foreach ($dbNames as $dbname) {
                        $candidates[] = [
                            'host' => $host,
                            'port' => $port,
                            'dbname' => $dbname,
                            'user' => $user,
                            'password' => $password,
                            'charset' => $config['charset'],
                        ];
                    }
                }
            }
        }
    }

    return $candidates;
}

function getDBConnection(): PDO {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    $lastError = null;
    $sqlPath = dirname(__DIR__) . '/database/payben_db.sql';

    foreach (getDatabaseConnectionCandidates() as $candidate) {
        try {
            $dsn = "mysql:host={$candidate['host']};port={$candidate['port']};dbname={$candidate['dbname']};charset={$candidate['charset']}";
            $pdo = new PDO($dsn, $candidate['user'], $candidate['password'], $options);

            try {
                $pdo->query("SELECT 1 FROM users LIMIT 1");
            } catch (\Throwable $tb) {
                if (file_exists($sqlPath)) {
                    $schemaSql = file_get_contents($sqlPath);
                    $pdo->exec($schemaSql);
                }
            }

            // Auto-apply fintech sandbox migration if wallet_ledger table missing
            try {
                $pdo->query("SELECT 1 FROM wallet_ledger LIMIT 1");
            } catch (\Throwable $tb) {
                $migFile = dirname(__DIR__) . '/database/migrations/001_fintech_sandbox_upgrade.sql';
                if (file_exists($migFile)) {
                    $pdo->exec(file_get_contents($migFile));
                }
            }

            // Auto-apply super app migration if bookings or merchants table missing
            try {
                $pdo->query("SELECT 1 FROM bookings LIMIT 1");
            } catch (\Throwable $tb) {
                $migFile2 = dirname(__DIR__) . '/database/migrations/002_super_app_upgrade.sql';
                if (file_exists($migFile2)) {
                    $pdo->exec(file_get_contents($migFile2));
                }
            }

            return $pdo;
        } catch (PDOException $e) {
            $lastError = $e;
        }
    }

    error_log("Database connection error: " . ($lastError ? $lastError->getMessage() : 'Unknown database error'));
    jsonResponse([
        'success' => false,
        'message' => 'Database connection failed. Please ensure MySQL is running and the configured credentials are valid.'
    ], 500);
}

