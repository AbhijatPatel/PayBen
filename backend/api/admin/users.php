<?php

require_once dirname(__DIR__, 2) . '/controllers/AdminController.php';

$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    AdminController::toggleUserStatus($input);
} else {
    AdminController::users();
}

