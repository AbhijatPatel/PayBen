<?php
// Logout

require_once __DIR__ . '/config/config.php';

// Ensure session is started before manipulating it
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Unset all session keys
$_SESSION = [];

// Delete PHP session cookie from browser
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy session on server
session_destroy();

// Start a fresh, clean session exclusively to carry the flash message
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (function_exists('setFlashMessage')) {
    setFlashMessage('info', 'You have been successfully logged out. See you soon!');
}

// Redirect back to login page
header('Location: ' . APP_URL . '/login.php');
exit;
