<?php
// transactions/details

header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Authentication required.'], 401);
}

$userId = (int)$_SESSION['user_id'];
$txnId  = trim($_GET['transaction_id'] ?? '');

if (empty($txnId)) {
    jsonResponse(['success' => false, 'message' => 'transaction_id parameter is required.'], 422);
}

try {
    $pdo = getDBConnection();

    $stmt = $pdo->prepare("SELECT t.*, u.full_name, u.email, u.mobile FROM transactions t
        JOIN users u ON t.user_id = u.id
        WHERE t.transaction_id = ? AND t.user_id = ? LIMIT 1");
    $stmt->execute([$txnId, $userId]);
    $txn = $stmt->fetch();

    if (!$txn) {
        jsonResponse(['success' => false, 'message' => 'Transaction not found or access denied.'], 404);
    }

    $txn['amount_formatted']     = formatCurrency((float)$txn['amount']);
    $txn['created_at_formatted'] = date('d M Y, h:i:s A', strtotime($txn['created_at']));

    jsonResponse(['success' => true, 'data' => $txn]);

} catch (PDOException $e) {
    error_log('Transaction details API error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Failed to fetch transaction details.'], 500);
}

