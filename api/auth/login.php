<?php
// POST /api/auth/login

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/config.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse([
        'success' => false,
        'message' => 'HTTP method not allowed. Only POST requests are accepted.'
    ], 405);
}

$rawInput = file_get_contents('php://input');
$jsonInput = json_decode($rawInput, true);
$input = is_array($jsonInput) ? array_merge($_POST, $jsonInput) : $_POST;

$email = trim(filter_var($input['email'] ?? '', FILTER_SANITIZE_EMAIL));
$password = (string)($input['password'] ?? '');

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse([
        'success' => false,
        'message' => 'A valid email address is required.'
    ], 400);
}

if (empty($password)) {
    jsonResponse([
        'success' => false,
        'message' => 'Password is required.'
    ], 400);
}

try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT u.id, u.full_name, u.email, u.mobile, u.password_hash, u.avatar, u.status, COALESCE(w.balance, 0.00) AS wallet_balance
        FROM users u
        LEFT JOIN wallets w ON u.id = w.user_id
        WHERE u.email = ?
        LIMIT 1
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        jsonResponse([
            'success' => false,
            'message' => 'Invalid email or password.'
        ], 401);
    }

    if (strtolower($user['status']) !== 'active') {
        jsonResponse([
            'success' => false,
            'message' => 'Account is suspended or inactive. Please contact support.'
        ], 403);
    }

        if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    session_regenerate_id(true);

    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['user_name'] = $user['full_name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_mobile'] = $user['mobile'];

    jsonResponse([
        'success' => true,
        'message' => 'Authentication successful.',
        'data' => [
            'user' => [
                'id' => (int)$user['id'],
                'full_name' => $user['full_name'],
                'email' => $user['email'],
                'mobile' => $user['mobile'],
                'avatar' => $user['avatar'] ?? null,
                'status' => $user['status']
            ],
            'wallet' => [
                'balance' => (float)$user['wallet_balance'],
                'formatted_balance' => formatCurrency((float)$user['wallet_balance'])
            ],
            'session_id' => session_id()
        ]
    ], 200);

} catch (PDOException $e) {
    error_log("Login API Error: " . $e->getMessage());
    jsonResponse([
        'success' => false,
        'message' => 'An internal database error occurred.'
    ], 500);
}
