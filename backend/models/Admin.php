<?php

require_once dirname(__DIR__) . '/config/database.php';

class Admin {
    public static function findByUsernameOrEmail(string $identifier): ?array {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ? OR email = ? LIMIT 1");
        $stmt->execute([$identifier, $identifier]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function getStats(): array {
        $pdo = getDBConnection();

        $usersCount = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $totalVol = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE status = 'SUCCESS'")->fetchColumn();
        $txCount = (int)$pdo->query("SELECT COUNT(*) FROM transactions")->fetchColumn();
        $walletHolding = (float)$pdo->query("SELECT COALESCE(SUM(balance), 0) FROM wallets")->fetchColumn();

        $recentTx = $pdo->query("
            SELECT t.*, u.full_name, u.email
            FROM transactions t
            LEFT JOIN users u ON t.user_id = u.id
            ORDER BY t.created_at DESC, t.id DESC LIMIT 10
        ")->fetchAll();

        return [
            'total_users' => $usersCount,
            'total_volume' => $totalVol,
            'total_transactions' => $txCount,
            'wallet_holdings' => $walletHolding,
            'recent_transactions' => $recentTx
        ];
    }

    public static function getUsers(int $limit = 50, int $offset = 0): array {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            SELECT u.id, u.full_name, u.email, u.mobile, u.status, u.created_at,
                   COALESCE(w.balance, 0.00) AS wallet_balance,
                   (SELECT COUNT(*) FROM transactions WHERE user_id = u.id) AS tx_count
            FROM users u
            LEFT JOIN wallets w ON u.id = w.user_id
            ORDER BY u.id DESC LIMIT ? OFFSET ?
        ");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function toggleUserStatus(int $userId): string {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT status FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        if (!$user) throw new RuntimeException("User not found.");

        $newStatus = ($user['status'] === 'active') ? 'suspended' : 'active';
        $stmtUp = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
        $stmtUp->execute([$newStatus, $userId]);
        return $newStatus;
    }

    public static function getAllTransactions(int $limit = 50, int $offset = 0): array {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            SELECT t.*, u.full_name, u.email, u.mobile
            FROM transactions t
            LEFT JOIN users u ON t.user_id = u.id
            ORDER BY t.created_at DESC, t.id DESC LIMIT ? OFFSET ?
        ");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}

