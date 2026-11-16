<?php

require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/middleware/auth.php';
require_once dirname(dirname(__DIR__)) . '/services/PaymentService.php';

$user = requireAuth();
$orderId = trim($_GET['order_id'] ?? '');

if (empty($orderId)) {
    jsonResponse(['success' => false, 'message' => 'Order ID is required.'], 400);
}

try {
    $order = PaymentService::getOrderStatus($orderId);
    jsonResponse(['success' => true, 'data' => $order]);
} catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => $e->getMessage()], 404);
}
