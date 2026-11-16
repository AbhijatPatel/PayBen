<?php

require_once dirname(__DIR__) . '/config/database.php';
require_once __DIR__ . '/WalletService.php';
require_once __DIR__ . '/NotificationService.php';

class BillPaymentService {
    public static function getCategories(): array {
        return [
            ['id' => 'electricity', 'name' => 'Electricity Bill', 'icon' => 'Zap', 'bbps' => true],
            ['id' => 'water', 'name' => 'Water Municipal Utility', 'icon' => 'Droplet', 'bbps' => true],
            ['id' => 'gas', 'name' => 'Piped Gas & Cylinder', 'icon' => 'Flame', 'bbps' => true],
            ['id' => 'broadband', 'name' => 'Broadband & Fiber', 'icon' => 'Wifi', 'bbps' => true],
            ['id' => 'landline', 'name' => 'Landline Postpaid', 'icon' => 'Phone', 'bbps' => true]
        ];
    }

    public static function getProviders(string $type = 'electricity'): array {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            SELECT id, name, type, code, state 
            FROM utility_providers 
            WHERE type = ? AND status = 'active' 
            ORDER BY state ASC, name ASC
        ");
        $stmt->execute([$type]);
        $rows = $stmt->fetchAll();

        // Fallback mock providers if empty for this type
        if (empty($rows)) {
            $fallbacks = [
                'electricity' => [
                    ['id' => 1, 'name' => 'Tata Power Delhi Distribution', 'type' => 'electricity', 'code' => 'TPDDL', 'state' => 'Delhi'],
                    ['id' => 2, 'name' => 'BSES Rajdhani Power Limited', 'type' => 'electricity', 'code' => 'BRPL', 'state' => 'Delhi'],
                    ['id' => 3, 'name' => 'Adani Electricity Mumbai', 'type' => 'electricity', 'code' => 'AEML', 'state' => 'Maharashtra'],
                    ['id' => 4, 'name' => 'Torrent Power', 'type' => 'electricity', 'code' => 'TORRENT', 'state' => 'Gujarat']
                ],
                'water' => [
                    ['id' => 10, 'name' => 'Delhi Jal Board (DJB)', 'type' => 'water', 'code' => 'DJB', 'state' => 'Delhi'],
                    ['id' => 11, 'name' => 'Municipal Corporation of Greater Mumbai', 'type' => 'water', 'code' => 'MCGM', 'state' => 'Maharashtra']
                ],
                'gas' => [
                    ['id' => 20, 'name' => 'Indraprastha Gas Limited (IGL)', 'type' => 'gas', 'code' => 'IGL', 'state' => 'Delhi'],
                    ['id' => 21, 'name' => 'Mahanagar Gas Limited (MGL)', 'type' => 'gas', 'code' => 'MGL', 'state' => 'Maharashtra'],
                    ['id' => 22, 'name' => 'Gujarat Gas Limited', 'type' => 'gas', 'code' => 'GGL', 'state' => 'Gujarat']
                ]
            ];
            return $fallbacks[$type] ?? [];
        }

        return $rows;
    }

    public static function fetchBill(string $type, int $providerId, string $consumerNumber): array {
        $pdo = getDBConnection();

        // 1. Check existing sample_bills in DB
        $stmt = $pdo->prepare("
            SELECT sb.*, up.name AS provider_name 
            FROM sample_bills sb
            JOIN utility_providers up ON sb.provider_id = up.id
            WHERE sb.consumer_number = ? AND up.type = ?
            LIMIT 1
        ");
        $stmt->execute([$consumerNumber, $type]);
        $bill = $stmt->fetch();

        if ($bill) {
            return [
                'found' => true,
                'bill_id' => (int)$bill['id'],
                'consumer_number' => $bill['consumer_number'],
                'consumer_name' => $bill['consumer_name'],
                'provider_name' => $bill['provider_name'],
                'bill_amount' => (float)$bill['bill_amount'],
                'formatted_amount' => formatCurrency((float)$bill['bill_amount']),
                'due_date' => $bill['due_date'],
                'bill_date' => $bill['bill_date'],
                'status' => $bill['status'],
                'is_paid' => $bill['status'] === 'paid'
            ];
        }

        // 2. Realistic Dynamic Sandbox Bill generation for any test consumer ID
        // Ensures user test inputs always successfully return a bill
        $stmtP = $pdo->prepare("SELECT name FROM utility_providers WHERE id = ? LIMIT 1");
        $stmtP->execute([$providerId]);
        $provRow = $stmtP->fetch();
        $providerName = $provRow ? $provRow['name'] : 'Utility Board';

        $generatedAmount = 450.00 + ((crc32($consumerNumber) % 1800) / 1.0);
        $generatedAmount = max(180.00, round($generatedAmount, 2));

        return [
            'found' => true,
            'bill_id' => null,
            'consumer_number' => $consumerNumber,
            'consumer_name' => 'Abhijat Patel',
            'provider_name' => $providerName,
            'bill_amount' => $generatedAmount,
            'formatted_amount' => formatCurrency($generatedAmount),
            'due_date' => date('Y-m-d', strtotime('+14 days')),
            'bill_date' => date('Y-m-d', strtotime('-16 days')),
            'status' => 'unpaid',
            'is_paid' => false,
            'sandbox_generated' => true
        ];
    }

    public static function payBill(int $userId, string $type, int $providerId, string $consumerNumber, float $amount): array {
        $pdo = getDBConnection();

        $stmtP = $pdo->prepare("SELECT name FROM utility_providers WHERE id = ? LIMIT 1");
        $stmtP->execute([$providerId]);
        $prov = $stmtP->fetch();
        $providerName = $prov ? $prov['name'] : 'Utility Provider';

        // 1. Debit wallet atomically
        $debitResult = WalletService::debitForService(
            $userId,
            $amount,
            strtoupper($type),
            "{$providerName} Bill Payment for Consumer #{$consumerNumber}"
        );

        $txId = $debitResult['transaction_id'];

        // 2. Mark sample bill as paid if existed
        $stmtMark = $pdo->prepare("UPDATE sample_bills SET status = 'paid' WHERE consumer_number = ?");
        $stmtMark->execute([$consumerNumber]);

        // 3. Log unified transaction
        $stmtTx = $pdo->prepare("
            INSERT INTO transactions (
                user_id, transaction_id, type, mobile_or_consumer_number, operator_or_provider, amount, status, payment_method, description, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, 'SUCCESS', 'Wallet', ?, NOW())
        ");
        $stmtTx->execute([
            $userId,
            $txId,
            $type,
            $consumerNumber,
            $providerName,
            $amount,
            "{$providerName} Bill Payment"
        ]);

        // 4. Notification
        NotificationService::create(
            $userId,
            "Bill Paid Successfully ✓",
            "Payment of " . formatCurrency($amount) . " to {$providerName} settled via Bharat BillPay (BBPS).",
            "success",
            ['transaction_id' => $txId, 'consumer_number' => $consumerNumber]
        );

        return [
            'transaction_id' => $txId,
            'provider' => $providerName,
            'consumer_number' => $consumerNumber,
            'amount' => $amount,
            'status' => 'SUCCESS',
            'new_balance' => $debitResult['new_balance'],
            'formatted_balance' => $debitResult['formatted_balance'],
            'created_at' => date('Y-m-d H:i:s'),
            'bbps_reference' => 'BBPS' . date('Ymd') . strtoupper(substr(uniqid(), -6))
        ];
    }
}
