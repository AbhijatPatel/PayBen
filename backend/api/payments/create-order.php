<?php

require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/middleware/auth.php';
require_once dirname(dirname(__DIR__)) . '/services/PaymentService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$user = requireAuth();
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$amount = (float)($input['amount'] ?? 0);
$notes = trim($input['notes'] ?? 'Wallet Top-up');

try {
    $order = PaymentService::createOrder((int)$user['id'], $amount, $notes);
    jsonResponse([
        'success' => true,
        'message' => 'Sandbox payment order generated.',
        'data' => $order
    ]);
} catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
}
