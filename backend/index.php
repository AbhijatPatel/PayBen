<?php

require_once __DIR__ . '/config/config.php';

jsonResponse([
    'status' => 'online',
    'service' => 'PayBen REST API Engine',
    'version' => APP_VERSION,
    'timestamp' => date('c'),
    'endpoints' => [
        'auth' => ['login', 'register', 'logout', 'me'],
        'wallet' => ['balance', 'add-money', 'transfer', 'scratch-card', 'history'],
        'recharge' => ['operators', 'plans', 'mobile', 'dth'],
        'bills' => ['electricity', 'water', 'gas'],
        'transactions' => ['list', 'details', 'dashboard'],
        'admin' => ['login', 'dashboard', 'users', 'transactions']
    ]
]);
