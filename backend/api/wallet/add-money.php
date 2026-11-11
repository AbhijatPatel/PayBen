<?php

require_once dirname(__DIR__, 2) . '/controllers/WalletController.php';

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
WalletController::addMoney($input);

