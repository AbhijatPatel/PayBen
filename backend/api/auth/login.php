<?php

require_once dirname(__DIR__, 2) . '/controllers/AuthController.php';

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
AuthController::login($input);
