<?php
// auth/register

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/config.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');

// Restrict to POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse([
        'success' => false,
        'message' => 'HTTP method not allowed. Only POST requests are accepted.'
    ], 405);
}

// Parse request payload (supports JSON body and form-data)
$rawInput = file_get_contents('php://input');
$jsonInput = json_decode($rawInput, true);
$input = is_array($jsonInput) ? array_merge($_POST, $jsonInput) : $_POST;

$fullName = trim((string)($input['full_name'] ?? ''));
$email = strtolower(trim((string)($input['email'] ?? '')));
$mobile = preg_replace('/\D/', '', (string)($input['mobile'] ?? ''));
$password = (string)($input['password'] ?? '');
$confirmPassword = (string)($input['confirm_password'] ?? '');

$errors = [];
if (empty($fullName)) {
    $errors['full_name'] = 'Full name is required.';
} elseif (mb_strlen($fullName) < 3 || mb_strlen($fullName) > 100) {
    $errors['full_name'] = 'Full name must be between 3 and 100 characters.';
}
if (empty($email)) {
    $errors['email'] = 'Email address is required.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'A valid email address is required.';
}
if (empty($mobile)) {
    $errors['mobile'] = 'Mobile number is required.';
} elseif (!preg_match('/^[6-9]\d{9}$/', $mobile)) {
    $errors['mobile'] = 'Please enter a valid 10-digit Indian mobile number.';
}
if (empty($password)) {
    $errors['password'] = 'Password is required.';
} elseif (strlen($password) < 6) {
    $errors['password'] = 'Password must be at least 6 characters long.';
}
if (empty($confirmPassword)) {
    $errors['confirm_password'] = 'Please confirm your password.';
} elseif ($password !== $confirmPassword) {
    $errors['confirm_password'] = 'Passwords do not match.';
}

if (!empty($errors)) {
    jsonResponse([
        'success' => false,
        'message' => reset($errors),
        'errors'  => $errors
    ], 400);
}

try {
    $pdo = getDBConnection();

    // Check for duplicate email or mobile
    $checkStmt = $pdo->prepare("SELECT id, email, mobile FROM users WHERE email = ? OR mobile = ? LIMIT 1");
    $checkStmt->execute([$email, $mobile]);
    $existing = $checkStmt->fetch();

    if ($existing) {
        if (strcasecmp($existing['email'], $email) === 0) {
            jsonResponse([
                'success' => false,
                'message' => 'An account with this email address is already registered.'
            ], 409);
        }
        if ($existing['mobile'] === $mobile) {
            jsonResponse([
                'success' => false,
                'message' => 'An account with this mobile number is already registered.'
            ], 409);
        }
    }

    // Atomic transaction: Insert user and initialize wallet
    $pdo->beginTransaction();

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $userStmt = $pdo->prepare("
        INSERT INTO users (full_name, email, mobile, password_hash, status, created_at)
        VALUES (?, ?, ?, ?, 'active', NOW())
    ");
    $userStmt->execute([$fullName, $email, $mobile, $passwordHash]);
    $newUserId = (int)$pdo->lastInsertId();

    // Create user wallet with 0.00 initial balance
    $walletStmt = $pdo->prepare("
        INSERT INTO wallets (user_id, balance, created_at)
        VALUES (?, 0.00, NOW())
    ");
    $walletStmt->execute([$newUserId]);
    $newWalletId = (int)$pdo->lastInsertId();

    $pdo->commit();

    // Start session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    session_regenerate_id(true);
    $_SESSION['user_id'] = $newUserId;
    $_SESSION['user_name'] = $fullName;
    $_SESSION['user_email'] = $email;
    $_SESSION['user_mobile'] = $mobile;

    jsonResponse([
        'success' => true,
        'message' => 'Registration successful! Welcome to PayBen.',
        'data'    => [
            'user' => [
                'id'         => $newUserId,
                'full_name'  => $fullName,
                'email'      => $email,
                'mobile'     => $mobile,
                'status'     => 'active',
                'created_at' => date('Y-m-d H:i:s')
            ],
            'wallet' => [
                'id'                => $newWalletId,
                'balance'           => 0.00,
                'formatted_balance' => formatCurrency(0.00)
            ],
            'session_id' => session_id()
        ]
    ], 201);

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Register API Error: " . $e->getMessage());
    jsonResponse([
        'success' => false,
        'message' => 'Database error occurred during registration. Please try again.'
    ], 500);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Register API Unexpected Error: " . $e->getMessage());
    jsonResponse([
        'success' => false,
        'message' => 'An unexpected server error occurred.'
    ], 500);
}
