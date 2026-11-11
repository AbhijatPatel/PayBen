<?php
// wallet/add-money

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/config.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse([
        'success' => false,
        'message' => 'HTTP method not allowed. Only POST requests are accepted.'
    ], 405);
}

if (!isLoggedIn()) {
    jsonResponse([
        'success' => false,
        'message' => 'Unauthorized. Please sign in to top up your wallet.'
    ], 401);
}

$rawInput = file_get_contents('php://input');
$jsonInput = json_decode($rawInput, true);
$input = is_array($jsonInput) ? array_merge($_POST, $jsonInput) : $_POST;

$amount = filter_var($input['amount'] ?? 0, FILTER_VALIDATE_FLOAT);
$paymentMethod = sanitize($input['payment_method'] ?? 'UPI / Net Banking');
if (empty($paymentMethod)) {
    $paymentMethod = 'UPI';
}

if ($amount === false || $amount < 1.00 || $amount > 50000.00) {
    jsonResponse([
        'success' => false,
        'message' => 'Please provide a valid top-up amount between ₹1.00 and ₹50,000.00.'
    ], 400);
}

$userId = (int)$_SESSION['user_id'];
$pdo = getDBConnection();

try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("SELECT id, balance FROM wallets WHERE user_id = ? FOR UPDATE");
    $stmt->execute([$userId]);
    $wallet = $stmt->fetch();

    if (!$wallet) {
        $pdo->rollBack();
        jsonResponse([
            'success' => false,
            'message' => 'Wallet account not found.'
        ], 404);
    }

    $walletId = (int)$wallet['id'];
    $currentBalance = (float)$wallet['balance'];
    $newBalance = round($currentBalance + $amount, 2);
    $updStmt = $pdo->prepare("UPDATE wallets SET balance = ?, updated_at = NOW() WHERE id = ?");
    $updStmt->execute([$newBalance, $walletId]);
    $txId = generateTransactionID('PB');
    $refId = 'WTX' . date('Ymd') . strtoupper(substr(uniqid(), -5));
    $desc = "Instant Wallet Topup via " . $paymentMethod;
    $wtxStmt = $pdo->prepare("
        INSERT INTO wallet_transactions (user_id, wallet_id, type, amount, description, reference_id, balance_after, created_at)
        VALUES (?, ?, 'credit', ?, ?, ?, ?, NOW())
    ");
    $wtxStmt->execute([$userId, $walletId, $amount, $desc, $refId, $newBalance]);
    $userMobile = $_SESSION['user_mobile'] ?? 'N/A';
    $txnStmt = $pdo->prepare("
        INSERT INTO transactions (user_id, transaction_id, type, mobile_or_consumer_number, operator_or_provider, amount, status, payment_method, description, created_at)
        VALUES (?, ?, 'wallet_topup', ?, 'PayBen Wallet', ?, 'SUCCESS', ?, ?, NOW())
    ");
    $txnStmt->execute([$userId, $txId, $userMobile, $amount, $paymentMethod, $desc]);

    $pdo->commit();

    jsonResponse([
        'success' => true,
        'message' => 'Wallet topped up successfully!',
        'data' => [
            'transaction_id' => $txId,
            'reference_id' => $refId,
            'amount_added' => $amount,
            'formatted_amount' => formatCurrency($amount),
            'previous_balance' => $currentBalance,
            'new_balance' => $newBalance,
            'formatted_balance' => formatCurrency($newBalance),
            'payment_method' => $paymentMethod,
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ], 200);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Wallet Top-up Error: " . $e->getMessage());
    jsonResponse([
        'success' => false,
        'message' => 'Failed to process top-up due to a server error.'
    ], 500);
}
