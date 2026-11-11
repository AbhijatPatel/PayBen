<?php
// wallet/balance

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/config.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');

if (!isLoggedIn()) {
    jsonResponse([
        'success' => false,
        'message' => 'Unauthorized. Please sign in to access wallet balance.'
    ], 401);
}

$userId = (int)$_SESSION['user_id'];

try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT w.id AS wallet_id, w.balance, u.full_name, u.email, u.mobile, w.updated_at
        FROM wallets w
        JOIN users u ON w.user_id = u.id
        WHERE w.user_id = ?
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $wallet = $stmt->fetch();

    if (!$wallet) {
        jsonResponse([
            'success' => false,
            'message' => 'Wallet account not found for user.'
        ], 404);
    }

    $balance = (float)$wallet['balance'];

    jsonResponse([
        'success' => true,
        'data' => [
            'wallet_id' => (int)$wallet['wallet_id'],
            'user_id' => $userId,
            'user_name' => $wallet['full_name'],
            'balance' => $balance,
            'formatted_balance' => formatCurrency($balance),
            'currency' => 'INR',
            'last_updated' => $wallet['updated_at']
        ]
    ], 200);

} catch (PDOException $e) {
    error_log("Wallet Balance API Error: " . $e->getMessage());
    jsonResponse([
        'success' => false,
        'message' => 'Failed to retrieve wallet balance.'
    ], 500);
}
