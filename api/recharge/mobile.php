<?php
// recharge/mobile

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
        'message' => 'Unauthorized. Please sign in to perform a recharge.'
    ], 401);
}

$rawInput = file_get_contents('php://input');
$jsonInput = json_decode($rawInput, true);
$input = is_array($jsonInput) ? array_merge($_POST, $jsonInput) : $_POST;

$mobile = preg_replace('/\D/', '', (string)($input['mobile_number'] ?? ''));
$operatorId = filter_var($input['operator_id'] ?? 0, FILTER_VALIDATE_INT);
$amount = filter_var($input['amount'] ?? 0, FILTER_VALIDATE_FLOAT);
$planId = filter_var($input['plan_id'] ?? null, FILTER_VALIDATE_INT) ?: null;

$errors = [];

if (empty($mobile) || !preg_match('/^[6-9]\d{9}$/', $mobile)) {
    $errors[] = 'A valid 10-digit Indian mobile number starting with 6, 7, 8, or 9 is required.';
}

if (!$operatorId || $operatorId <= 0) {
    $errors[] = 'A valid operator ID must be selected.';
}

if ($amount === false || $amount <= 0 || $amount > 10000.00) {
    $errors[] = 'Recharge amount must be between ₹1.00 and ₹10,000.00.';
}

if (!empty($errors)) {
    jsonResponse([
        'success' => false,
        'message' => implode(' ', $errors),
        'errors' => $errors
    ], 400);
}

$userId = (int)$_SESSION['user_id'];
$pdo = getDBConnection();

try {
    $opStmt = $pdo->prepare("SELECT id, name, code, status FROM operators WHERE id = ? AND type = 'mobile' LIMIT 1");
    $opStmt->execute([$operatorId]);
    $operator = $opStmt->fetch();

    if (!$operator) {
        jsonResponse([
            'success' => false,
            'message' => 'The selected mobile operator does not exist.'
        ], 404);
    }

    if ($operator['status'] !== 'active') {
        jsonResponse([
            'success' => false,
            'message' => 'The selected mobile operator is currently inactive.'
        ], 400);
    }

    // Optional: Fetch plan description if plan_id passed
    $planDesc = 'Mobile Recharge';
    if ($planId) {
        $pStmt = $pdo->prepare("SELECT plan_name, validity, data_limit FROM recharge_plans WHERE id = ? AND operator_id = ? LIMIT 1");
        $pStmt->execute([$planId, $operatorId]);
        $plan = $pStmt->fetch();
        if ($plan) {
            $planDesc = "{$plan['plan_name']} ({$plan['validity']}, {$plan['data_limit']})";
        }
    }

    $pdo->beginTransaction();
    $wStmt = $pdo->prepare("SELECT id, balance FROM wallets WHERE user_id = ? FOR UPDATE");
    $wStmt->execute([$userId]);
    $wallet = $wStmt->fetch();

    if (!$wallet) {
        $pdo->rollBack();
        jsonResponse([
            'success' => false,
            'message' => 'Wallet account not found.'
        ], 404);
    }

    $walletId = (int)$wallet['id'];
    $currentBalance = (float)$wallet['balance'];
    if ($currentBalance < $amount) {
        $pdo->rollBack();
        jsonResponse([
            'success' => false,
            'message' => 'Insufficient wallet balance. Please add money before proceeding.',
            'data' => [
                'current_balance' => $currentBalance,
                'required_amount' => $amount,
                'shortage' => round($amount - $currentBalance, 2)
            ]
        ], 400);
    }

    $newBalance = round($currentBalance - $amount, 2);
    $updStmt = $pdo->prepare("UPDATE wallets SET balance = ?, updated_at = NOW() WHERE id = ?");
    $updStmt->execute([$newBalance, $walletId]);
    $txId = generateTransactionID('PB');
    $refId = 'WTX' . date('Ymd') . strtoupper(substr(uniqid(), -5));
    $ledgerDesc = "Payment for {$operator['name']} Mobile Recharge ({$mobile})";
    $wtxStmt = $pdo->prepare("
        INSERT INTO wallet_transactions (user_id, wallet_id, type, amount, description, reference_id, balance_after, created_at)
        VALUES (?, ?, 'debit', ?, ?, ?, ?, NOW())
    ");
    $wtxStmt->execute([$userId, $walletId, $amount, $ledgerDesc, $refId, $newBalance]);
    $txDesc = "Mobile Recharge - {$operator['name']} - {$planDesc}";
    $txnStmt = $pdo->prepare("
        INSERT INTO transactions (user_id, transaction_id, type, mobile_or_consumer_number, operator_or_provider, amount, status, payment_method, description, created_at)
        VALUES (?, ?, 'mobile_recharge', ?, ?, ?, 'SUCCESS', 'Wallet', ?, NOW())
    ");
    $txnStmt->execute([$userId, $txId, $mobile, $operator['name'], $amount, $txDesc]);

    $pdo->commit();

    jsonResponse([
        'success' => true,
        'message' => 'Mobile recharge processed successfully!',
        'data' => [
            'transaction_id' => $txId,
            'reference_id' => $refId,
            'type' => 'mobile_recharge',
            'mobile_number' => $mobile,
            'operator' => $operator['name'],
            'amount' => $amount,
            'formatted_amount' => formatCurrency($amount),
            'remaining_balance' => $newBalance,
            'formatted_balance' => formatCurrency($newBalance),
            'status' => 'SUCCESS',
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ], 200);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Mobile Recharge API Error: " . $e->getMessage());
    jsonResponse([
        'success' => false,
        'message' => 'Recharge processing failed due to an internal server error.'
    ], 500);
}
