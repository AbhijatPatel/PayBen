<?php

require_once dirname(__DIR__) . '/config/database.php';

class LedgerService {
    /**
     * Record an immutable double-entry ledger event
     */
    public static function record(
        int $walletId,
        int $userId,
        string $transactionId,
        string $entryType,
        string $category,
        int $amountPaise,
        int $balanceAfterPaise,
        string $description,
        ?int $counterpartyId = null,
        ?string $counterpartyVpa = null,
        ?string $idempotencyKey = null
    ): int {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            INSERT INTO wallet_ledger (
                transaction_id, wallet_id, user_id, entry_type, category,
                amount_paise, balance_after_paise, counterparty_id, counterparty_vpa,
                idempotency_key, description, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $transactionId,
            $walletId,
            $userId,
            strtoupper($entryType),
            strtoupper($category),
            $amountPaise,
            $balanceAfterPaise,
            $counterpartyId,
            $counterpartyVpa,
            $idempotencyKey,
            $description
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function checkIdempotency(?string $key): bool {
        if (!$key) return false;
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT id FROM wallet_ledger WHERE idempotency_key = ? LIMIT 1");
        $stmt->execute([$key]);
        return (bool)$stmt->fetch();
    }

    public static function getStatement(int $userId, int $limit = 50, int $offset = 0): array {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            SELECT id, transaction_id, entry_type, category, amount_paise,
                   balance_after_paise, description, counterparty_vpa, created_at
            FROM wallet_ledger
            WHERE user_id = ?
            ORDER BY id DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->bindValue(3, $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        return array_map(function($r) {
            $amountRupees = toRupees((int)$r['amount_paise']);
            $balanceRupees = toRupees((int)$r['balance_after_paise']);
            return [
                'id' => (int)$r['id'],
                'transaction_id' => $r['transaction_id'],
                'entry_type' => $r['entry_type'],
                'category' => $r['category'],
                'amount' => $amountRupees,
                'formatted_amount' => formatCurrency($amountRupees),
                'balance_after' => $balanceRupees,
                'formatted_balance' => formatCurrency($balanceRupees),
                'description' => $r['description'],
                'counterparty_vpa' => $r['counterparty_vpa'],
                'created_at' => $r['created_at']
            ];
        }, $rows);
    }
}
