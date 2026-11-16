<?php

require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/middleware/auth.php';
require_once dirname(dirname(__DIR__)) . '/services/NotificationService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$user = requireAuth();
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$notificationId = isset($input['notification_id']) ? (int)$input['notification_id'] : null;

NotificationService::markAsRead((int)$user['id'], $notificationId);
jsonResponse(['success' => true, 'message' => 'Notification status updated.']);
