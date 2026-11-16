<?php

require_once dirname(__DIR__) . '/config/database.php';
require_once __DIR__ . '/WalletService.php';
require_once __DIR__ . '/NotificationService.php';
require_once __DIR__ . '/EmailService.php';

class RechargeService {
    public static function getOperators(string $type = 'mobile'): array {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT id, name, type, code, logo FROM operators WHERE type = ? AND status = 'active' ORDER BY name ASC");
        $stmt->execute([$type]);
        return $stmt->fetchAll();
    }

    public static function getPlans(int $operatorId, ?string $planType = null): array {
        $pdo = getDBConnection();
        $sql = "SELECT id, plan_name, amount, validity, data_limit, description, plan_type FROM recharge_plans WHERE operator_id = ? AND status = 'active'";
        $params = [$operatorId];

        if ($planType) {
            $sql .= " AND plan_type = ?";
            $params[] = $planType;
        }
        $sql .= " ORDER BY amount ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        return array_map(function($p) {
            return [
                'id' => (int)$p['id'],
                'plan_name' => $p['plan_name'],
                'amount' => (float)$p['amount'],
                'formatted_amount' => formatCurrency((float)$p['amount']),
                'validity' => $p['validity'],
                'data_limit' => $p['data_limit'],
                'description' => $p['description'],
                'plan_type' => $p['plan_type']
            ];
        }, $rows);
    }

    public static function executeRecharge(
        int $userId,
        string $serviceType, // 'mobile_recharge' or 'dth_recharge'
        string $number,
        int $operatorId,
        float $amount,
        ?int $planId = null
    ): array {
        $pdo = getDBConnection();

        // 1. Validate operator
        $stmtOp = $pdo->prepare("SELECT name, code FROM operators WHERE id = ? LIMIT 1");
        $stmtOp->execute([$operatorId]);
        $operator = $stmtOp->fetch();
        if (!$operator) {
            throw new RuntimeException("Selected operator is not available.");
        }

        // 2. Debit wallet atomically
        $debitResult = WalletService::debitForService(
            $userId,
            $amount,
            ($serviceType === 'mobile_recharge' ? 'RECHARGE' : 'DTH'),
            "{$operator['name']} recharge for {$number}"
        );

        $txId = $debitResult['transaction_id'];

        // 3. Log transaction
        $stmtTx = $pdo->prepare("
            INSERT INTO transactions (
                user_id, transaction_id, type, mobile_or_consumer_number, operator_or_provider, amount, status, payment_method, description, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, 'SUCCESS', 'Wallet', ?, NOW())
        ");
        $stmtTx->execute([
            $userId,
            $txId,
            $serviceType,
            $number,
            $operator['name'],
            $amount,
            "{$operator['name']} Recharge of " . formatCurrency($amount)
        ]);

        // 4. In-app notification
        NotificationService::create(
            $userId,
            "Recharge Successful ✓",
            "Your {$operator['name']} recharge of " . formatCurrency($amount) . " for {$number} is successful.",
            "success",
            ['transaction_id' => $txId, 'number' => $number]
        );

        // 5. User receipt details
        return [
            'transaction_id' => $txId,
            'operator' => $operator['name'],
            'number' => $number,
            'amount' => $amount,
            'status' => 'SUCCESS',
            'new_balance' => $debitResult['new_balance'],
            'formatted_balance' => $debitResult['formatted_balance'],
            'created_at' => date('Y-m-d H:i:s'),
            'sandbox_provider' => 'PayBen Mock Gateway'
        ];
    }
}
