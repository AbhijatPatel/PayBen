<?php
// wallet/scratch-card

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/config.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed. Use POST.'], 405);
}

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Unauthorized. Please sign in.'], 401);
}

$userId = (int)$_SESSION['user_id'];

// Random cashback rewards between ₹5 and ₹50
$rewardAmounts = [5.00, 7.50, 10.00, 12.00, 15.00, 20.00, 25.00, 50.00];
$cashback = $rewardAmounts[array_rand($rewardAmounts)];

try {
    $pdo = getDBConnection();
    $pdo->beginTransaction();

    $wStmt = $pdo->prepare("SELECT id, balance FROM wallets WHERE user_id = ? FOR UPDATE");
    $wStmt->execute([$userId]);
    $wallet = $wStmt->fetch();

    if (!$wallet) {
        $pdo->rollBack();
        jsonResponse(['success' => false, 'message' => 'Wallet not found.'], 404);
    }

    $newBalance = (float)$wallet['balance'] + $cashback;
    $refId = 'REW' . date('Ymd') . rand(1000, 9999);

    // Credit cashback to wallet
    $pdo->prepare("UPDATE wallets SET balance = ?, updated_at = NOW() WHERE id = ?")->execute([$newBalance, $wallet['id']]);

    // Ledger credit
    $pdo->prepare("INSERT INTO wallet_transactions (user_id, wallet_id, type, amount, description, reference_id, balance_after)
                   VALUES (?, ?, 'credit', ?, 'PayBen Cashback Reward Won!', ?, ?)")
        ->execute([$userId, $wallet['id'], $cashback, $refId, $newBalance]);

    $pdo->commit();

    jsonResponse([
        'success' => true,
        'message' => 'You won ₹' . number_format($cashback, 2) . ' cashback from PayBen Rewards!',
        'data' => [
            'reward_amount'     => $cashback,
            'reward_formatted'  => formatCurrency($cashback),
            'new_balance'       => $newBalance,
            'balance_formatted' => formatCurrency($newBalance),
            'reference_id'      => $refId,
            'tagline'           => 'Credited directly to your PayBen Wallet!'
        ]
    ]);

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    error_log('Scratch card error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Failed to redeem reward.'], 500);
}

