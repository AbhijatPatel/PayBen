<?php

require_once dirname(__DIR__) . '/config/database.php';

class UtilityBill {
    public static function getProviders(string $type): array {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT id, name, type, code, state FROM utility_providers WHERE type = ? AND status = 'active' ORDER BY name ASC");
        $stmt->execute([$type]);
        return $stmt->fetchAll();
    }

    public static function fetchBill(int $providerId, string $consumerNumber): ?array {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            SELECT b.id, b.consumer_number, b.consumer_name, b.bill_amount, b.due_date, b.bill_date, b.status,
                   p.name AS provider_name, p.type AS provider_type
            FROM sample_bills b
            JOIN utility_providers p ON b.provider_id = p.id
            WHERE b.provider_id = ? AND b.consumer_number = ?
            LIMIT 1
        ");
        $stmt->execute([$providerId, $consumerNumber]);
        $bill = $stmt->fetch();
        if ($bill) return $bill;

        // If not found in sample_bills, generate a realistic simulated bill so user can test any consumer number!
        $stmtP = $pdo->prepare("SELECT name, type FROM utility_providers WHERE id = ?");
        $stmtP->execute([$providerId]);
        $p = $stmtP->fetch();
        if (!$p) return null;

        $names = ['Rahul Sharma', 'Priya Patel', 'Amit Verma', 'Sunita Rao', 'Kavita Singh', 'Rohan Gupta'];
        $simulatedAmount = (float)mt_rand(450, 3200) + 0.50;
        $dueDate = date('Y-m-d', strtotime('+12 days'));
        $billDate = date('Y-m-d', strtotime('-18 days'));

        return [
            'id' => null,
            'consumer_number' => $consumerNumber,
            'consumer_name' => $names[array_rand($names)],
            'bill_amount' => $simulatedAmount,
            'due_date' => $dueDate,
            'bill_date' => $billDate,
            'status' => 'unpaid',
            'provider_name' => $p['name'],
            'provider_type' => $p['type']
        ];
    }

    public static function payBill(int $userId, string $type, int $providerId, string $consumerNumber, float $amount): array {
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

            $stmtP = $pdo->prepare("SELECT name FROM utility_providers WHERE id = ?");
            $stmtP->execute([$providerId]);
            $provider = $stmtP->fetch();
            $providerName = $provider['name'] ?? 'Utility Board';

            $newBalance = (float)$wallet['balance'] - $amount;
            $prefix = strtoupper(substr($type, 0, 2)) . 'B';
            $txId = generateTransactionID($prefix);

            $stmtUp = $pdo->prepare("UPDATE wallets SET balance = ?, updated_at = NOW() WHERE id = ?");
            $stmtUp->execute([$newBalance, $wallet['id']]);

            $desc = ucfirst($type) . " bill for {$consumerNumber} ({$providerName})";
            $stmtLedger = $pdo->prepare("
                INSERT INTO wallet_transactions (user_id, wallet_id, type, amount, description, reference_id, balance_after, created_at)
                VALUES (?, ?, 'debit', ?, ?, ?, ?, NOW())
            ");
            $stmtLedger->execute([$userId, $wallet['id'], $amount, $desc, $txId, $newBalance]);

            $stmtTx = $pdo->prepare("
                INSERT INTO transactions (user_id, transaction_id, type, mobile_or_consumer_number, operator_or_provider, amount, status, payment_method, description, created_at)
                VALUES (?, ?, ?, ?, ?, ?, 'SUCCESS', 'Wallet', ?, NOW())
            ");
            $stmtTx->execute([$userId, $txId, $type, $consumerNumber, $providerName, $amount, $desc]);

            // Mark sample bill as paid if exists
            $stmtMark = $pdo->prepare("UPDATE sample_bills SET status = 'paid' WHERE provider_id = ? AND consumer_number = ?");
            $stmtMark->execute([$providerId, $consumerNumber]);

            $pdo->commit();
            return [
                'transaction_id' => $txId,
                'consumer_number' => $consumerNumber,
                'provider' => $providerName,
                'amount' => $amount,
                'new_balance' => $newBalance
            ];
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
