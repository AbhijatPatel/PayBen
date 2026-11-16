<?php

require_once dirname(__DIR__) . '/config/database.php';

class OtpService {
    /**
     * Generate and issue a new secure OTP challenge
     */
    public static function sendOtp(string $identifier, string $purpose = 'LOGIN', ?int $userId = null): array {
        $identifier = trim(strtolower($identifier));
        $purpose = strtoupper(trim($purpose));

        $pdo = getDBConnection();

        // 1. Check cooldown (60 seconds)
        $stmtCheck = $pdo->prepare("
            SELECT id, resend_after, created_at 
            FROM otp_challenges 
            WHERE identifier = ? AND purpose = ? AND status = 'PENDING'
            ORDER BY id DESC LIMIT 1
        ");
        $stmtCheck->execute([$identifier, $purpose]);
        $recent = $stmtCheck->fetch();

        if ($recent && strtotime($recent['resend_after']) > time()) {
            $waitSeconds = strtotime($recent['resend_after']) - time();
            throw new RuntimeException("Please wait {$waitSeconds} seconds before requesting a new OTP.");
        }

        // 2. Generate random 6-digit OTP
        $otpCode = str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        $otpHash = password_hash($otpCode, PASSWORD_BCRYPT);
        $expiresAt = date('Y-m-d H:i:s', time() + (5 * 60)); // 5 minutes
        $resendAfter = date('Y-m-d H:i:s', time() + 60); // 60 seconds

        // Invalidate older pending OTPs for this identifier and purpose
        $stmtExpire = $pdo->prepare("UPDATE otp_challenges SET status = 'EXPIRED' WHERE identifier = ? AND purpose = ? AND status = 'PENDING'");
        $stmtExpire->execute([$identifier, $purpose]);

        // Insert new challenge
        $stmtInsert = $pdo->prepare("
            INSERT INTO otp_challenges (
                user_id, identifier, purpose, otp_hash, attempts, max_attempts, status, resend_after, expires_at, created_at
            ) VALUES (?, ?, ?, ?, 0, 5, 'PENDING', ?, ?, NOW())
        ");
        $stmtInsert->execute([$userId, $identifier, $purpose, $otpHash, $resendAfter, $expiresAt]);
        $challengeId = (int)$pdo->lastInsertId();

        $response = [
            'challenge_id' => $challengeId,
            'identifier' => $identifier,
            'purpose' => $purpose,
            'expires_in_seconds' => 300,
            'cooldown_seconds' => 60
        ];

        // Free Sandbox / Development mode transparency:
        // Always provide OTP for test environments so no paid SMS is required
        if (getenv('APP_ENV') !== 'production' || OTP_PROVIDER === 'local') {
            $response['sandbox_otp'] = $otpCode;
            $response['message'] = "Sandbox OTP generated: {$otpCode} (Valid for 5 minutes).";
        } else {
            $response['message'] = "OTP sent successfully to {$identifier}.";
        }

        return $response;
    }

    /**
     * Verify an entered OTP against the active challenge
     */
    public static function verifyOtp(string $identifier, string $purpose, string $enteredOtp): bool {
        $identifier = trim(strtolower($identifier));
        $purpose = strtoupper(trim($purpose));
        $enteredOtp = trim($enteredOtp);

        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            SELECT id, otp_hash, attempts, max_attempts, expires_at 
            FROM otp_challenges 
            WHERE identifier = ? AND purpose = ? AND status = 'PENDING'
            ORDER BY id DESC LIMIT 1
        ");
        $stmt->execute([$identifier, $purpose]);
        $challenge = $stmt->fetch();

        if (!$challenge) {
            throw new RuntimeException("No active OTP request found or OTP has expired.");
        }

        if (strtotime($challenge['expires_at']) < time()) {
            $stmtExp = $pdo->prepare("UPDATE otp_challenges SET status = 'EXPIRED' WHERE id = ?");
            $stmtExp->execute([(int)$challenge['id']]);
            throw new RuntimeException("OTP has expired. Please request a new one.");
        }

        if ((int)$challenge['attempts'] >= (int)$challenge['max_attempts']) {
            $stmtFail = $pdo->prepare("UPDATE otp_challenges SET status = 'FAILED' WHERE id = ?");
            $stmtFail->execute([(int)$challenge['id']]);
            throw new RuntimeException("Maximum verification attempts exceeded. Please request a new OTP.");
        }

        // Increment attempts
        $newAttempts = (int)$challenge['attempts'] + 1;
        $stmtInc = $pdo->prepare("UPDATE otp_challenges SET attempts = ? WHERE id = ?");
        $stmtInc->execute([$newAttempts, (int)$challenge['id']]);

        // Verify hash
        if (password_verify($enteredOtp, $challenge['otp_hash'])) {
            $stmtSuccess = $pdo->prepare("UPDATE otp_challenges SET status = 'VERIFIED' WHERE id = ?");
            $stmtSuccess->execute([(int)$challenge['id']]);
            return true;
        }

        $remaining = (int)$challenge['max_attempts'] - $newAttempts;
        throw new RuntimeException("Invalid OTP. {$remaining} attempts remaining.");
    }
}
