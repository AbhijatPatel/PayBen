<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

if (file_exists(__DIR__ . '/auth.php')) {
    require_once __DIR__ . '/auth.php';
}

if (!isset($currentUser) && function_exists('getCurrentUser')) {
    $currentUser = getCurrentUser();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?= isset($pageTitle) ? sanitize($pageTitle) . ' - ' . APP_NAME : APP_NAME . ' - ' . APP_TAGLINE ?></title>

    <meta name="description" content="<?= APP_NAME ?> - Fastest, Secure Mobile Recharge & Bill Payment Platform in India.">
    <meta name="csrf-token" content="<?= function_exists('generateCSRFToken') ? generateCSRFToken() : '' ?>">

    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%234361ee'><path d='M13 2L3 14h9l-1 8 10-12h-9l1-8z'/></svg>">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">

    <link href="<?= APP_URL ?>/assets/css/style.css" rel="stylesheet">

    <?php if (isset($extraHead)) echo $extraHead; ?>
</head>
<body class="<?= isset($bodyClass) ? sanitize($bodyClass) : '' ?>">
