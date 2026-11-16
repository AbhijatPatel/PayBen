<?php

require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/services/OtpService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$identifier = trim($input['identifier'] ?? $input['email'] ?? $input['mobile'] ?? '');
$purpose = trim($input['purpose'] ?? 'LOGIN');
$otp = trim($input['otp'] ?? '');

if (empty($identifier) || empty($otp)) {
    jsonResponse(['success' => false, 'message' => 'Identifier and 6-digit OTP code are required.'], 400);
}

try {
    $ok = OtpService::verifyOtp($identifier, $purpose, $otp);
    jsonResponse([
        'success' => true,
        'message' => 'OTP verified successfully.',
        'data' => [
            'identifier' => $identifier,
            'verified' => true
        ]
    ]);
} catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
}
