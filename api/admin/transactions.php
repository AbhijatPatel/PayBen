<?php
// admin/transactions

header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

if (!isAdmin()) {
    jsonResponse(['success' => false, 'message' => 'Administrator privileges required.'], 403);
}

$page      = max(1, (int)($_GET['page'] ?? 1));
$perPage   = 20;
$offset    = ($page - 1) * $perPage;
$type      = $_GET['type'] ?? '';
$status    = $_GET['status'] ?? '';
$search    = trim($_GET['search'] ?? '');
$dateFrom  = $_GET['date_from'] ?? '';
$dateTo    = $_GET['date_to'] ?? '';

$validTypes    = ['mobile_recharge','dth_recharge','electricity','water','gas','wallet_topup'];
$validStatuses = ['SUCCESS','PENDING','FAILED'];

$where  = ['1=1'];
$params = [];

if ($type && in_array($type, $validTypes))             { $where[] = 't.type = ?';           $params[] = $type; }
if ($status && in_array($status, $validStatuses))      { $where[] = 't.status = ?';         $params[] = $status; }
if ($search) {
    $where[] = '(t.transaction_id LIKE ? OR t.mobile_or_consumer_number LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)';
    $s = "%$search%"; $params[] = $s; $params[] = $s; $params[] = $s; $params[] = $s;
}
if ($dateFrom)  { $where[] = 'DATE(t.created_at) >= ?'; $params[] = $dateFrom; }
if ($dateTo)    { $where[] = 'DATE(t.created_at) <= ?'; $params[] = $dateTo; }

$whereClause = implode(' AND ', $where);

try {
    $pdo = getDBConnection();

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM transactions t JOIN users u ON t.user_id=u.id WHERE $whereClause");
    $countStmt->execute($params); $total = (int)$countStmt->fetchColumn();

    // Aggregate for filtered view
    $aggStmt = $pdo->prepare("SELECT SUM(t.amount) as total_volume, COUNT(*) as count,
        SUM(CASE WHEN t.status='SUCCESS' THEN 1 ELSE 0 END) as success_count
        FROM transactions t JOIN users u ON t.user_id=u.id WHERE $whereClause");
    $aggStmt->execute($params); $agg = $aggStmt->fetch();

    $dataStmt = $pdo->prepare("SELECT t.id, t.transaction_id, t.type, t.mobile_or_consumer_number,
        t.operator_or_provider, t.amount, t.status, t.payment_method, t.description, t.created_at,
        u.full_name, u.email, u.mobile as user_mobile
        FROM transactions t JOIN users u ON t.user_id=u.id
        WHERE $whereClause ORDER BY t.created_at DESC, t.id DESC LIMIT $perPage OFFSET $offset");
    $dataStmt->execute($params); $transactions = $dataStmt->fetchAll();

    foreach ($transactions as &$txn) {
        $txn['amount_formatted']     = formatCurrency((float)$txn['amount']);
        $txn['created_at_formatted'] = date('d M Y, h:i A', strtotime($txn['created_at']));
    }

    jsonResponse([
        'success' => true,
        'data' => [
            'transactions'   => $transactions,
            'aggregate' => [
                'total_volume'       => formatCurrency((float)($agg['total_volume'] ?? 0)),
                'total_count'        => (int)$agg['count'],
                'success_count'      => (int)$agg['success_count']
            ],
            'pagination' => [
                'current_page'  => $page,
                'per_page'      => $perPage,
                'total_records' => $total,
                'total_pages'   => (int)ceil($total / $perPage),
                'has_next'      => $page < (int)ceil($total / $perPage),
                'has_prev'      => $page > 1
            ]
        ]
    ]);

} catch (PDOException $e) {
    error_log('Admin transactions API error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Failed to fetch transactions.'], 500);
}

