<?php

require_once dirname(__DIR__) . '/config/database.php';
require_once __DIR__ . '/LedgerService.php';

class WalletService {
    /**
     * Get wallet balance in paise and rupees
     */
    public static function getBalance(int $userId): array {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT id, balance, balance_paise FROM wallets WHERE user_id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();

        if (!$row) {
            // Auto-create wallet if missing
            $stmtInit = $pdo->prepare("INSERT INTO wallets (user_id, balance, balance_paise, created_at) VALUES (?, 0.00, 0, NOW())");
            $stmtInit->execute([$userId]);
            $walletId = (int)$pdo->lastInsertId();
            $paise = 0;
            $balance = 0.00;
        } else {
            $walletId = (int)$row['id'];
            $paise = isset($row['balance_paise']) && $row['balance_paise'] > 0 
                ? (int)$row['balance_paise'] 
                : toPaise((float)$row['balance']);
            $balance = toRupees($paise);
        }

        return [
            'wallet_id' => $walletId,
            'balance_paise' => $paise,
            'balance' => $balance,
            'formatted_balance' => formatCurrency($balance)
        ];
    }

    /**
     * Add funds atomically to wallet
     */
    public static function addFunds(int $userId, float $amountRupees, string $paymentMethod = 'UPI', ?string $idempotencyKey = null): array {
        $amountPaise = toPaise($amountRupees);
        if ($amountPaise < 100 || $amountPaise > 10000000) { // ₹1 to ₹1,00,000
            throw new InvalidArgumentException("Amount must be between ₹1.00 and ₹1,00,000.00.");
        }

        if ($idempotencyKey && LedgerService::checkIdempotency($idempotencyKey)) {
            return self::getBalance($userId);
        }

        $pdo = getDBConnection();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("SELECT id, balance_paise, balance FROM wallets WHERE user_id = ? FOR UPDATE");
            $stmt->execute([$userId]);
            $wallet = $stmt->fetch();
            if (!$wallet) {
                throw new RuntimeException("Wallet account not found.");
            }

            $walletId = (int)$wallet['id'];
            $currentPaise = isset($wallet['balance_paise']) && $wallet['balance_paise'] > 0 
                ? (int)$wallet['balance_paise'] 
                : toPaise((float)$wallet['balance']);
            
            $newPaise = $currentPaise + $amountPaise;
            $newRupees = toRupees($newPaise);
            $txId = generateTransactionID('WT');

            // Update wallet balance atomically
            $stmtUp = $pdo->prepare("UPDATE wallets SET balance = ?, balance_paise = ?, updated_at = NOW() WHERE id = ?");
            $stmtUp->execute([$newRupees, $newPaise, $walletId]);

            // Double-entry immutable ledger record
            LedgerService::record(
                $walletId,
                $userId,
                $txId,
                'CREDIT',
                'TOPUP',
                $amountPaise,
                $newPaise,
                "Added funds via {$paymentMethod}",
                null,
                null,
                $idempotencyKey
            );

            // Legacy transaction record for backwards-compatible listing
            $stmtTx = $pdo->prepare("
                INSERT INTO transactions (user_id, transaction_id, type, mobile_or_consumer_number, operator_or_provider, amount, status, payment_method, description, created_at)
                VALUES (?, ?, 'wallet_topup', '-', 'Wallet Load', ?, 'SUCCESS', ?, 'Wallet Top-up', NOW())
            ");
            $stmtTx->execute([$userId, $txId, $amountRupees, $paymentMethod]);

            $pdo->commit();

            return [
                'transaction_id' => $txId,
                'amount' => $amountRupees,
                'amount_paise' => $amountPaise,
                'new_balance' => $newRupees,
                'new_balance_paise' => $newPaise,
                'formatted_balance' => formatCurrency($newRupees)
            ];
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Peer-to-peer wallet transfer with deadlock prevention
     */
    public static function transferFunds(int $senderId, int $receiverId, float $amountRupees, string $note = '', ?string $idempotencyKey = null): array {
        if ($senderId === $receiverId) {
            throw new InvalidArgumentException("Cannot transfer funds to your own account.");
        }
        $amountPaise = toPaise($amountRupees);
        if ($amountPaise < 100 || $amountPaise > 5000000) { // ₹1 to ₹50,000
            throw new InvalidArgumentException("Transfer amount must be between ₹1.00 and ₹50,000.00.");
        }

        if ($idempotencyKey && LedgerService::checkIdempotency($idempotencyKey)) {
            return self::getBalance($senderId);
        }

        $pdo = getDBConnection();
        $pdo->beginTransaction();
        try {
            // Deadlock prevention: lock lower user ID first
            $firstId = min($senderId, $receiverId);
            $secondId = max($senderId, $receiverId);

            $stmt1 = $pdo->prepare("SELECT id, user_id, balance, balance_paise FROM wallets WHERE user_id = ? FOR UPDATE");
            $stmt1->execute([$firstId]);
            $w1 = $stmt1->fetch();

            $stmt2 = $pdo->prepare("SELECT id, user_id, balance, balance_paise FROM wallets WHERE user_id = ? FOR UPDATE");
            $stmt2->execute([$secondId]);
            $w2 = $stmt2->fetch();

            $senderW = ($firstId === $senderId) ? $w1 : $w2;
            $receiverW = ($firstId === $receiverId) ? $w1 : $w2;

            if (!$senderW || !$receiverW) {
                throw new RuntimeException("Sender or receiver wallet does not exist.");
            }

            $senderPaise = (int)($senderW['balance_paise'] ?: toPaise((float)$senderW['balance']));
            $receiverPaise = (int)($receiverW['balance_paise'] ?: toPaise((float)$receiverW['balance']));

            if ($senderPaise < $amountPaise) {
                throw new RuntimeException("Insufficient wallet balance. Please add funds.");
            }

            // User info
            $stmtU = $pdo->prepare("SELECT full_name, mobile, email FROM users WHERE id = ?");
            $stmtU->execute([$receiverId]);
            $receiver = $stmtU->fetch();

            $stmtS = $pdo->prepare("SELECT full_name, mobile, email FROM users WHERE id = ?");
            $stmtS->execute([$senderId]);
            $sender = $stmtS->fetch();

            $senderNewPaise = $senderPaise - $amountPaise;
            $receiverNewPaise = $receiverPaise + $amountPaise;

            $senderNewRupees = toRupees($senderNewPaise);
            $receiverNewRupees = toRupees($receiverNewPaise);

            // Update wallets
            $stmtUp = $pdo->prepare("UPDATE wallets SET balance = ?, balance_paise = ?, updated_at = NOW() WHERE id = ?");
            $stmtUp->execute([$senderNewRupees, $senderNewPaise, (int)$senderW['id']]);
            $stmtUp->execute([$receiverNewRupees, $receiverNewPaise, (int)$receiverW['id']]);

            $txId = generateTransactionID('TR');

            // Sender ledger (DEBIT)
            LedgerService::record(
                (int)$senderW['id'],
                $senderId,
                $txId,
                'DEBIT',
                'TRANSFER_SENT',
                $amountPaise,
                $senderNewPaise,
                "Transfer to {$receiver['full_name']} (" . ($note ?: 'Personal') . ")",
                $receiverId,
                $receiver['mobile'] . '@payben',
                $idempotencyKey ? "{$idempotencyKey}_debit" : null
            );

            // Receiver ledger (CREDIT)
            LedgerService::record(
                (int)$receiverW['id'],
                $receiverId,
                $txId,
                'CREDIT',
                'TRANSFER_RECEIVED',
                $amountPaise,
                $receiverNewPaise,
                "Transfer from {$sender['full_name']} (" . ($note ?: 'Personal') . ")",
                $senderId,
                $sender['mobile'] . '@payben',
                $idempotencyKey ? "{$idempotencyKey}_credit" : null
            );

            // Log transaction entries
            $stmtTx = $pdo->prepare("
                INSERT INTO transactions (user_id, transaction_id, type, mobile_or_consumer_number, operator_or_provider, amount, status, payment_method, description, created_at)
                VALUES (?, ?, 'wallet_topup', ?, ?, ?, 'SUCCESS', 'Wallet', ?, NOW())
            ");
            $stmtTx->execute([$senderId, $txId, $receiver['mobile'], $receiver['full_name'], $amountRupees, "Sent to {$receiver['full_name']}"]);
            $stmtTx->execute([$receiverId, $txId, $sender['mobile'], $sender['full_name'], $amountRupees, "Received from {$sender['full_name']}"]);

            $pdo->commit();

            return [
                'transaction_id' => $txId,
                'amount' => $amountRupees,
                'amount_paise' => $amountPaise,
                'sender_balance' => $senderNewRupees,
                'formatted_balance' => formatCurrency($senderNewRupees),
                'recipient_name' => $receiver['full_name'],
                'recipient_mobile' => $receiver['mobile']
            ];
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Debit wallet atomically for service checkout (Recharge, Bill Payment)
     */
    public static function debitForService(int $userId, float $amountRupees, string $category, string $description, ?string $idempotencyKey = null): array {
        $amountPaise = toPaise($amountRupees);
        if ($amountPaise <= 0) {
            throw new InvalidArgumentException("Invalid payment amount.");
        }

        $pdo = getDBConnection();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("SELECT id, balance, balance_paise FROM wallets WHERE user_id = ? FOR UPDATE");
            $stmt->execute([$userId]);
            $wallet = $stmt->fetch();
            if (!$wallet) {
                throw new RuntimeException("Wallet not found.");
            }

            $currentPaise = (int)($wallet['balance_paise'] ?: toPaise((float)$wallet['balance']));
            if ($currentPaise < $amountPaise) {
                throw new RuntimeException("Insufficient wallet balance (₹" . number_format(toRupees($currentPaise), 2) . "). Please add funds to proceed.");
            }

            $newPaise = $currentPaise - $amountPaise;
            $newRupees = toRupees($newPaise);
            $txId = generateTransactionID('SV');

            $stmtUp = $pdo->prepare("UPDATE wallets SET balance = ?, balance_paise = ?, updated_at = NOW() WHERE id = ?");
            $stmtUp->execute([$newRupees, $newPaise, (int)$wallet['id']]);

            LedgerService::record(
                (int)$wallet['id'],
                $userId,
                $txId,
                'DEBIT',
                strtoupper($category),
                $amountPaise,
                $newPaise,
                $description,
                null,
                null,
                $idempotencyKey
            );

            $pdo->commit();

            return [
                'transaction_id' => $txId,
                'amount' => $amountRupees,
                'amount_paise' => $amountPaise,
                'new_balance' => $newRupees,
                'formatted_balance' => formatCurrency($newRupees)
            ];
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Compensating refund if an external or sandbox provider fails after debit
     */
    public static function compensatingRefund(int $userId, float $amountRupees, string $originalTxId, string $reason): array {
        $amountPaise = toPaise($amountRupees);
        $pdo = getDBConnection();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("SELECT id, balance, balance_paise FROM wallets WHERE user_id = ? FOR UPDATE");
            $stmt->execute([$userId]);
            $wallet = $stmt->fetch();
            if (!$wallet) {
                throw new RuntimeException("Wallet not found.");
            }

            $currentPaise = (int)($wallet['balance_paise'] ?: toPaise((float)$wallet['balance']));
            $newPaise = $currentPaise + $amountPaise;
            $newRupees = toRupees($newPaise);
            $refundTxId = generateTransactionID('RF');

            $stmtUp = $pdo->prepare("UPDATE wallets SET balance = ?, balance_paise = ?, updated_at = NOW() WHERE id = ?");
            $stmtUp->execute([$newRupees, $newPaise, (int)$wallet['id']]);

            LedgerService::record(
                (int)$wallet['id'],
                $userId,
                $refundTxId,
                'CREDIT',
                'REFUND',
                $amountPaise,
                $newPaise,
                "Refund for {$originalTxId}: {$reason}"
            );

            $pdo->commit();

            return [
                'refund_transaction_id' => $refundTxId,
                'amount' => $amountRupees,
                'new_balance' => $newRupees,
                'formatted_balance' => formatCurrency($newRupees)
            ];
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
