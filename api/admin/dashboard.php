<?php
// admin/dashboard

header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

if (!isAdmin()) {
    jsonResponse(['success' => false, 'message' => 'Administrator privileges required.'], 403);
}

try {
    $pdo = getDBConnection();

    // User stats
    $users = $pdo->query("SELECT COUNT(*) as total, SUM(CASE WHEN status='active' THEN 1 ELSE 0 END) as active FROM users")->fetch();

    // Transaction stats
    $txnStats = $pdo->query("SELECT
        COUNT(*) as total,
        SUM(CASE WHEN status='SUCCESS' THEN 1 ELSE 0 END) as successful,
        SUM(CASE WHEN status='PENDING' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status='FAILED'  THEN 1 ELSE 0 END) as failed,
        SUM(CASE WHEN status='SUCCESS' THEN amount ELSE 0 END) as total_volume
        FROM transactions")->fetch();

    // Wallet reserve
    $walletSum = $pdo->query("SELECT SUM(balance) as total_balance FROM wallets")->fetchColumn();

    // Service breakdown
    $serviceBreakdown = $pdo->query("SELECT type, COUNT(*) as count, SUM(amount) as volume
        FROM transactions WHERE status='SUCCESS' GROUP BY type")->fetchAll();

    // Recent users (last 5)
    $recentUsers = $pdo->query("SELECT u.id, u.full_name, u.email, u.mobile, u.status, u.created_at, w.balance
        FROM users u LEFT JOIN wallets w ON u.id=w.user_id ORDER BY u.created_at DESC LIMIT 5")->fetchAll();

    // Recent transactions (last 5)
    $recentTxns = $pdo->query("SELECT t.*, u.full_name FROM transactions t JOIN users u ON t.user_id=u.id
        ORDER BY t.created_at DESC LIMIT 5")->fetchAll();

    foreach ($recentTxns as &$txn) {
        $txn['amount_formatted'] = formatCurrency((float)$txn['amount']);
    }

    jsonResponse([
        'success' => true,
        'data' => [
            'users' => [
                'total'  => (int)$users['total'],
                'active' => (int)$users['active']
            ],
            'transactions' => [
                'total'        => (int)$txnStats['total'],
                'successful'   => (int)$txnStats['successful'],
                'pending'      => (int)$txnStats['pending'],
                'failed'       => (int)$txnStats['failed'],
                'total_volume' => (float)$txnStats['total_volume'],
                'total_volume_fmt' => formatCurrency((float)$txnStats['total_volume'])
            ],
            'wallet_reserve'       => formatCurrency((float)$walletSum),
            'service_breakdown'    => $serviceBreakdown,
            'recent_users'         => $recentUsers,
            'recent_transactions'  => $recentTxns
        ]
    ]);

} catch (PDOException $e) {
    error_log('Admin dashboard API error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Failed to fetch dashboard data.'], 500);
}

