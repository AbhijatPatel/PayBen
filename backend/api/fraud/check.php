<?php

require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/middleware/auth.php';
require_once dirname(dirname(__DIR__)) . '/services/FraudDetectionService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$user = requireAuth();
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$amount = (float)($input['amount'] ?? 0);
$type = trim($input['type'] ?? 'transfer');
$recipient = $input['recipient'] ?? null;

$eval = FraudDetectionService::evaluateTransactionRisk((int)$user['id'], $amount, $type, $recipient);
jsonResponse(['success' => true, 'data' => $eval]);
