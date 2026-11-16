<?php

require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/middleware/auth.php';
require_once dirname(dirname(__DIR__)) . '/services/FraudDetectionService.php';

requireAdminAuth();

$limit = max(1, min(100, (int)($_GET['limit'] ?? 50)));
$offset = max(0, (int)($_GET['offset'] ?? 0));

$alerts = FraudDetectionService::getAlerts($limit, $offset);
jsonResponse(['success' => true, 'data' => $alerts]);
