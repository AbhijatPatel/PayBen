<?php

require_once dirname(__DIR__) . '/config/database.php';

class Wallet {
    public static function getBalance(int $userId): float {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT balance FROM wallets WHERE user_id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        return $row ? (float)$row['balance'] : 0.00;
    }

    public static function addMoney(int $userId, float $amount, string $paymentMethod = 'UPI'): array {
        if ($amount < 1 || $amount > 100000) {
            throw new InvalidArgumentException("Amount must be between ₹1 and ₹1,00,000.");
        }

        $pdo = getDBConnection();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("SELECT id, balance FROM wallets WHERE user_id = ? FOR UPDATE");
            $stmt->execute([$userId]);
            $wallet = $stmt->fetch();
            if (!$wallet) {
                throw new RuntimeException("Wallet not found.");
            }

            $walletId = (int)$wallet['id'];
            $newBalance = (float)$wallet['balance'] + $amount;
            $txId = generateTransactionID('WT');

            $stmtUp = $pdo->prepare("UPDATE wallets SET balance = ?, updated_at = NOW() WHERE id = ?");
            $stmtUp->execute([$newBalance, $walletId]);

            $stmtLedger = $pdo->prepare("
                INSERT INTO wallet_transactions (user_id, wallet_id, type, amount, description, reference_id, balance_after, created_at)
                VALUES (?, ?, 'credit', ?, ?, ?, ?, NOW())
            ");
            $stmtLedger->execute([$userId, $walletId, $amount, "Added money via {$paymentMethod}", $txId, $newBalance]);

            // General transaction table
            $stmtGen = $pdo->prepare("
                INSERT INTO transactions (user_id, transaction_id, type, mobile_or_consumer_number, operator_or_provider, amount, status, payment_method, description, created_at)
                VALUES (?, ?, 'wallet_topup', '-', 'Wallet Load', ?, 'SUCCESS', ?, 'Wallet Top-up', NOW())
            ");
            $stmtGen->execute([$userId, $txId, $amount, $paymentMethod]);

            $pdo->commit();
            return [
                'transaction_id' => $txId,
                'amount' => $amount,
                'new_balance' => $newBalance
            ];
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function transfer(int $senderId, int $receiverId, float $amount, string $note = ''): array {
        if ($senderId === $receiverId) {
            throw new InvalidArgumentException("Cannot transfer funds to your own account.");
        }
        if ($amount < 1 || $amount > 50000) {
            throw new InvalidArgumentException("Transfer amount must be between ₹1 and ₹50,000.");
        }

        $pdo = getDBConnection();
        $pdo->beginTransaction();
        try {
            // Lock lower ID first to prevent deadlocks
            $firstId = min($senderId, $receiverId);
            $secondId = max($senderId, $receiverId);

            $stmtL1 = $pdo->prepare("SELECT id, user_id, balance FROM wallets WHERE user_id = ? FOR UPDATE");
            $stmtL1->execute([$firstId]);
            $w1 = $stmtL1->fetch();

            $stmtL2 = $pdo->prepare("SELECT id, user_id, balance FROM wallets WHERE user_id = ? FOR UPDATE");
            $stmtL2->execute([$secondId]);
            $w2 = $stmtL2->fetch();

            $senderWallet = ($firstId === $senderId) ? $w1 : $w2;
            $receiverWallet = ($firstId === $receiverId) ? $w1 : $w2;

            if (!$senderWallet || !$receiverWallet) {
                throw new RuntimeException("Sender or receiver wallet does not exist.");
            }

            if ((float)$senderWallet['balance'] < $amount) {
                throw new RuntimeException("Insufficient wallet balance. Please add funds.");
            }

            // Receiver details
            $stmtRecv = $pdo->prepare("SELECT full_name, mobile, email FROM users WHERE id = ?");
            $stmtRecv->execute([$receiverId]);
            $receiver = $stmtRecv->fetch();

            $stmtSend = $pdo->prepare("SELECT full_name, mobile, email FROM users WHERE id = ?");
            $stmtSend->execute([$senderId]);
            $sender = $stmtSend->fetch();

            $senderNewBal = (float)$senderWallet['balance'] - $amount;
            $receiverNewBal = (float)$receiverWallet['balance'] + $amount;
            $txId = generateTransactionID('UPI');

            // Update sender
            $stmtUpS = $pdo->prepare("UPDATE wallets SET balance = ?, updated_at = NOW() WHERE id = ?");
            $stmtUpS->execute([$senderNewBal, $senderWallet['id']]);

            // Update receiver
            $stmtUpR = $pdo->prepare("UPDATE wallets SET balance = ?, updated_at = NOW() WHERE id = ?");
            $stmtUpR->execute([$receiverNewBal, $receiverWallet['id']]);

            // Sender ledger entry
            $descSender = "Sent to " . ($receiver['full_name'] ?? 'User') . ($note ? " ({$note})" : "");
            $stmtLS = $pdo->prepare("
                INSERT INTO wallet_transactions (user_id, wallet_id, type, amount, description, reference_id, balance_after, created_at)
                VALUES (?, ?, 'debit', ?, ?, ?, ?, NOW())
            ");
            $stmtLS->execute([$senderId, $senderWallet['id'], $amount, $descSender, $txId, $senderNewBal]);

            // Receiver ledger entry
            $descReceiver = "Received from " . ($sender['full_name'] ?? 'User') . ($note ? " ({$note})" : "");
            $stmtLR = $pdo->prepare("
                INSERT INTO wallet_transactions (user_id, wallet_id, type, amount, description, reference_id, balance_after, created_at)
                VALUES (?, ?, 'credit', ?, ?, ?, ?, NOW())
            ");
            $stmtLR->execute([$receiverId, $receiverWallet['id'], $amount, $descReceiver, $txId, $receiverNewBal]);

            // Unified transactions table for sender
            $stmtTxS = $pdo->prepare("
                INSERT INTO transactions (user_id, transaction_id, type, mobile_or_consumer_number, operator_or_provider, amount, status, payment_method, description, created_at)
                VALUES (?, ?, 'wallet_topup', ?, 'PayBen UPI', ?, 'SUCCESS', 'Wallet', ?, NOW())
            ");
            $stmtTxS->execute([$senderId, $txId, $receiver['mobile'] ?? '', $amount, $descSender]);

            $pdo->commit();
            return [
                'transaction_id' => $txId,
                'amount' => $amount,
                'recipient_name' => $receiver['full_name'] ?? 'User',
                'recipient_mobile' => $receiver['mobile'] ?? '',
                'new_balance' => $senderNewBal
            ];
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function scratchCard(int $userId): array {
        $pdo = getDBConnection();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("SELECT id, balance FROM wallets WHERE user_id = ? FOR UPDATE");
            $stmt->execute([$userId]);
            $wallet = $stmt->fetch();
            if (!$wallet) throw new RuntimeException("Wallet not found.");

            // Cashback between ₹5 and ₹50
            $cashback = (float)mt_rand(5, 50);
            $newBalance = (float)$wallet['balance'] + $cashback;
            $txId = generateTransactionID('SC');

            $stmtUp = $pdo->prepare("UPDATE wallets SET balance = ?, updated_at = NOW() WHERE id = ?");
            $stmtUp->execute([$newBalance, $wallet['id']]);

            $stmtLedger = $pdo->prepare("
                INSERT INTO wallet_transactions (user_id, wallet_id, type, amount, description, reference_id, balance_after, created_at)
                VALUES (?, ?, 'credit', ?, 'PayBen Scratch Card Reward', ?, ?, NOW())
            ");
            $stmtLedger->execute([$userId, $wallet['id'], $cashback, $txId, $newBalance]);

            $pdo->commit();
            return [
                'transaction_id' => $txId,
                'cashback_amount' => $cashback,
                'new_balance' => $newBalance
            ];
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function getHistory(int $userId, int $limit = 20, int $offset = 0): array {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            SELECT id, type, amount, description, reference_id, balance_after, created_at
            FROM wallet_transactions
            WHERE user_id = ?
            ORDER BY created_at DESC, id DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->bindValue(3, $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
