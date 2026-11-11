<?php

require_once dirname(__DIR__) . '/config/database.php';

class User {
    public static function findById(int $id): ?array {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            SELECT u.id, u.full_name, u.email, u.mobile, u.avatar, u.status, u.created_at,
                   COALESCE(w.balance, 0.00) AS wallet_balance
            FROM users u
            LEFT JOIN wallets w ON u.id = w.user_id
            WHERE u.id = ? LIMIT 1
        ");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function findByEmail(string $email): ?array {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            SELECT u.*, COALESCE(w.balance, 0.00) AS wallet_balance
            FROM users u
            LEFT JOIN wallets w ON u.id = w.user_id
            WHERE u.email = ? LIMIT 1
        ");
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function findByMobileOrEmail(string $identifier): ?array {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            SELECT u.id, u.full_name, u.email, u.mobile, u.avatar, u.status,
                   COALESCE(w.balance, 0.00) AS wallet_balance
            FROM users u
            LEFT JOIN wallets w ON u.id = w.user_id
            WHERE u.mobile = ? OR u.email = ? LIMIT 1
        ");
        $stmt->execute([$identifier, $identifier]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(string $fullName, string $email, string $mobile, string $password): int {
        $pdo = getDBConnection();
        $pdo->beginTransaction();
        try {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("
                INSERT INTO users (full_name, email, mobile, password_hash, status, created_at)
                VALUES (?, ?, ?, ?, 'active', NOW())
            ");
            $stmt->execute([$fullName, $email, $mobile, $hash]);
            $userId = (int)$pdo->lastInsertId();

            // Create initial wallet with ₹0.00 (or initial welcome bonus)
            $stmtW = $pdo->prepare("
                INSERT INTO wallets (user_id, balance, created_at)
                VALUES (?, 500.00, NOW())
            ");
            $stmtW->execute([$userId]);
            $walletId = (int)$pdo->lastInsertId();

            // Log welcome credit
            $stmtTx = $pdo->prepare("
                INSERT INTO wallet_transactions (user_id, wallet_id, type, amount, description, reference_id, balance_after, created_at)
                VALUES (?, ?, 'credit', 500.00, 'Welcome Sign-up Bonus', ?, 500.00, NOW())
            ");
            $stmtTx->execute([$userId, $walletId, generateTransactionID('WB')]);

            $pdo->commit();
            return $userId;
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function updateProfile(int $id, string $fullName, string $mobile): bool {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, mobile = ?, updated_at = NOW() WHERE id = ?");
        return $stmt->execute([$fullName, $mobile, $id]);
    }

    public static function updatePassword(int $id, string $newPassword): bool {
        $pdo = getDBConnection();
        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?");
        return $stmt->execute([$hash, $id]);
    }

    public static function getNetworkContacts(int $excludeUserId, int $limit = 6): array {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            SELECT id, full_name, mobile, email, avatar
            FROM users
            WHERE id != ? AND status = 'active'
            ORDER BY id ASC LIMIT ?
        ");
        $stmt->bindValue(1, $excludeUserId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}

