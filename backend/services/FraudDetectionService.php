<?php

require_once dirname(__DIR__) . '/config/database.php';

class FraudDetectionService {
    /**
     * Evaluate transaction risk in real time before execution
     */
    public static function evaluateTransactionRisk(int $userId, float $amount, string $txType, ?string $recipientVpa = null): array {
        $pdo = getDBConnection();
        $riskScore = 0;
        $triggeredRules = [];

        // Rule 1: High Velocity (Transactions in last 10 minutes)
        $stmtVel = $pdo->prepare("
            SELECT COUNT(*) AS tx_count 
            FROM transactions 
            WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)
        ");
        $stmtVel->execute([$userId]);
        $txCount = (int)($stmtVel->fetch()['tx_count'] ?? 0);
        if ($txCount >= 5) {
            $riskScore += 35;
            $triggeredRules[] = "High velocity: {$txCount} transactions executed in the last 10 minutes.";
        }

        // Rule 2: Repeated Failed Transactions (in last 1 hour)
        $stmtFail = $pdo->prepare("
            SELECT COUNT(*) AS fail_count 
            FROM transactions 
            WHERE user_id = ? AND status = 'FAILED' AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ");
        $stmtFail->execute([$userId]);
        $failCount = (int)($stmtFail->fetch()['fail_count'] ?? 0);
        if ($failCount >= 3) {
            $riskScore += 30;
            $triggeredRules[] = "Repeated failures: {$failCount} failed transactions in the last 60 minutes.";
        }

        // Rule 3: High-Value Threshold (> ₹25,000)
        if ($amount > 25000) {
            $riskScore += 25;
            $triggeredRules[] = "High-value transfer: ₹" . number_format($amount, 2) . " exceeds normal threshold of ₹25,000.";
        }

        // Rule 4: Rapid Duplicate Transfer to same recipient within 90 seconds
        if ($recipientVpa) {
            $stmtDup = $pdo->prepare("
                SELECT COUNT(*) AS dup_count 
                FROM transactions 
                WHERE user_id = ? AND mobile_or_consumer_number = ? AND amount = ? 
                  AND created_at >= DATE_SUB(NOW(), INTERVAL 90 SECOND)
            ");
            $stmtDup->execute([$userId, $recipientVpa, $amount]);
            $dupCount = (int)($stmtDup->fetch()['dup_count'] ?? 0);
            if ($dupCount >= 1) {
                $riskScore += 40;
                $triggeredRules[] = "Rapid duplicate: identical transfer to {$recipientVpa} within 90 seconds.";
            }
        }

        // Determine Risk Level
        if ($riskScore >= 70) {
            $riskLevel = 'HIGH';
        } elseif ($riskScore >= 30) {
            $riskLevel = 'MEDIUM';
        } else {
            $riskLevel = 'LOW';
        }

        // Log to fraud_alerts table if Medium or High risk
        if ($riskScore >= 30) {
            $stmtAlert = $pdo->prepare("
                INSERT INTO fraud_alerts (user_id, risk_score, risk_level, triggered_rules, status, created_at)
                VALUES (?, ?, ?, ?, 'PENDING', NOW())
            ");
            $stmtAlert->execute([
                $userId,
                $riskScore,
                $riskLevel,
                implode(" | ", $triggeredRules)
            ]);
        }

        return [
            'risk_score' => $riskScore,
            'risk_level' => $riskLevel,
            'is_allowed' => ($riskScore < 90), // Only block if extreme risk
            'triggered_rules' => $triggeredRules
        ];
    }

    public static function getAlerts(int $limit = 50, int $offset = 0): array {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            SELECT fa.id, fa.user_id, u.full_name, u.email, u.mobile,
                   fa.transaction_id, fa.risk_score, fa.risk_level, fa.triggered_rules,
                   fa.status, fa.created_at
            FROM fraud_alerts fa
            LEFT JOIN users u ON fa.user_id = u.id
            ORDER BY fa.id DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        return array_map(function($r) {
            return [
                'id' => (int)$r['id'],
                'user_id' => (int)$r['user_id'],
                'user_name' => $r['full_name'],
                'user_email' => $r['email'],
                'user_mobile' => $r['mobile'],
                'transaction_id' => $r['transaction_id'],
                'risk_score' => (int)$r['risk_score'],
                'risk_level' => $r['risk_level'],
                'triggered_rules' => $r['triggered_rules'],
                'status' => $r['status'],
                'created_at' => $r['created_at']
            ];
        }, $rows);
    }
}
