<?php // App config & helpers

define('APP_NAME', 'PayBen');
define('APP_TAGLINE', 'Recharge & Bill Payment Platform');
define('APP_VERSION', '1.0.0');

if (!defined('APP_URL')) {
    if (isset($_SERVER['HTTP_HOST'])) {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'];
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        if (str_starts_with($uri, '/PayBen') || str_starts_with($scriptName, '/PayBen')) {
            define('APP_URL', $protocol . $host . '/PayBen');
        } else {
            define('APP_URL', $protocol . $host);
        }
    } else {
        define('APP_URL', 'http://localhost:8000');
    }
}
define('APP_ROOT', dirname(__DIR__));

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_samesite', 'Strict');
    session_start();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

date_default_timezone_set('Asia/Kolkata');

function generateCSRFToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken(?string $token): bool {
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function sanitize($data): string {
    if (is_array($data)) {
        return '';
    }
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

function generateTransactionID(string $prefix = 'PB'): string {
    return $prefix . date('Ymd') . strtoupper(substr(uniqid(), -6));
}

function formatCurrency($amount): string {
    $num = is_numeric($amount) ? (float)$amount : 0.00;
    return '₹' . number_format($num, 2);
}

function setFlashMessage(string $type, string $message): void {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

function getFlashMessage(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function jsonResponse($data, int $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
