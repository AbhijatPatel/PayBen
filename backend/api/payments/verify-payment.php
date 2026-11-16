<?php

require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/middleware/auth.php';
require_once dirname(dirname(__DIR__)) . '/services/PaymentService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$user = requireAuth();
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$orderId = trim($input['order_id'] ?? '');
$action = strtoupper(trim($input['action'] ?? 'SUCCESS')); // SUCCESS, FAILED, CANCELLED
$paymentId = $input['payment_id'] ?? null;
$signature = $input['signature'] ?? null;

if (empty($orderId)) {
    jsonResponse(['success' => false, 'message' => 'Order ID is required.'], 400);
}

try {
    $result = PaymentService::verifyPayment((int)$user['id'], $orderId, $action, $paymentId, $signature);
    jsonResponse($result, $result['success'] ? 200 : 400);
} catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
}
