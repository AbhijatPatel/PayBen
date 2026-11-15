<?php
// router.php - Lightweight production router for PHP server

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$file = __DIR__ . $uri;

// 1. If file exists directly (e.g. .php, .css, .json, .png), let PHP built-in server handle it
if ($uri !== '/' && file_exists($file) && !is_dir($file)) {
    return false;
}

// 2. If URI without extension matches a .php file, execute it
if (file_exists($file . '.php')) {
    $_SERVER['SCRIPT_NAME'] = $uri . '.php';
    $_SERVER['PHP_SELF'] = $uri . '.php';
    require $file . '.php';
    return true;
}

// 3. Fallback to index.php for root or routing
if (file_exists(__DIR__ . '/index.php')) {
    $_SERVER['SCRIPT_NAME'] = '/index.php';
    $_SERVER['PHP_SELF'] = '/index.php';
    require __DIR__ . '/index.php';
    return true;
}

return false;
