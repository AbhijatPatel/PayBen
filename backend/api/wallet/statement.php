<?php

require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/middleware/auth.php';
require_once dirname(dirname(__DIR__)) . '/services/LedgerService.php';
require_once dirname(dirname(__DIR__)) . '/services/WalletService.php';

$user = requireAuth();
$limit = max(1, min(100, (int)($_GET['limit'] ?? 50)));
$offset = max(0, (int)($_GET['offset'] ?? 0));

$balanceData = WalletService::getBalance((int)$user['id']);
$statement = LedgerService::getStatement((int)$user['id'], $limit, $offset);

jsonResponse([
    'success' => true,
    'data' => [
        'wallet' => $balanceData,
        'ledger_entries' => $statement
    ]
]);
