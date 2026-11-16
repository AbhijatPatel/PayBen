<?php

require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/services/OtpService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$identifier = trim($input['identifier'] ?? $input['email'] ?? $input['mobile'] ?? '');
$purpose = trim($input['purpose'] ?? 'LOGIN');

if (empty($identifier)) {
    jsonResponse(['success' => false, 'message' => 'Email or mobile number is required.'], 400);
}

try {
    $result = OtpService::sendOtp($identifier, $purpose);
    jsonResponse([
        'success' => true,
        'message' => $result['message'],
        'data' => $result
    ]);
} catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
}
