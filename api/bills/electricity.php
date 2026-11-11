<?php
// bills/electricity

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Authentication required. Please log in.'], 401);
}

$userId = (int)$_SESSION['user_id'];
$pdo    = getDBConnection();

// ── FETCH BILL (GET) ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'fetch') {
    $provider_id     = (int)($_GET['provider_id'] ?? 0);
    $consumer_number = trim($_GET['consumer_number'] ?? '');

    if ($provider_id <= 0 || empty($consumer_number)) {
        jsonResponse(['success' => false, 'message' => 'Provider ID and consumer number are required.'], 422);
    }

    try {
        // Verify provider is electricity type
        $provStmt = $pdo->prepare("SELECT id, name, state FROM utility_providers WHERE id = ? AND type = 'electricity' AND status = 'active' LIMIT 1");
        $provStmt->execute([$provider_id]);
        $provider = $provStmt->fetch();
        if (!$provider) {
            jsonResponse(['success' => false, 'message' => 'Electricity provider not found or inactive.'], 404);
        }

        $billStmt = $pdo->prepare("SELECT * FROM sample_bills WHERE provider_id = ? AND consumer_number = ? AND status = 'unpaid' LIMIT 1");
        $billStmt->execute([$provider_id, $consumer_number]);
        $bill = $billStmt->fetch();

        if (!$bill) {
            jsonResponse(['success' => false, 'message' => 'No pending bill found for this consumer number. It may already be paid or the number is incorrect.'], 404);
        }

        jsonResponse([
            'success' => true,
            'message' => 'Bill details fetched successfully.',
            'data' => [
                'bill_id'         => $bill['id'],
                'consumer_name'   => $bill['consumer_name'],
                'consumer_number' => $bill['consumer_number'],
                'provider'        => $provider['name'],
                'state'           => $provider['state'],
                'bill_amount'     => $bill['bill_amount'],
                'amount_formatted'=> formatCurrency((float)$bill['bill_amount']),
                'bill_date'       => $bill['bill_date'],
                'due_date'        => $bill['due_date'],
                'status'          => $bill['status']
            ]
        ]);
    } catch (PDOException $e) {
        error_log('Electricity fetch API error: ' . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Failed to fetch bill. Please try again.'], 500);
    }
}

// ── PAY BILL (POST) ───────────────────────────────────────────────────────────
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) $input = $_POST;

    if (($input['action'] ?? '') !== 'pay') {
        jsonResponse(['success' => false, 'message' => 'Invalid action. Use action=pay for POST requests.'], 400);
    }

    $bill_id = (int)($input['bill_id'] ?? 0);
    if ($bill_id <= 0) {
        jsonResponse(['success' => false, 'message' => 'Valid bill_id is required.'], 422);
    }

    try {
        $pdo->beginTransaction();

        // Lock wallet row
        $walletStmt = $pdo->prepare("SELECT id, balance FROM wallets WHERE user_id = ? FOR UPDATE");
        $walletStmt->execute([$userId]);
        $wallet = $walletStmt->fetch();
        if (!$wallet) { $pdo->rollBack(); jsonResponse(['success' => false, 'message' => 'Wallet not found.'], 404); }

        // Fetch unpaid bill (lock it)
        $billStmt = $pdo->prepare("SELECT sb.*, up.name as provider_name, up.state FROM sample_bills sb
            JOIN utility_providers up ON sb.provider_id = up.id
            WHERE sb.id = ? AND sb.status = 'unpaid' AND up.type = 'electricity' LIMIT 1");
        $billStmt->execute([$bill_id]);
        $bill = $billStmt->fetch();
        if (!$bill) { $pdo->rollBack(); jsonResponse(['success' => false, 'message' => 'Bill not found or already paid.'], 404); }

        $amount = (float)$bill['bill_amount'];
        if ((float)$wallet['balance'] < $amount) {
            $pdo->rollBack();
            jsonResponse(['success' => false, 'message' => 'Insufficient wallet balance.',
                'available' => formatCurrency((float)$wallet['balance']), 'required' => formatCurrency($amount)], 402);
        }

        $newBalance = (float)$wallet['balance'] - $amount;
        $txnId      = generateTransactionID('EB');

        $pdo->prepare("UPDATE wallets SET balance = ? WHERE id = ?")->execute([$newBalance, $wallet['id']]);
        $pdo->prepare("INSERT INTO wallet_transactions (user_id, wallet_id, type, amount, description, reference_id, balance_after)
                       VALUES (?, ?, 'debit', ?, ?, ?, ?)")
            ->execute([$userId, $wallet['id'], $amount,
                "Electricity Bill - {$bill['provider_name']} - Consumer #{$bill['consumer_number']} [SIMULATED]",
                $txnId, $newBalance]);
        $pdo->prepare("INSERT INTO transactions (user_id, transaction_id, type, mobile_or_consumer_number, operator_or_provider, amount, status, payment_method, description)
                       VALUES (?, ?, 'electricity', ?, ?, ?, 'SUCCESS', 'Wallet', ?)")
            ->execute([$userId, $txnId, $bill['consumer_number'], $bill['provider_name'], $amount,
                "Electricity Bill Payment - {$bill['provider_name']} [SIMULATED]"]);
        $pdo->prepare("UPDATE sample_bills SET status = 'paid' WHERE id = ?")->execute([$bill_id]);

        $pdo->commit();

        jsonResponse([
            'success' => true,
            'message' => 'Electricity bill paid successfully! [SIMULATED PAYMENT]',
            'data' => [
                'transaction_id'   => $txnId,
                'consumer_number'  => $bill['consumer_number'],
                'consumer_name'    => $bill['consumer_name'],
                'provider'         => $bill['provider_name'],
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
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Electricity pay API error: ' . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Payment failed due to a server error. Please try again.'], 500);
    }
} else {
    jsonResponse(['success' => false, 'message' => 'Invalid request. Use GET?action=fetch or POST with action=pay.'], 405);
}

