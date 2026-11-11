<?php

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/middleware/auth.php';
require_once dirname(__DIR__) . '/models/Transaction.php';
require_once dirname(__DIR__) . '/models/User.php';

class TransactionController {
    public static function list() {
        $user = requireAuth();
        $type = $_GET['type'] ?? null;
        $status = $_GET['status'] ?? null;
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 50)));
        $offset = max(0, (int)($_GET['offset'] ?? 0));

        $transactions = Transaction::getUserTransactions((int)$user['id'], $type, $status, $limit, $offset);
        $stats = Transaction::getUserStats((int)$user['id']);

        jsonResponse([
            'success' => true,
            'data' => [
                'transactions' => $transactions,
                'stats' => $stats
            ]
        ]);
    }

    public static function details() {
        $user = requireAuth();
        $txId = trim($_GET['id'] ?? $_GET['transaction_id'] ?? '');
        if (!$txId) {
            jsonResponse(['success' => false, 'message' => 'Transaction ID is required.'], 400);
        }

        $tx = Transaction::getDetails((int)$user['id'], $txId);
        if (!$tx) {
            jsonResponse(['success' => false, 'message' => 'Transaction not found.'], 404);
        }

        jsonResponse([
            'success' => true,
            'data' => $tx
        ]);
    }

    public static function getDashboardSummary() {
        $user = requireAuth();
        $userId = (int)$user['id'];
        $stats = Transaction::getUserStats($userId);
        $recent = Transaction::getUserTransactions($userId, null, null, 6, 0);
        $contacts = User::getNetworkContacts($userId, 8);

        jsonResponse([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $userId,
                    'full_name' => $user['full_name'],
                    'email' => $user['email'],
                    'mobile' => $user['mobile'],
                    'avatar' => $user['avatar'] ?? null,
                    'upi_id' => $user['mobile'] . '@payben'
                ],
                'wallet' => [
                    'balance' => (float)$user['wallet_balance'],
                    'formatted_balance' => formatCurrency((float)$user['wallet_balance'])
                ],
                'stats' => $stats,
                'recent_transactions' => $recent,
                'contacts' => $contacts
            ]
        ]);
    }
}

