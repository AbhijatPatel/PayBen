<?php

require_once dirname(__DIR__) . '/config/database.php';

class Transaction {
    public static function getUserTransactions(int $userId, ?string $type = null, ?string $status = null, int $limit = 50, int $offset = 0): array {
        $pdo = getDBConnection();
        $sql = "SELECT id, transaction_id, type, mobile_or_consumer_number, operator_or_provider, amount, status, payment_method, description, created_at FROM transactions WHERE user_id = ?";
        $params = [$userId];

        if ($type && $type !== 'all') {
            $sql .= " AND type = ?";
            $params[] = $type;
        }
        if ($status && $status !== 'all') {
            $sql .= " AND status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY created_at DESC, id DESC LIMIT ? OFFSET ?";
        $stmt = $pdo->prepare($sql);
        $idx = 1;
        foreach ($params as $p) {
            $stmt->bindValue($idx++, $p);
        }
        $stmt->bindValue($idx++, $limit, PDO::PARAM_INT);
        $stmt->bindValue($idx++, $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function getDetails(int $userId, string $transactionId): ?array {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM transactions WHERE user_id = ? AND transaction_id = ? LIMIT 1");
        $stmt->execute([$userId, $transactionId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function getUserStats(int $userId): array {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) AS total_count,
                COALESCE(SUM(CASE WHEN status = 'SUCCESS' THEN 1 ELSE 0 END), 0) AS success_count,
                COALESCE(SUM(CASE WHEN status = 'SUCCESS' THEN amount ELSE 0 END), 0.00) AS total_spent
            FROM transactions 
            WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        $stats = $stmt->fetch();
        return [
            'total_transactions' => (int)($stats['total_count'] ?? 0),
            'successful_transactions' => (int)($stats['success_count'] ?? 0),
            'total_spent' => (float)($stats['total_spent'] ?? 0.00)
        ];
    }
}
