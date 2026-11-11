<?php

require_once dirname(__DIR__) . '/config/database.php';

function getAuthUser(): ?array {
    // Check PHP session first
    $userId = $_SESSION['user_id'] ?? null;

    // Also support Bearer token (base64 userId:hash or session ID)
    if (!$userId) {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        if (preg_match('/Bearer\s+(\S+)/i', $authHeader, $matches)) {
            $token = $matches[1];
            // Decode payload or check session
            $parts = explode(':', base64_decode($token));
            if (count($parts) === 2 && is_numeric($parts[0])) {
                $userId = (int)$parts[0];
            }
        }
    }

    if (!$userId) {
        return null;
    }

    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            SELECT u.id, u.full_name, u.email, u.mobile, u.avatar, u.status,
                   COALESCE(w.balance, 0.00) AS wallet_balance
            FROM users u
            LEFT JOIN wallets w ON u.id = w.user_id
            WHERE u.id = ? AND u.status = 'active'
            LIMIT 1
        ");
        $stmt->execute([(int)$userId]);
        $user = $stmt->fetch();
        return $user ?: null;
    } catch (PDOException $e) {
        error_log("Auth verification error: " . $e->getMessage());
        return null;
    }
}

function requireAuth(): array {
    $user = getAuthUser();
    if (!$user) {
        jsonResponse([
            'success' => false,
            'message' => 'Unauthorized. Please sign in to continue.'
        ], 401);
    }
    return $user;
}

function getAuthAdmin(): ?array {
    $adminId = $_SESSION['admin_id'] ?? null;
    if (!$adminId) {
        return null;
    }

    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT id, username, email, full_name, role, status FROM admins WHERE id = ? LIMIT 1");
        $stmt->execute([(int)$adminId]);
        $admin = $stmt->fetch();
        return $admin ?: null;
    } catch (PDOException $e) {
        return null;
    }
}

function requireAdminAuth(): array {
    $admin = getAuthAdmin();
    if (!$admin) {
        jsonResponse([
            'success' => false,
            'message' => 'Administrator privileges required.'
        ], 403);
    }
    return $admin;
}
