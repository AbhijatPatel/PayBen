<?php

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/middleware/auth.php';
require_once dirname(__DIR__) . '/models/User.php';
require_once dirname(__DIR__) . '/services/EmailService.php';

class AuthController {
    public static function login(array $data) {
        $email = strtolower(trim((string)($data['email'] ?? '')));
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
        $fullName = trim((string)($data['full_name'] ?? ''));
        $email = strtolower(trim((string)($data['email'] ?? '')));
        $mobile = preg_replace('/\D/', '', (string)($data['mobile'] ?? ''));
        $password = (string)($data['password'] ?? '');
        $confirmPassword = (string)($data['confirm_password'] ?? '');

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
        if ($confirmPassword === '' || !hash_equals($password, $confirmPassword)) {
            jsonResponse(['success' => false, 'message' => 'Password confirmation does not match.'], 400);
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
        } catch (Throwable $e) {
            error_log("Registration error: " . $e->getMessage());
            jsonResponse(['success' => false, 'message' => 'Registration failed. Mobile or email may already be in use.'], 500);
        }
    }

    public static function requestPasswordReset(array $data) {
        $email = strtolower(trim((string)($data['email'] ?? '')));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonResponse(['success' => false, 'message' => 'Please provide a valid email address.'], 400);
        }

        $environment = strtolower(trim((string)getenv('APP_ENV')));
        $isDevelopment = in_array($environment, ['development', 'local', 'test'], true);
        $emailConfigured = EMAIL_PROVIDER === 'brevo' && trim(BREVO_API_KEY) !== '';
        if (!$isDevelopment && !$emailConfigured) {
            jsonResponse([
                'success' => false,
                'message' => 'Password recovery is not configured. Please contact your PayBen administrator.'
            ], 503);
        }

        try {
            $user = User::findByEmail($email);
            $response = [
                'success' => true,
                'message' => 'If an active PayBen account matches this email, reset instructions will be sent.',
                'data' => []
            ];
            if (!$user || strtolower($user['status']) !== 'active') {
                jsonResponse($response);
            }
            if (User::hasRecentPasswordReset($email)) {
                jsonResponse($response);
            }

            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', time() + 1800);
            if (!User::storePasswordResetToken($email, hash('sha256', $token), $expiresAt)) {
                throw new RuntimeException('Unable to store password reset token.');
            }

            $resetUrl = rtrim(FRONTEND_URL, '/') . '/reset-password#token='
                . rawurlencode($token) . '&email=' . rawurlencode($email);
            if ($emailConfigured) {
                $safeName = htmlspecialchars($user['full_name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $safeUrl = htmlspecialchars($resetUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $emailHtml = '<p>Hello ' . $safeName . ',</p>'
                    . '<p>Use the link below to reset your PayBen password. This link expires in 30 minutes and can only be used once.</p>'
                    . '<p><a href="' . $safeUrl . '">Reset your password</a></p>'
                    . '<p>If you did not request this, you can ignore this email.</p>';
                if (!EmailService::sendEmail($user['email'], $user['full_name'], 'Reset your PayBen password', $emailHtml)) {
                    error_log('Password reset email delivery failed.');
                    User::deletePasswordResetToken($email);
                    jsonResponse([
                        'success' => false,
                        'message' => 'Unable to send password reset instructions right now. Please try again later.'
                    ], 503);
                }
            } else {
                $response['message'] = 'Development-only password reset link created. It expires in 30 minutes.';
                $response['data']['development_reset_url'] = $resetUrl;
            }

            jsonResponse($response);
        } catch (Throwable $e) {
            error_log('Password reset request error: ' . $e->getMessage());
            jsonResponse(['success' => false, 'message' => 'Unable to process the password reset request right now.'], 500);
        }
    }

    public static function resetPassword(array $data) {
        $email = strtolower(trim((string)($data['email'] ?? '')));
        $token = trim((string)($data['token'] ?? ''));
        $password = (string)($data['password'] ?? '');
        $confirmPassword = (string)($data['confirm_password'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonResponse(['success' => false, 'message' => 'Please provide a valid email address.'], 400);
        }
        if (!preg_match('/^[a-f0-9]{64}$/i', $token)) {
            jsonResponse(['success' => false, 'message' => 'This password reset link is invalid or has expired.'], 400);
        }
        if (strlen($password) < 6) {
            jsonResponse(['success' => false, 'message' => 'Password must be at least 6 characters.'], 400);
        }
        if (!hash_equals($password, $confirmPassword)) {
            jsonResponse(['success' => false, 'message' => 'Password confirmation does not match.'], 400);
        }

        try {
            $updated = User::resetPasswordWithToken($email, hash('sha256', $token), $password);
            if (!$updated) {
                jsonResponse(['success' => false, 'message' => 'This password reset link is invalid or has expired.'], 400);
            }
            jsonResponse([
                'success' => true,
                'message' => 'Your password has been updated. Sign in with your new password.',
                'data' => []
            ]);
        } catch (Throwable $e) {
            error_log('Password reset completion error: ' . $e->getMessage());
            jsonResponse(['success' => false, 'message' => 'Unable to update your password right now. Please try again.'], 500);
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

    private static function fetchGoogleCertificates(): ?array {
        if (function_exists('apcu_fetch') && filter_var(ini_get('apc.enabled'), FILTER_VALIDATE_BOOLEAN)) {
            $certificates = apcu_fetch('payben_google_id_token_certificates', $cached);
            if ($cached && is_array($certificates)) {
                return $certificates;
            }
        }

        $cacheMaxAge = 0;
        $curl = curl_init('https://www.googleapis.com/oauth2/v1/certs');
        $curlOptions = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_HEADERFUNCTION => static function ($handle, string $header) use (&$cacheMaxAge): int {
                if (preg_match('/^Cache-Control:.*?max-age=(\d+)/i', $header, $matches)) {
                    $cacheMaxAge = (int)$matches[1];
                }
                return strlen($header);
            }
        ];
        $caBundle = trim((string)getenv('GOOGLE_CA_BUNDLE'));
        if ($caBundle !== '') {
            if (!is_file($caBundle) || !is_readable($caBundle)) {
                error_log('Configured Google CA bundle is not readable.');
                return null;
            }
            $curlOptions[CURLOPT_CAINFO] = $caBundle;
        }
        curl_setopt_array($curl, $curlOptions);
        $response = curl_exec($curl);
        $status = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if ($response === false || $status !== 200) {
            error_log('Unable to fetch Google signing certificates: ' . $error);
            return null;
        }

        $certificates = json_decode($response, true);
        if (!is_array($certificates) || !$certificates) {
            error_log('Google signing certificate response was invalid.');
            return null;
        }
        if ($cacheMaxAge > 0 && function_exists('apcu_store') && filter_var(ini_get('apc.enabled'), FILTER_VALIDATE_BOOLEAN)) {
            apcu_store('payben_google_id_token_certificates', $certificates, $cacheMaxAge);
        }
        return $certificates;
    }

    private static function decodeGoogleTokenPart(string $part): ?string {
        $base64 = strtr($part, '-_', '+/');
        $decoded = base64_decode($base64 . str_repeat('=', (4 - strlen($base64) % 4) % 4), true);
        return $decoded === false ? null : $decoded;
    }

    private static function verifyGoogleCredential(string $credential, string $clientId): array {
        if (strlen($credential) > 8192) {
            return ['status' => 'invalid'];
        }

        $parts = explode('.', $credential);
        if (count($parts) !== 3) {
            return ['status' => 'invalid'];
        }
        $headerJson = self::decodeGoogleTokenPart($parts[0]);
        $payloadJson = self::decodeGoogleTokenPart($parts[1]);
        $signature = self::decodeGoogleTokenPart($parts[2]);
        $header = $headerJson === null ? null : json_decode($headerJson, true);
        $claims = $payloadJson === null ? null : json_decode($payloadJson, true);
        if (
            !is_array($header)
            || ($header['alg'] ?? '') !== 'RS256'
            || !is_string($header['kid'] ?? null)
            || $header['kid'] === ''
            || !is_array($claims)
            || $signature === null
        ) {
            return ['status' => 'invalid'];
        }

        $certificates = self::fetchGoogleCertificates();
        if ($certificates === null) {
            return ['status' => 'unavailable'];
        }
        $certificate = $certificates[$header['kid']] ?? null;
        $certificateDetails = is_string($certificate) ? openssl_x509_parse($certificate) : false;
        if (
            !is_array($certificateDetails)
            || !isset($certificateDetails['validFrom_time_t'], $certificateDetails['validTo_time_t'])
            || $certificateDetails['validFrom_time_t'] > time()
            || $certificateDetails['validTo_time_t'] < time()
        ) {
            return ['status' => 'invalid'];
        }
        $publicKey = openssl_pkey_get_public($certificate);
        if ($publicKey === false) {
            return ['status' => 'invalid'];
        }
        $keyDetails = openssl_pkey_get_details($publicKey);
        $signatureIsValid = is_array($keyDetails)
            && ($keyDetails['type'] ?? null) === OPENSSL_KEYTYPE_RSA
            && ($keyDetails['bits'] ?? 0) >= 2048
            && openssl_verify($parts[0] . '.' . $parts[1], $signature, $publicKey, OPENSSL_ALGO_SHA256) === 1;
        openssl_free_key($publicKey);
        if (!$signatureIsValid) {
            return ['status' => 'invalid'];
        }

        $audience = $claims['aud'] ?? null;
        $audienceMatches = is_array($audience)
            ? in_array($clientId, $audience, true)
            : is_string($audience) && hash_equals($clientId, $audience);
        $authorizedParty = $claims['azp'] ?? null;
        $authorizedPartyMatches = $authorizedParty === null
            || (is_string($authorizedParty) && hash_equals($clientId, $authorizedParty));
        $issuerMatches = in_array($claims['iss'] ?? '', ['accounts.google.com', 'https://accounts.google.com'], true);
        $issuedAt = $claims['iat'] ?? null;
        $expiresAt = $claims['exp'] ?? null;
        $timeClaimsValid = is_numeric($issuedAt)
            && (int)$issuedAt <= time() + 60
            && is_numeric($expiresAt)
            && (int)$expiresAt > time();
        $emailClaim = $claims['email'] ?? null;
        $subject = $claims['sub'] ?? null;
        if (!is_string($emailClaim) || !is_string($subject) || $subject === '') {
            return ['status' => 'invalid'];
        }
        $email = strtolower(trim($emailClaim));
        $emailVerified = in_array($claims['email_verified'] ?? false, [true, 'true', 1, '1'], true);

        if (
            !$audienceMatches
            || !$authorizedPartyMatches
            || !$issuerMatches
            || !$timeClaimsValid
            || !$emailVerified
            || !filter_var($email, FILTER_VALIDATE_EMAIL)
        ) {
            return ['status' => 'invalid'];
        }

        $claims['email'] = $email;
        return ['status' => 'valid', 'claims' => $claims];
    }

    public static function googleLogin(array $data) {
        $clientId = trim((string)getenv('GOOGLE_CLIENT_ID'));
        if ($clientId === '') {
            $message = getenv('APP_ENV') === 'production'
                ? 'Google sign-in is currently unavailable. Please use email and password.'
                : 'Google sign-in is not configured. Set GOOGLE_CLIENT_ID in the backend environment.';
            jsonResponse(['success' => false, 'message' => $message], 503);
        }

        $credential = trim((string)($data['credential'] ?? ''));
        if ($credential === '') {
            jsonResponse(['success' => false, 'message' => 'Google did not provide an identity token. Please try again.'], 400);
        }
        if (!function_exists('curl_init') || !function_exists('openssl_verify')) {
            error_log('Google sign-in requires the PHP cURL and OpenSSL extensions.');
            jsonResponse(['success' => false, 'message' => 'Google sign-in is temporarily unavailable. Please use email and password.'], 503);
        }

        $verification = self::verifyGoogleCredential($credential, $clientId);
        if ($verification['status'] === 'unavailable') {
            jsonResponse(['success' => false, 'message' => 'Google sign-in could not be verified right now. Please try again.'], 503);
        }
        if ($verification['status'] !== 'valid') {
            jsonResponse(['success' => false, 'message' => 'Google sign-in could not be verified. Please try again.'], 401);
        }

        $claims = $verification['claims'];
        $email = $claims['email'];
        $googleId = (string)$claims['sub'];
        $fullName = trim((string)($claims['name'] ?? $claims['given_name'] ?? ''));
        if ($fullName === '') {
            $fullName = explode('@', $email)[0];
        }
        $avatar = isset($claims['picture']) && is_string($claims['picture']) ? $claims['picture'] : null;

        $user = User::findByGoogleId($googleId);
        $isLinkingExistingAccount = false;
        if ($user && strtolower($user['email']) !== $email) {
            jsonResponse(['success' => false, 'message' => 'This Google account is already linked to a different PayBen email.'], 409);
        }

        if (!$user) {
            $existingEmailUser = User::findByEmail($email);
            if ($existingEmailUser) {
                $authenticatedUser = getAuthUser();
                if (
                    !$authenticatedUser
                    || (int)$authenticatedUser['id'] !== (int)$existingEmailUser['id']
                    || !empty($existingEmailUser['google_id'])
                ) {
                    jsonResponse([
                        'success' => false,
                        'message' => 'A PayBen account already uses this email. Sign in to that account first, then link Google while signed in.'
                    ], 409);
                }

                if (!User::linkGoogleId((int)$existingEmailUser['id'], $googleId, $avatar)) {
                    jsonResponse(['success' => false, 'message' => 'Unable to link this Google account. Please try again.'], 409);
                }
                $user = User::findByGoogleId($googleId);
                if (!$user) {
                    jsonResponse(['success' => false, 'message' => 'Unable to link this Google account. Please try again.'], 409);
                }
                $isLinkingExistingAccount = true;
            } else {
                try {
                    $userId = User::createGoogleUser($fullName, $email, $googleId, $avatar);
                    $user = User::findById($userId);
                } catch (Exception $e) {
                    error_log('Google auth user creation error: ' . $e->getMessage());
                    jsonResponse(['success' => false, 'message' => 'Unable to create a PayBen account with Google. Please try again.'], 500);
                }
            }
        }

        if (!$user) {
            jsonResponse(['success' => false, 'message' => 'Unable to complete Google authentication.'], 500);
        }
        if (strtolower($user['status']) !== 'active') {
            jsonResponse(['success' => false, 'message' => 'Your account is currently suspended. Please contact support.'], 403);
        }

        // Start a normal PayBen session and issue its existing API token.
        if (session_status() === PHP_SESSION_NONE) session_start();
        session_regenerate_id(true);

        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_mobile'] = $user['mobile'];

        $token = generateAuthToken((int)$user['id']);

        jsonResponse([
            'success' => true,
            'message' => $isLinkingExistingAccount
                ? 'Google account linked and signed in successfully.'
                : 'Signed in with Google successfully.',
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

