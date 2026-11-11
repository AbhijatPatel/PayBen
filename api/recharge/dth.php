<?php
// recharge/dth

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Authentication required. Please log in.'], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed. Use POST.'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) $input = $_POST;

$customer_id = trim($input['customer_id'] ?? '');
$operator_id = (int)($input['operator_id'] ?? 0);
$amount      = (float)($input['amount'] ?? 0);
$userId      = (int)$_SESSION['user_id'];

// Validation
$errors = [];
if (empty($customer_id) || !preg_match('/^\d{8,15}$/', $customer_id))
    $errors['customer_id'] = 'Enter a valid Customer/Subscriber ID (8–15 digits).';
if ($operator_id <= 0)
    $errors['operator_id'] = 'Please select a DTH operator.';
if ($amount < 100 || $amount > 10000)
    $errors['amount'] = 'Recharge amount must be between ₹100 and ₹10,000.';

if (!empty($errors)) {
    jsonResponse(['success' => false, 'message' => 'Validation failed.', 'errors' => $errors], 422);
}

try {
    $pdo = getDBConnection();

    // Verify operator exists and is DTH type
    $opStmt = $pdo->prepare("SELECT id, name FROM operators WHERE id = ? AND type = 'dth' AND status = 'active' LIMIT 1");
    $opStmt->execute([$operator_id]);
    $operator = $opStmt->fetch();
    if (!$operator) {
        jsonResponse(['success' => false, 'message' => 'Selected DTH operator is not available.'], 404);
    }

    $pdo->beginTransaction();

    // Lock wallet row
    $walletStmt = $pdo->prepare("SELECT id, balance FROM wallets WHERE user_id = ? FOR UPDATE");
    $walletStmt->execute([$userId]);
    $wallet = $walletStmt->fetch();

    if (!$wallet) {
        $pdo->rollBack();
        jsonResponse(['success' => false, 'message' => 'Wallet not found. Please contact support.'], 404);
    }

    if ((float)$wallet['balance'] < $amount) {
        $pdo->rollBack();
        jsonResponse([
            'success'           => false,
            'message'           => 'Insufficient wallet balance.',
            'available_balance' => formatCurrency((float)$wallet['balance']),
            'required_amount'   => formatCurrency($amount)
        ], 402);
    }

    $newBalance = (float)$wallet['balance'] - $amount;
    $txnId      = generateTransactionID('DT');

    // Deduct wallet balance
    $pdo->prepare("UPDATE wallets SET balance = ? WHERE id = ?")->execute([$newBalance, $wallet['id']]);

    // Log wallet debit
    $pdo->prepare("INSERT INTO wallet_transactions (user_id, wallet_id, type, amount, description, reference_id, balance_after)
                   VALUES (?, ?, 'debit', ?, ?, ?, ?)")
        ->execute([$userId, $wallet['id'], $amount, "DTH Recharge - {$operator['name']} ({$customer_id}) [SIMULATED]", $txnId, $newBalance]);

    // Create main transaction record
    $pdo->prepare("INSERT INTO transactions (user_id, transaction_id, type, mobile_or_consumer_number, operator_or_provider, amount, status, payment_method, description)
                   VALUES (?, ?, 'dth_recharge', ?, ?, ?, 'SUCCESS', 'Wallet', ?)")
        ->execute([$userId, $txnId, $customer_id, $operator['name'], $amount,
                   "DTH Recharge - {$operator['name']} - Subscriber ID: {$customer_id} [SIMULATED]"]);

    $pdo->commit();

    jsonResponse([
        'success'           => true,
        'message'           => 'DTH recharge successful! [SIMULATED TRANSACTION]',
        'data' => [
            'transaction_id'   => $txnId,
            'customer_id'      => $customer_id,
            'operator'         => $operator['name'],
            'amount'           => $amount,
            'amount_formatted' => formatCurrency($amount),
            'new_balance'      => $newBalance,
            'new_balance_fmt'  => formatCurrency($newBalance),
            'status'           => 'SUCCESS',
            'simulated'        => true,
            'timestamp'        => date('Y-m-d H:i:s')
        ]
    ]);

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    error_log('DTH Recharge API error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Recharge failed due to a server error. Please try again.'], 500);
}

