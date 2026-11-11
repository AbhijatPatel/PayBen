<?php

// Load .env variables if file exists
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $val) = explode('=', $line, 2);
            $key = trim($key);
            $val = trim($val);
            if (!array_key_exists($key, $_SERVER) && !array_key_exists($key, $_ENV)) {
                putenv("$key=$val");
                $_ENV[$key] = $val;
                $_SERVER[$key] = $val;
            }
        }
    }
}

define('APP_NAME', getenv('APP_NAME') ?: 'PayBen');
define('APP_TAGLINE', 'Smart Recharge & Bill Payment Platform');
define('APP_VERSION', '2.0.0');
define('FRONTEND_URL', getenv('FRONTEND_URL') ?: 'http://localhost:5173');

$isCli = (php_sapi_name() === 'cli');

// CORS Headers for React Frontend (when called via HTTP web server)
if (!$isCli && !headers_sent()) {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    $allowedOrigins = [
        'http://localhost:5173',
        'http://127.0.0.1:5173',
        'http://localhost:3000',
        'http://localhost:8000',
        'https://frontend-3p6j.vercel.app'
    ];

    $frontendEnv = getenv('FRONTEND_URL');
    if ($frontendEnv) {
        $allowedOrigins[] = rtrim($frontendEnv, '/');
    }

    $isVercel = (bool)preg_match('/^https?:\/\/[a-z0-9\-\.]+\.vercel\.app$/i', $origin);

    if (in_array($origin, $allowedOrigins, true) || $isVercel || empty($origin)) {
        header('Access-Control-Allow-Origin: ' . ($origin ?: 'http://localhost:5173'));
    }
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-CSRF-Token');

    // Handle preflight OPTIONS request
    if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }

    // Session configuration
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', '1');
        ini_set('session.use_strict_mode', '1');
        ini_set('session.cookie_samesite', 'Lax');
        session_start();
    }
}

error_reporting(E_ALL);
ini_set('display_errors', '0');
date_default_timezone_set('Asia/Kolkata');

function jsonResponse($data, int $statusCode = 200) {
    if (!headers_sent()) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=UTF-8');
    }
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function sanitize($data): string {
    if (is_array($data)) return '';
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

function generateTransactionID(string $prefix = 'PB'): string {
    return $prefix . date('Ymd') . strtoupper(substr(uniqid(), -6));
}

function formatCurrency($amount): string {
    $num = is_numeric($amount) ? (float)$amount : 0.00;
    return '₹' . number_format($num, 2);
}
