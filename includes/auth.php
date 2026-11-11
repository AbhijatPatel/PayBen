<?php
// Auth helpers

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['admin_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        if (function_exists('setFlashMessage')) {
            setFlashMessage('warning', 'Please sign in to access this page.');
        }
        header('Location: ' . APP_URL . '/login.php');
        exit;
    }
}

function requireAdmin() {
    if (!isAdmin()) {
        if (function_exists('setFlashMessage')) {
            setFlashMessage('danger', 'Administrator privileges required.');
        }
        header('Location: ' . APP_URL . '/admin/login.php');
        exit;
    }
}

function getCurrentUser() {
    if (!isLoggedIn()) return null;
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT u.*, w.balance as wallet_balance FROM users u LEFT JOIN wallets w ON u.id = w.user_id WHERE u.id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        return $user ?: null;
    } catch (PDOException $e) {
        error_log("getCurrentUser error: " . $e->getMessage());
        return null;
    }
}

function getCurrentAdmin() {
    if (!isAdmin()) return null;
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
        $stmt->execute([$_SESSION['admin_id']]);
        $admin = $stmt->fetch();
        return $admin ?: null;
    } catch (PDOException $e) {
        error_log("getCurrentAdmin error: " . $e->getMessage());
        return null;
    }
}
