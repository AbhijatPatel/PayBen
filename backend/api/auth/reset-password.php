<?php

require_once dirname(__DIR__, 2) . '/controllers/AuthController.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
AuthController::resetPassword($input);
