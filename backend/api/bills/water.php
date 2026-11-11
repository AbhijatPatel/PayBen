<?php

require_once dirname(__DIR__, 2) . '/controllers/BillController.php';

$type = 'water';
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $action = $_GET['action'] ?? 'providers';
    if ($action === 'providers') {
        BillController::getProviders($type);
    } else {
        BillController::fetchBill($type, $_GET);
    }
} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    BillController::payBill($type, $input);
}
