<?php
// Admin logout

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';

// Unset all administrative session variables
unset(
    $_SESSION['admin_id'],
    $_SESSION['admin_name'],
    $_SESSION['admin_username'],
    $_SESSION['admin_email'],
    $_SESSION['admin_role']
);

// If no standard customer user session exists either, clear and regenerate session
if (!isset($_SESSION['user_id'])) {
    session_regenerate_id(true);
}

if (function_exists('setFlashMessage')) {
    setFlashMessage('info', 'You have been successfully signed out of the Administrator Console.');
}

// Redirect back to Admin Login
header('Location: ' . APP_URL . '/admin/login.php');
exit;
