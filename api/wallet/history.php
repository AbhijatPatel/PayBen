<?php
// wallet/history

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/config.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');

if (!isLoggedIn()) {
    jsonResponse([
        'success' => false,
        'message' => 'Unauthorized. Please sign in to view wallet history.'
    ], 401);
}

$userId = (int)$_SESSION['user_id'];
$limit = min(100, max(1, (int)($_GET['limit'] ?? 20)));
$offset = max(0, (int)($_GET['offset'] ?? 0));
$typeFilter = strtolower(trim((string)($_GET['type'] ?? '')));

try {
    $pdo = getDBConnection();

    $where = ["user_id = ?"];
    $params = [$userId];

    if (in_array($typeFilter, ['credit', 'debit'], true)) {
        $where[] = "type = ?";
        $params[] = $typeFilter;
    }

    $whereSql = implode(' AND ', $where);

    // Get total count
    $countStmt = $pdo->prepare("SELECT COUNT(*) AS total FROM wallet_transactions WHERE {$whereSql}");
    $countStmt->execute($params);
    $totalRecords = (int)$countStmt->fetch()['total'];

    // Get paginated rows
    $sql = "
        SELECT id, type, amount, description, reference_id, balance_after, created_at
        FROM wallet_transactions
        WHERE {$whereSql}
        ORDER BY id DESC
        LIMIT {$limit} OFFSET {$offset}
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $transactions = $stmt->fetchAll();

    $formatted = array_map(function ($row) {
        $amountFloat = (float)$row['amount'];
        $balanceAfterFloat = (float)$row['balance_after'];
        return [
            'id' => (int)$row['id'],
            'type' => $row['type'],
            'amount' => $amountFloat,
            'formatted_amount' => ($row['type'] === 'credit' ? '+' : '-') . formatCurrency($amountFloat),
            'description' => $row['description'],
            'reference_id' => $row['reference_id'],
            'balance_after' => $balanceAfterFloat,
            'formatted_balance_after' => formatCurrency($balanceAfterFloat),
            'created_at' => $row['created_at']
        ];
    }, $transactions);

    jsonResponse([
        'success' => true,
        'data' => [
            'total' => $totalRecords,
            'limit' => $limit,
            'offset' => $offset,
            'transactions' => $formatted
        ]
    ], 200);

} catch (PDOException $e) {
    error_log("Wallet History API Error: " . $e->getMessage());
    jsonResponse([
        'success' => false,
        'message' => 'Failed to retrieve wallet history.'
    ], 500);
}
