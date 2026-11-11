<?php

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/middleware/auth.php';
require_once dirname(__DIR__) . '/models/Wallet.php';
require_once dirname(__DIR__) . '/models/User.php';

class WalletController {
    public static function getBalance() {
        $user = requireAuth();
        $balance = Wallet::getBalance((int)$user['id']);
        jsonResponse([
            'success' => true,
            'data' => [
                'balance' => $balance,
                'formatted_balance' => formatCurrency($balance),
                'upi_id' => $user['mobile'] . '@payben'
            ]
        ]);
    }

    public static function addMoney(array $data) {
        $user = requireAuth();
        $amount = (float)($data['amount'] ?? 0);
        $method = trim($data['payment_method'] ?? 'UPI');

        try {
            $result = Wallet::addMoney((int)$user['id'], $amount, $method);
            jsonResponse([
                'success' => true,
                'message' => '₹' . number_format($amount, 2) . ' added successfully to wallet!',
                'data' => [
                    'transaction_id' => $result['transaction_id'],
                    'amount' => $amount,
                    'new_balance' => $result['new_balance'],
                    'formatted_balance' => formatCurrency($result['new_balance'])
                ]
            ]);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public static function transfer(array $data) {
        $user = requireAuth();
        $recipientInput = trim($data['recipient'] ?? $data['mobile'] ?? '');
        $amount = (float)($data['amount'] ?? 0);
        $note = trim($data['note'] ?? '');

        // Strip @payben if provided
        $recipientClean = strtolower(str_replace('@payben', '', $recipientInput));
        $recipient = User::findByMobileOrEmail($recipientClean);

        if (!$recipient) {
            jsonResponse(['success' => false, 'message' => 'Recipient not found on PayBen network. Check mobile or email.'], 404);
        }

        try {
            $result = Wallet::transfer((int)$user['id'], (int)$recipient['id'], $amount, $note);
            jsonResponse([
                'success' => true,
                'message' => 'Payment of ₹' . number_format($amount, 2) . ' sent successfully!',
                'data' => [
                    'transaction_id' => $result['transaction_id'],
                    'recipient_name' => $result['recipient_name'],
                    'recipient_mobile' => $result['recipient_mobile'],
                    'amount' => $amount,
                    'new_balance' => $result['new_balance'],
                    'formatted_balance' => formatCurrency($result['new_balance'])
                ]
            ]);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public static function scratchCard() {
        $user = requireAuth();
        try {
            $result = Wallet::scratchCard((int)$user['id']);
            jsonResponse([
                'success' => true,
                'message' => 'Congratulations! You won ₹' . number_format($result['cashback_amount'], 2) . ' cashback!',
                'data' => [
                    'transaction_id' => $result['transaction_id'],
                    'cashback_amount' => $result['cashback_amount'],
                    'new_balance' => $result['new_balance'],
                    'formatted_balance' => formatCurrency($result['new_balance'])
                ]
            ]);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public static function getHistory() {
        $user = requireAuth();
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 20)));
        $offset = max(0, (int)($_GET['offset'] ?? 0));

        $history = Wallet::getHistory((int)$user['id'], $limit, $offset);
        jsonResponse([
            'success' => true,
            'data' => $history
        ]);
    }
}
