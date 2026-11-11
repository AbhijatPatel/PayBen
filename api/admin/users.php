<?php
// admin/users

header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

if (!isAdmin()) {
    jsonResponse(['success' => false, 'message' => 'Administrator privileges required.'], 403);
}

$pdo = getDBConnection();

// ── GET: List users ───────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $page    = max(1, (int)($_GET['page'] ?? 1));
    $perPage = 20;
    $offset  = ($page - 1) * $perPage;
    $search  = trim($_GET['search'] ?? '');
    $status  = $_GET['status'] ?? '';

    $where  = ['1=1'];
    $params = [];

    if ($search) {
        $where[]  = '(u.full_name LIKE ? OR u.email LIKE ? OR u.mobile LIKE ?)';
        $s = "%$search%"; $params[] = $s; $params[] = $s; $params[] = $s;
    }
    if ($status === 'active' || $status === 'inactive') {
        $where[] = 'u.status = ?'; $params[] = $status;
    }

    $whereClause = implode(' AND ', $where);

    try {
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM users u WHERE $whereClause");
        $countStmt->execute($params); $total = (int)$countStmt->fetchColumn();

        $dataStmt = $pdo->prepare("SELECT u.id, u.full_name, u.email, u.mobile, u.status, u.created_at, w.balance
            FROM users u LEFT JOIN wallets w ON u.id=w.user_id
            WHERE $whereClause ORDER BY u.created_at DESC LIMIT $perPage OFFSET $offset");
        $dataStmt->execute($params); $users = $dataStmt->fetchAll();

        foreach ($users as &$user) {
            $user['balance_formatted'] = formatCurrency((float)($user['balance'] ?? 0));
            $user['joined'] = date('d M Y', strtotime($user['created_at']));
        }

        jsonResponse([
            'success' => true,
            'data' => [
                'users' => $users,
                'pagination' => [
                    'current_page'  => $page,
                    'per_page'      => $perPage,
                    'total_records' => $total,
                    'total_pages'   => (int)ceil($total / $perPage)
                ]
            ]
        ]);

    } catch (PDOException $e) {
        error_log('Admin users GET error: ' . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Failed to fetch users.'], 500);
    }
}

// ── POST: Toggle user status ──────────────────────────────────────────────────
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input   = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $userId  = (int)($input['user_id'] ?? 0);
    $action  = $input['action'] ?? '';

    if (!$userId || !in_array($action, ['enable', 'disable'])) {
        jsonResponse(['success' => false, 'message' => 'Valid user_id and action (enable/disable) required.'], 422);
    }

    try {
        $newStatus = $action === 'enable' ? 'active' : 'inactive';
        $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $userId]);

        if ($stmt->rowCount() === 0) {
            jsonResponse(['success' => false, 'message' => 'User not found.'], 404);
        }

        jsonResponse(['success' => true, 'message' => "User account has been {$action}d successfully.", 'new_status' => $newStatus]);

    } catch (PDOException $e) {
        error_log('Admin users POST error: ' . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Failed to update user status.'], 500);
    }
} else {
    jsonResponse(['success' => false, 'message' => 'Method not allowed.'], 405);
}

