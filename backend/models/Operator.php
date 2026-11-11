<?php

require_once dirname(__DIR__) . '/config/database.php';

class Operator {
    public static function getAll(string $type = 'mobile'): array {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT id, name, type, code, logo FROM operators WHERE type = ? AND status = 'active' ORDER BY name ASC");
        $stmt->execute([$type]);
        return $stmt->fetchAll();
    }

    public static function getPlans(int $operatorId, ?string $planType = null): array {
        $pdo = getDBConnection();
        $sql = "SELECT id, operator_id, plan_name, amount, validity, data_limit, description, plan_type FROM recharge_plans WHERE operator_id = ? AND status = 'active'";
        $params = [$operatorId];
        if ($planType) {
            $sql .= " AND plan_type = ?";
            $params[] = $planType;
        }
        $sql .= " ORDER BY amount ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function recharge(int $userId, string $type, string $targetNumber, int $operatorId, float $amount, ?int $planId = null): array {
        $pdo = getDBConnection();
        $pdo->beginTransaction();
        try {
            $stmtW = $pdo->prepare("SELECT id, balance FROM wallets WHERE user_id = ? FOR UPDATE");
            $stmtW->execute([$userId]);
            $wallet = $stmtW->fetch();
            if (!$wallet) throw new RuntimeException("Wallet not found.");
            if ((float)$wallet['balance'] < $amount) {
                throw new RuntimeException("Insufficient wallet balance. Please add funds.");
            }

            // Get operator name
            $stmtOp = $pdo->prepare("SELECT name FROM operators WHERE id = ?");
            $stmtOp->execute([$operatorId]);
            $op = $stmtOp->fetch();
            $opName = $op['name'] ?? 'Telecom';

            $newBalance = (float)$wallet['balance'] - $amount;
            $prefix = ($type === 'mobile_recharge') ? 'MR' : 'DTH';
            $txId = generateTransactionID($prefix);

            // Deduct wallet
            $stmtUp = $pdo->prepare("UPDATE wallets SET balance = ?, updated_at = NOW() WHERE id = ?");
            $stmtUp->execute([$newBalance, $wallet['id']]);

            // Ledger
            $desc = ucfirst(str_replace('_', ' ', $type)) . " for {$targetNumber} ({$opName})";
            $stmtLedger = $pdo->prepare("
                INSERT INTO wallet_transactions (user_id, wallet_id, type, amount, description, reference_id, balance_after, created_at)
                VALUES (?, ?, 'debit', ?, ?, ?, ?, NOW())
            ");
            $stmtLedger->execute([$userId, $wallet['id'], $amount, $desc, $txId, $newBalance]);

            // Transactions
            $stmtTx = $pdo->prepare("
                INSERT INTO transactions (user_id, transaction_id, type, mobile_or_consumer_number, operator_or_provider, amount, status, payment_method, description, created_at)
                VALUES (?, ?, ?, ?, ?, ?, 'SUCCESS', 'Wallet', ?, NOW())
            ");
            $stmtTx->execute([$userId, $txId, $type, $targetNumber, $opName, $amount, $desc]);

            $pdo->commit();
            return [
                'transaction_id' => $txId,
                'target_number' => $targetNumber,
                'operator' => $opName,
                'amount' => $amount,
                'new_balance' => $newBalance
            ];
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
