<?php

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/middleware/auth.php';
require_once dirname(__DIR__) . '/models/UtilityBill.php';

class BillController {
    public static function getProviders(string $type) {
        $providers = UtilityBill::getProviders($type);
        jsonResponse(['success' => true, 'data' => $providers]);
    }

    public static function fetchBill(string $type, array $params) {
        $providerId = (int)($params['provider_id'] ?? 0);
        $consumerNumber = trim($params['consumer_number'] ?? '');

        if ($providerId <= 0) {
            jsonResponse(['success' => false, 'message' => 'Please select a provider.'], 400);
        }
        if (strlen($consumerNumber) < 4) {
            jsonResponse(['success' => false, 'message' => 'Valid consumer number is required.'], 400);
        }

        $bill = UtilityBill::fetchBill($providerId, $consumerNumber);
        if (!$bill) {
            jsonResponse(['success' => false, 'message' => 'No pending bill found for this consumer number.'], 404);
        }

        jsonResponse([
            'success' => true,
            'message' => 'Bill details fetched successfully.',
            'data' => $bill
        ]);
    }

    public static function payBill(string $type, array $data) {
        $user = requireAuth();
        $providerId = (int)($data['provider_id'] ?? 0);
        $consumerNumber = trim($data['consumer_number'] ?? '');
        $amount = (float)($data['amount'] ?? 0);

        if ($providerId <= 0) {
            jsonResponse(['success' => false, 'message' => 'Please select a utility provider.'], 400);
        }
        if (strlen($consumerNumber) < 4) {
            jsonResponse(['success' => false, 'message' => 'Valid consumer number is required.'], 400);
        }
        if ($amount <= 0) {
            jsonResponse(['success' => false, 'message' => 'Bill payment amount must be greater than zero.'], 400);
        }

        try {
            $result = UtilityBill::payBill((int)$user['id'], $type, $providerId, $consumerNumber, $amount);
            jsonResponse([
                'success' => true,
                'message' => ucfirst($type) . ' bill of ₹' . number_format($amount, 2) . ' paid successfully!',
                'data' => $result
            ]);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }
}
