<?php

require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/middleware/auth.php';
require_once dirname(dirname(__DIR__)) . '/services/NotificationService.php';

$user = requireAuth();
$limit = max(1, min(50, (int)($_GET['limit'] ?? 20)));

try {
    $data = NotificationService::getList((int)$user['id'], $limit);
    jsonResponse(['success' => true, 'data' => $data]);
} catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
}
