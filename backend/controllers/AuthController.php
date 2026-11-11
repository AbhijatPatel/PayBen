<?php

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/middleware/auth.php';
require_once dirname(__DIR__) . '/models/User.php';

class AuthController {
    public static function login(array $data) {
        $email = trim(filter_var($data['email'] ?? '', FILTER_SANITIZE_EMAIL));
        $password = (string)($data['password'] ?? '');

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonResponse(['success' => false, 'message' => 'Please provide a valid email address.'], 400);
        }
        if (empty($password)) {
            jsonResponse(['success' => false, 'message' => 'Password is required.'], 400);
        }

        $user = User::findByEmail($email);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            jsonResponse(['success' => false, 'message' => 'Invalid email address or password.'], 401);
        }

        if (strtolower($user['status']) !== 'active') {
            jsonResponse(['success' => false, 'message' => 'Your account is currently suspended. Please contact support.'], 403);
        }

        if (session_status() === PHP_SESSION_NONE) session_start();
        session_regenerate_id(true);

        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_mobile'] = $user['mobile'];

        // Token for React headers
        $token = base64_encode($user['id'] . ':' . md5($user['password_hash']));

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
                    'status' => $user['status'],
                    'upi_id' => $user['mobile'] . '@payben'
                ],
                'wallet' => [
                    'balance' => (float)$user['wallet_balance'],
                    'formatted_balance' => formatCurrency((float)$user['wallet_balance'])
                ],
                'token' => $token,
                'session_id' => session_id()
            ]
        ]);
    }

    public static function register(array $data) {
        $fullName = trim($data['full_name'] ?? '');
        $email = trim(filter_var($data['email'] ?? '', FILTER_SANITIZE_EMAIL));
        $mobile = preg_replace('/\D/', '', $data['mobile'] ?? '');
        $password = (string)($data['password'] ?? '');

        if (strlen($fullName) < 3) {
            jsonResponse(['success' => false, 'message' => 'Full name must be at least 3 characters.'], 400);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonResponse(['success' => false, 'message' => 'Please provide a valid email address.'], 400);
        }
        if (strlen($mobile) !== 10 || !in_array($mobile[0], ['6','7','8','9'])) {
            jsonResponse(['success' => false, 'message' => 'Mobile number must be a valid 10-digit Indian number.'], 400);
        }
        if (strlen($password) < 6) {
            jsonResponse(['success' => false, 'message' => 'Password must be at least 6 characters.'], 400);
        }

        if (User::findByEmail($email)) {
            jsonResponse(['success' => false, 'message' => 'Email address is already registered.'], 409);
        }

        try {
            $userId = User::create($fullName, $email, $mobile, $password);
            $user = User::findById($userId);

            if (session_status() === PHP_SESSION_NONE) session_start();
            session_regenerate_id(true);

            $_SESSION['user_id'] = $userId;
            $_SESSION['user_name'] = $fullName;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_mobile'] = $mobile;

            $token = base64_encode($userId . ':' . md5($password));

            jsonResponse([
                'success' => true,
                'message' => 'Registration successful! ₹500 welcome bonus added to your wallet.',
                'data' => [
                    'user' => [
                        'id' => $userId,
                        'full_name' => $fullName,
                        'email' => $email,
                        'mobile' => $mobile,
                        'status' => 'active',
                        'upi_id' => $mobile . '@payben'
                    ],
                    'wallet' => [
                        'balance' => 500.00,
                        'formatted_balance' => formatCurrency(500.00)
                    ],
                    'token' => $token,
                    'session_id' => session_id()
                ]
            ], 201);
        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            jsonResponse(['success' => false, 'message' => 'Registration failed. Mobile or email may already be in use.'], 500);
        }
    }

    public static function me() {
        $user = getAuthUser();
        if (!$user) {
            jsonResponse(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        jsonResponse([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => (int)$user['id'],
                    'full_name' => $user['full_name'],
                    'email' => $user['email'],
                    'mobile' => $user['mobile'],
                    'avatar' => $user['avatar'] ?? null,
                    'status' => $user['status'],
                    'upi_id' => $user['mobile'] . '@payben'
                ],
                'wallet' => [
                    'balance' => (float)$user['wallet_balance'],
                    'formatted_balance' => formatCurrency((float)$user['wallet_balance'])
                ]
            ]
        ]);
    }

    public static function logout() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
        }
        session_destroy();
        jsonResponse(['success' => true, 'message' => 'Logged out successfully.']);
    }
}
