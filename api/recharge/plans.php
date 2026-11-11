<?php
// recharge/plans

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/config.php';
require_once dirname(__DIR__, 2) . '/config/database.php';

// Ensure proper JSON headers
header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');

// Restrict to GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse([
        'success' => false,
        'message' => 'HTTP method not allowed. Only GET requests are accepted.'
    ], 405);
}

// Validate operator_id parameter
$operatorId = filter_input(INPUT_GET, 'operator_id', FILTER_VALIDATE_INT);

if (!$operatorId || $operatorId <= 0) {
    jsonResponse([
        'success' => false,
        'message' => 'A valid positive numeric operator_id parameter is required.'
    ], 400);
}

try {
    $pdo = getDBConnection();
    $opStmt = $pdo->prepare("SELECT id, name, code, type, status FROM operators WHERE id = ? LIMIT 1");
    $opStmt->execute([$operatorId]);
    $operator = $opStmt->fetch();

    if (!$operator) {
        jsonResponse([
            'success' => false,
            'message' => 'The requested operator was not found in our directory.'
        ], 404);
    }

    if ($operator['status'] !== 'active') {
        jsonResponse([
            'success' => false,
            'message' => 'The selected operator is currently disabled or inactive.'
        ], 400);
    }
    $planStmt = $pdo->prepare("
        SELECT 
            id, 
            operator_id, 
            plan_name, 
            amount, 
            validity, 
            data_limit, 
            description, 
            plan_type, 
            status, 
            created_at
        FROM recharge_plans 
        WHERE operator_id = ? AND status = 'active' 
        ORDER BY amount ASC
    ");
    $planStmt->execute([$operatorId]);
    $plans = $planStmt->fetchAll();
    $formattedPlans = array_map(function ($plan) {
        $amountFloat = (float)$plan['amount'];
        return [
            'id'               => (int)$plan['id'],
            'operator_id'      => (int)$plan['operator_id'],
            'plan_name'        => htmlspecialchars_decode($plan['plan_name'], ENT_QUOTES),
            'amount'           => number_format($amountFloat, 2, '.', ''),
            'amount_numeric'   => $amountFloat,
            'formatted_amount' => formatCurrency($amountFloat),
            'validity'         => htmlspecialchars_decode($plan['validity'], ENT_QUOTES),
            'data_limit'       => $plan['data_limit'] ? htmlspecialchars_decode($plan['data_limit'], ENT_QUOTES) : 'N/A',
            'description'      => htmlspecialchars_decode($plan['description'] ?? '', ENT_QUOTES),
            'plan_type'        => $plan['plan_type'] ?? 'prepaid',
            'status'           => $plan['status']
        ];
    }, $plans);

    // Return standard PayBen JSON response
    jsonResponse([
        'success'   => true,
        'operator'  => [
            'id'    => (int)$operator['id'],
            'name'  => $operator['name'],
            'code'  => $operator['code'],
            'type'  => $operator['type']
        ],
        'count'     => count($formattedPlans),
        'plans'     => $formattedPlans,
        'data'      => $formattedPlans // Alias for versatile frontend consumers
    ], 200);

} catch (PDOException $e) {
    error_log("Plans API Database Error: " . $e->getMessage());
    jsonResponse([
        'success' => false,
        'message' => 'A database error occurred while retrieving plans. Please try again later.'
    ], 500);
} catch (Throwable $e) {
    error_log("Plans API Unexpected Error: " . $e->getMessage());
    jsonResponse([
        'success' => false,
        'message' => 'An unexpected server error occurred.'
    ], 500);
}
