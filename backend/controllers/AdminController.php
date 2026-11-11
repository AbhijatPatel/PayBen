<?php

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/middleware/auth.php';
require_once dirname(__DIR__) . '/models/Admin.php';

class AdminController {
    public static function login(array $data) {
        $username = trim($data['username'] ?? $data['email'] ?? '');
        $password = (string)($data['password'] ?? '');

        if (empty($username) || empty($password)) {
            jsonResponse(['success' => false, 'message' => 'Username/email and password are required.'], 400);
        }

        $admin = Admin::findByUsernameOrEmail($username);
        if (!$admin || !password_verify($password, $admin['password_hash'])) {
            jsonResponse(['success' => false, 'message' => 'Invalid administrator credentials.'], 401);
        }

        if (session_status() === PHP_SESSION_NONE) session_start();
        session_regenerate_id(true);

        $_SESSION['admin_id'] = (int)$admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['admin_role'] = $admin['role'];

        jsonResponse([
            'success' => true,
            'message' => 'Admin authentication successful.',
            'data' => [
                'admin' => [
                    'id' => (int)$admin['id'],
                    'username' => $admin['username'],
                    'email' => $admin['email'],
                    'full_name' => $admin['full_name'],
                    'role' => $admin['role']
                ],
                'session_id' => session_id()
            ]
        ]);
    }

    public static function dashboard() {
        requireAdminAuth();
        $stats = Admin::getStats();
        jsonResponse(['success' => true, 'data' => $stats]);
    }

    public static function users() {
        requireAdminAuth();
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 50)));
        $offset = max(0, (int)($_GET['offset'] ?? 0));
        $users = Admin::getUsers($limit, $offset);
        jsonResponse(['success' => true, 'data' => $users]);
    }

    public static function toggleUserStatus(array $data) {
        requireAdminAuth();
        $userId = (int)($data['user_id'] ?? 0);
        if ($userId <= 0) {
            jsonResponse(['success' => false, 'message' => 'User ID is required.'], 400);
        }
        try {
            $newStatus = Admin::toggleUserStatus($userId);
            jsonResponse([
                'success' => true,
                'message' => "User status updated to {$newStatus}.",
                'data' => ['status' => $newStatus]
            ]);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public static function transactions() {
        requireAdminAuth();
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 50)));
        $offset = max(0, (int)($_GET['offset'] ?? 0));
        $tx = Admin::getAllTransactions($limit, $offset);
        jsonResponse(['success' => true, 'data' => $tx]);
    }
}
