<?php

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/middleware/auth.php';
require_once dirname(__DIR__) . '/models/Operator.php';

class RechargeController {
    public static function getOperators() {
        $type = $_GET['type'] ?? 'mobile';
        if (!in_array($type, ['mobile', 'dth'])) $type = 'mobile';
        $operators = Operator::getAll($type);
        jsonResponse(['success' => true, 'data' => $operators]);
    }

    public static function getPlans() {
        $operatorId = (int)($_GET['operator_id'] ?? 0);
        $planType = $_GET['plan_type'] ?? null;
        if ($operatorId <= 0) {
            jsonResponse(['success' => false, 'message' => 'Valid operator_id required.'], 400);
        }
        $plans = Operator::getPlans($operatorId, $planType);
        jsonResponse(['success' => true, 'data' => $plans]);
    }

    public static function mobileRecharge(array $data) {
        $user = requireAuth();
        $mobile = preg_replace('/\D/', '', $data['mobile'] ?? '');
        $operatorId = (int)($data['operator_id'] ?? 0);
        $amount = (float)($data['amount'] ?? 0);
        $planId = isset($data['plan_id']) ? (int)$data['plan_id'] : null;

        if (strlen($mobile) !== 10 || !in_array($mobile[0], ['6','7','8','9'])) {
            jsonResponse(['success' => false, 'message' => 'Please provide a valid 10-digit Indian mobile number.'], 400);
        }
        if ($operatorId <= 0) {
            jsonResponse(['success' => false, 'message' => 'Please select an operator.'], 400);
        }
        if ($amount < 10) {
            jsonResponse(['success' => false, 'message' => 'Minimum recharge amount is ₹10.'], 400);
        }

        try {
            $result = Operator::recharge((int)$user['id'], 'mobile_recharge', $mobile, $operatorId, $amount, $planId);
            jsonResponse([
                'success' => true,
                'message' => 'Mobile recharge of ₹' . number_format($amount, 2) . ' successful!',
                'data' => $result
            ]);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public static function dthRecharge(array $data) {
        $user = requireAuth();
        $subscriberId = trim($data['subscriber_id'] ?? $data['customer_id'] ?? '');
        $operatorId = (int)($data['operator_id'] ?? 0);
        $amount = (float)($data['amount'] ?? 0);

        if (strlen($subscriberId) < 5) {
            jsonResponse(['success' => false, 'message' => 'Subscriber ID / VC Number must be at least 5 characters.'], 400);
        }
        if ($operatorId <= 0) {
            jsonResponse(['success' => false, 'message' => 'Please select your DTH operator.'], 400);
        }
        if ($amount < 50) {
            jsonResponse(['success' => false, 'message' => 'Minimum DTH recharge amount is ₹50.'], 400);
        }

        try {
            $result = Operator::recharge((int)$user['id'], 'dth_recharge', $subscriberId, $operatorId, $amount);
            jsonResponse([
                'success' => true,
                'message' => 'DTH recharge of ₹' . number_format($amount, 2) . ' successful!',
                'data' => $result
            ]);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }
}

