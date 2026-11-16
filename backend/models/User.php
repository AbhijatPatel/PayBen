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

    public static function findByGoogleId(string $googleId): ?array {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            SELECT u.*, COALESCE(w.balance, 0.00) AS wallet_balance
            FROM users u
            LEFT JOIN wallets w ON u.id = w.user_id
            WHERE u.google_id = ? LIMIT 1
        ");
        $stmt->execute([$googleId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function linkGoogleId(int $userId, string $googleId, ?string $avatar = null): bool {
        $pdo = getDBConnection();
        if ($avatar) {
            $stmt = $pdo->prepare("UPDATE users SET google_id = ?, avatar = COALESCE(avatar, ?), updated_at = NOW() WHERE id = ?");
            return $stmt->execute([$googleId, $avatar, $userId]);
        }
        $stmt = $pdo->prepare("UPDATE users SET google_id = ?, updated_at = NOW() WHERE id = ?");
        return $stmt->execute([$googleId, $userId]);
    }

    public static function createGoogleUser(string $fullName, string $email, string $googleId, ?string $avatar = null): int {
        $pdo = getDBConnection();
        $pdo->beginTransaction();
        try {
            // Generate a random high-entropy dummy password hash for OAuth account
            $randomPassword = bin2hex(random_bytes(16));
            $hash = password_hash($randomPassword, PASSWORD_BCRYPT);
            
            // Generate random 10-digit mobile starting with 9 if not available
            $mobile = '9' . str_pad((string)mt_rand(100000000, 999999999), 9, '0', STR_PAD_LEFT);

            $stmt = $pdo->prepare("
                INSERT INTO users (full_name, email, mobile, password_hash, google_id, avatar, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, 'active', NOW())
            ");
            $stmt->execute([$fullName, $email, $mobile, $hash, $googleId, $avatar]);
            $userId = (int)$pdo->lastInsertId();

            // Create initial wallet with ₹500.00 welcome bonus
            $stmtW = $pdo->prepare("
                INSERT INTO wallets (user_id, balance, balance_paise, created_at)
                VALUES (?, 500.00, 50000, NOW())
            ");
            $stmtW->execute([$userId]);
            $walletId = (int)$pdo->lastInsertId();

            // Log welcome credit in wallet_transactions and wallet_ledger
            $stmtTx = $pdo->prepare("
                INSERT INTO wallet_transactions (user_id, wallet_id, type, amount, description, reference_id, balance_after, created_at)
                VALUES (?, ?, 'credit', 500.00, 'Welcome Sign-up Bonus (Google Auth)', ?, 500.00, NOW())
            ");
            $stmtTx->execute([$userId, $walletId, generateTransactionID('GWB')]);

            try {
                $stmtLedger = $pdo->prepare("
                    INSERT INTO wallet_ledger (user_id, wallet_id, type, amount_paise, balance_after_paise, reference_type, description)
                    VALUES (?, ?, 'CREDIT', 50000, 50000, 'SIGNUP_BONUS', 'Google Welcome Bonus ₹500.00')
                ");
                $stmtLedger->execute([$userId, $walletId]);
            } catch (\Throwable $eLedger) {
                // Ignore if ledger table structure differs
            }

            $pdo->commit();
            return $userId;
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
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

