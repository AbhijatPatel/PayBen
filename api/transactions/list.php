<?php
// transactions/list

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Authentication required.'], 401);
}

$userId    = (int)$_SESSION['user_id'];
$page      = max(1, (int)($_GET['page'] ?? 1));
$perPage   = 20;
$offset    = ($page - 1) * $perPage;
$type      = $_GET['type'] ?? '';
$status    = $_GET['status'] ?? '';
$search    = trim($_GET['search'] ?? '');
$dateFrom  = $_GET['date_from'] ?? '';
$dateTo    = $_GET['date_to'] ?? '';

$validTypes   = ['mobile_recharge','dth_recharge','electricity','water','gas','wallet_topup'];
$validStatuses= ['SUCCESS','PENDING','FAILED'];

$where  = ['t.user_id = ?'];
$params = [$userId];

if ($type && in_array($type, $validTypes))             { $where[] = 't.type = ?';           $params[] = $type; }
if ($status && in_array($status, $validStatuses))      { $where[] = 't.status = ?';         $params[] = $status; }
if ($search)                                           { $where[] = '(t.transaction_id LIKE ? OR t.mobile_or_consumer_number LIKE ? OR t.operator_or_provider LIKE ?)';
                                                         $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($dateFrom)                                         { $where[] = 'DATE(t.created_at) >= ?'; $params[] = $dateFrom; }
if ($dateTo)                                           { $where[] = 'DATE(t.created_at) <= ?'; $params[] = $dateTo; }

$whereClause = implode(' AND ', $where);

try {
    $pdo = getDBConnection();

    // Total count for pagination
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM transactions t WHERE $whereClause");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();
    $totalPages = (int)ceil($total / $perPage);

    // Fetch transactions
    $dataStmt = $pdo->prepare("SELECT t.id, t.transaction_id, t.type, t.mobile_or_consumer_number,
        t.operator_or_provider, t.amount, t.status, t.payment_method, t.description, t.created_at
        FROM transactions t WHERE $whereClause ORDER BY t.created_at DESC, t.id DESC LIMIT $perPage OFFSET $offset");
    $dataStmt->execute($params);
    $transactions = $dataStmt->fetchAll();

    // Format amounts
    foreach ($transactions as &$txn) {
        $txn['amount_formatted'] = formatCurrency((float)$txn['amount']);
        $txn['created_at_formatted'] = date('d M Y, h:i A', strtotime($txn['created_at']));
    }

    jsonResponse([
        'success' => true,
        'data' => [
            'transactions'  => $transactions,
            'pagination'    => [
                'current_page'  => $page,
                'per_page'      => $perPage,
                'total_records' => $total,
                'total_pages'   => $totalPages,
                'has_next'      => $page < $totalPages,
                'has_prev'      => $page > 1
            ]
        ]
    ]);

} catch (PDOException $e) {
    error_log('Transaction list API error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Failed to fetch transactions.'], 500);
}

