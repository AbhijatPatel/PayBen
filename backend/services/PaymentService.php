<?php

require_once dirname(__DIR__) . '/config/database.php';
require_once __DIR__ . '/WalletService.php';

class PaymentService {
    /**
     * Create a new payment order for sandbox checkout
     */
    public static function createOrder(int $userId, float $amountRupees, string $notes = 'Wallet Top-up'): array {
        $amountPaise = toPaise($amountRupees);
        if ($amountPaise < 100 || $amountPaise > 10000000) {
            throw new InvalidArgumentException("Order amount must be between ₹1.00 and ₹1,00,000.00.");
        }

        $orderId = 'ORDER_' . date('YmdHis') . '_' . strtoupper(substr(uniqid(), -4));
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            INSERT INTO payment_orders (order_id, user_id, amount_paise, currency, provider, status, notes, created_at)
            VALUES (?, ?, ?, 'INR', ?, 'PENDING', ?, NOW())
        ");
        $stmt->execute([$orderId, $userId, $amountPaise, PAYMENT_PROVIDER, $notes]);

        return [
            'order_id' => $orderId,
            'amount' => $amountRupees,
            'amount_paise' => $amountPaise,
            'currency' => 'INR',
            'provider' => PAYMENT_PROVIDER,
            'status' => 'PENDING',
            'notes' => $notes,
            'sandbox_mode' => true
        ];
    }

    /**
     * Verify and settle simulated payment checkout
     */
    public static function verifyPayment(
        int $userId,
        string $orderId,
        string $action = 'SUCCESS', // 'SUCCESS', 'FAILED', 'CANCELLED'
        ?string $paymentId = null,
        ?string $signature = null
    ): array {
        $action = strtoupper(trim($action));
        $pdo = getDBConnection();

        $stmt = $pdo->prepare("SELECT * FROM payment_orders WHERE order_id = ? AND user_id = ? FOR UPDATE");
        $stmt->execute([$orderId, $userId]);
        $order = $stmt->fetch();

        if (!$order) {
            throw new RuntimeException("Payment order not found.");
        }

        if ($order['status'] !== 'PENDING') {
            return [
                'order_id' => $orderId,
                'status' => $order['status'],
                'already_processed' => true
            ];
        }

        $paymentId = $paymentId ?: ('PAY_' . date('YmdHis') . '_' . strtoupper(substr(uniqid(), -6)));
        $signature = $signature ?: hash_hmac('sha256', "{$orderId}|{$paymentId}", APP_SECRET);

        if ($action === 'SUCCESS') {
            $amountRupees = toRupees((int)$order['amount_paise']);
            
            // Credit wallet atomically
            $walletResult = WalletService::addFunds(
                $userId,
                $amountRupees,
                'PayBen Sandbox Gateway',
                "order_{$orderId}"
            );

            // Update order status
            $stmtUp = $pdo->prepare("
                UPDATE payment_orders 
                SET status = 'SUCCESS', payment_id = ?, signature = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            $stmtUp->execute([$paymentId, $signature, (int)$order['id']]);

            return [
                'success' => true,
                'order_id' => $orderId,
                'payment_id' => $paymentId,
                'status' => 'SUCCESS',
                'amount' => $amountRupees,
                'new_balance' => $walletResult['new_balance'],
                'formatted_balance' => $walletResult['formatted_balance'],
                'message' => 'Simulated checkout completed successfully. Wallet updated!'
            ];
        } else {
            $newStatus = ($action === 'CANCELLED') ? 'CANCELLED' : 'FAILED';
            $stmtUp = $pdo->prepare("
                UPDATE payment_orders 
                SET status = ?, payment_id = ?, signature = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            $stmtUp->execute([$newStatus, $paymentId, $signature, (int)$order['id']]);

            return [
                'success' => false,
                'order_id' => $orderId,
                'payment_id' => $paymentId,
                'status' => $newStatus,
                'message' => "Payment simulated as {$newStatus}."
            ];
        }
    }

    public static function getOrderStatus(string $orderId): array {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM payment_orders WHERE order_id = ? LIMIT 1");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();
        if (!$order) {
            throw new RuntimeException("Order not found.");
        }
        return [
            'order_id' => $order['order_id'],
            'amount' => toRupees((int)$order['amount_paise']),
            'status' => $order['status'],
            'payment_id' => $order['payment_id'],
            'created_at' => $order['created_at']
        ];
    }
}
