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

        // Token for React headers (HMAC Signed)
        $token = generateAuthToken((int)$user['id']);

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

            $token = generateAuthToken((int)$userId);

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

    public static function googleLogin(array $data) {
        $email = '';
        $fullName = '';
        $googleId = '';
        $avatar = null;

        // 1. If Google GSI / OAuth JWT credential token is provided
        if (!empty($data['credential'])) {
            $credential = trim((string)$data['credential']);
            $parts = explode('.', $credential);
            if (count($parts) === 3) {
                $payloadJson = base64_decode(strtr($parts[1], '-_', '+/'));
                $jwtPayload = json_decode($payloadJson, true);
                if (is_array($jwtPayload)) {
                    $email = trim(filter_var($jwtPayload['email'] ?? '', FILTER_SANITIZE_EMAIL));
                    $fullName = trim($jwtPayload['name'] ?? ($jwtPayload['given_name'] ?? 'Google User'));
                    $googleId = (string)($jwtPayload['sub'] ?? '');
                    $avatar = $jwtPayload['picture'] ?? null;
                }
            }

            // Optional: verify with Google public tokeninfo endpoint if network available
            if (!empty($credential) && function_exists('curl_init')) {
                $ch = curl_init('https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($credential));
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 4,
                    CURLOPT_SSL_VERIFYPEER => true
                ]);
                $verifyRes = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($httpCode === 200 && $verifyRes) {
                    $verifiedData = json_decode($verifyRes, true);
                    if (!empty($verifiedData['email'])) {
                        $email = trim(filter_var($verifiedData['email'], FILTER_SANITIZE_EMAIL));
                        $fullName = trim($verifiedData['name'] ?? $fullName);
                        $googleId = (string)($verifiedData['sub'] ?? $googleId);
                        $avatar = $verifiedData['picture'] ?? $avatar;
                    }
                }
            }
        }

        // 2. Fallback to direct payload (from client Google Auth or Sandbox chooser)
        if (empty($email)) {
            $email = trim(filter_var($data['email'] ?? '', FILTER_SANITIZE_EMAIL));
            $fullName = trim($data['name'] ?? ($data['full_name'] ?? 'Google User'));
            $googleId = (string)($data['google_id'] ?? ($data['sub'] ?? ('g_' . substr(md5($email), 0, 16))));
            $avatar = $data['avatar'] ?? ($data['picture'] ?? null);
        }

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonResponse(['success' => false, 'message' => 'Invalid Google authentication payload: email required.'], 400);
        }

        if (empty($fullName)) {
            $fullName = explode('@', $email)[0];
        }

        // 3. Find or Create User
        $user = null;
        if (!empty($googleId)) {
            $user = User::findByGoogleId($googleId);
        }
        if (!$user) {
            $user = User::findByEmail($email);
        }

        if ($user) {
            // Existing user
            if (strtolower($user['status']) !== 'active') {
                jsonResponse(['success' => false, 'message' => 'Your account is currently suspended. Please contact support.'], 403);
            }
            // Link Google ID and update avatar if not set
            User::linkGoogleId((int)$user['id'], $googleId, $avatar);
            $user = User::findById((int)$user['id']);
        } else {
            // Create new Google User
            try {
                $userId = User::createGoogleUser($fullName, $email, $googleId, $avatar);
                $user = User::findById($userId);
            } catch (Exception $e) {
                error_log("Google auth user creation error: " . $e->getMessage());
                jsonResponse(['success' => false, 'message' => 'Failed to initialize account with Google.'], 500);
            }
        }

        if (!$user) {
            jsonResponse(['success' => false, 'message' => 'Unable to complete Google authentication.'], 500);
        }

        // 4. Session & Token Generation
        if (session_status() === PHP_SESSION_NONE) session_start();
        session_regenerate_id(true);

        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_mobile'] = $user['mobile'];

        $token = generateAuthToken((int)$user['id']);

        jsonResponse([
            'success' => true,
            'message' => 'Signed in with Google successfully.',
            'data' => [
                'user' => [
                    'id' => (int)$user['id'],
                    'full_name' => $user['full_name'],
                    'email' => $user['email'],
                    'mobile' => $user['mobile'],
                    'avatar' => $user['avatar'] ?? $avatar,
                    'status' => $user['status'],
                    'upi_id' => $user['mobile'] ? ($user['mobile'] . '@payben') : ($user['id'] . '@payben')
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

