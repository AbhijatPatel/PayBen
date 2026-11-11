<?php
// wallet/transfer

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
    jsonResponse(['success' => false, 'message' => 'Unauthorized. Please sign in to send money.'], 401);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) $input = $_POST;

$senderId = (int)$_SESSION['user_id'];
$recipientInput = trim($input['recipient'] ?? '');
$amount = (float)($input['amount'] ?? 0);
$note = trim($input['note'] ?? 'UPI Payment via PayBen');

if (empty($recipientInput)) {
    jsonResponse(['success' => false, 'message' => 'Recipient phone number or UPI ID is required.'], 422);
}

if ($amount < 1 || $amount > 100000) {
    jsonResponse(['success' => false, 'message' => 'Transfer amount must be between ₹1 and ₹1,00,000.'], 422);
}

// Clean recipient input (strip @payben if provided as UPI ID)
$cleanMobile = preg_replace('/@payben$/i', '', $recipientInput);
$cleanMobile = preg_replace('/\D/', '', $cleanMobile); // extract digits if 10-digit phone

try {
    $pdo = getDBConnection();
    if (strlen($cleanMobile) === 10) {
        $stmt = $pdo->prepare("SELECT id, full_name, email, mobile FROM users WHERE mobile = ? LIMIT 1");
        $stmt->execute([$cleanMobile]);
    } else {
        $stmt = $pdo->prepare("SELECT id, full_name, email, mobile FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$recipientInput]);
    }
    $recipient = $stmt->fetch();

    if (!$recipient) {
        jsonResponse(['success' => false, 'message' => 'Recipient not found on PayBen UPI network. Please verify phone number or UPI ID.'], 404);
    }

    $receiverId = (int)$recipient['id'];
    if ($receiverId === $senderId) {
        jsonResponse(['success' => false, 'message' => 'You cannot transfer money to your own account.'], 400);
    }

    $pdo->beginTransaction();
    $wSenderStmt = $pdo->prepare("SELECT id, balance FROM wallets WHERE user_id = ? FOR UPDATE");
    $wSenderStmt->execute([$senderId]);
    $senderWallet = $wSenderStmt->fetch();

    if (!$senderWallet || (float)$senderWallet['balance'] < $amount) {
        $pdo->rollBack();
        jsonResponse([
            'success' => false,
            'message' => 'Insufficient wallet balance for this transfer.',
            'available' => formatCurrency((float)($senderWallet['balance'] ?? 0)),
            'required' => formatCurrency($amount)
        ], 402);
    }
    $wRecvStmt = $pdo->prepare("SELECT id, balance FROM wallets WHERE user_id = ? FOR UPDATE");
    $wRecvStmt->execute([$receiverId]);
    $receiverWallet = $wRecvStmt->fetch();

    if (!$receiverWallet) {
        // Create wallet if missing
        $pdo->prepare("INSERT INTO wallets (user_id, balance) VALUES (?, 0.00)")->execute([$receiverId]);
        $wRecvStmt->execute([$receiverId]);
        $receiverWallet = $wRecvStmt->fetch();
    }
    $senderNewBal = (float)$senderWallet['balance'] - $amount;
    $recvNewBal   = (float)$receiverWallet['balance'] + $amount;

    $txnId = generateTransactionID('UPI');
    $upiRef = 'UPI' . date('Ymd') . rand(100000, 999999);

    // Update Wallets
    $pdo->prepare("UPDATE wallets SET balance = ?, updated_at = NOW() WHERE id = ?")->execute([$senderNewBal, $senderWallet['id']]);
    $pdo->prepare("UPDATE wallets SET balance = ?, updated_at = NOW() WHERE id = ?")->execute([$recvNewBal, $receiverWallet['id']]);

    // Double-entry ledger: Sender Debit
    $pdo->prepare("INSERT INTO wallet_transactions (user_id, wallet_id, type, amount, description, reference_id, balance_after)
                   VALUES (?, ?, 'debit', ?, ?, ?, ?)")
        ->execute([$senderId, $senderWallet['id'], $amount, "Sent to {$recipient['full_name']} ({$recipient['mobile']}@payben)", $txnId, $senderNewBal]);

    // Double-entry ledger: Receiver Credit
    $pdo->prepare("INSERT INTO wallet_transactions (user_id, wallet_id, type, amount, description, reference_id, balance_after)
                   VALUES (?, ?, 'credit', ?, ?, ?, ?)")
        ->execute([$receiverId, $receiverWallet['id'], $amount, "Received from {$_SESSION['user_name']} ({$_SESSION['user_mobile']}@payben)", $txnId, $recvNewBal]);

    // Record main transaction for sender
    $pdo->prepare("INSERT INTO transactions (user_id, transaction_id, type, mobile_or_consumer_number, operator_or_provider, amount, status, payment_method, description)
                   VALUES (?, ?, 'wallet_topup', ?, ?, ?, 'SUCCESS', 'PayBen UPI', ?)")
        ->execute([$senderId, $txnId, $recipient['mobile'], "PayBen UPI - {$recipient['full_name']}", $amount, "Paid to {$recipient['full_name']} ($note)"]);

    $pdo->commit();

    jsonResponse([
        'success' => true,
        'message' => 'Payment to ' . $recipient['full_name'] . ' was successful!',
        'data' => [
            'transaction_id'    => $txnId,
            'upi_ref'           => $upiRef,
            'recipient_name'    => $recipient['full_name'],
            'recipient_upi'     => $recipient['mobile'] . '@payben',
            'amount'            => $amount,
            'amount_formatted'  => formatCurrency($amount),
            'remaining_balance' => $senderNewBal,
            'balance_formatted' => formatCurrency($senderNewBal),
            'timestamp'         => date('d M Y, h:i A'),
            'note'              => $note
        ]
    ]);

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    error_log('Transfer API error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Transfer failed due to a server error. Please try again.'], 500);
}

