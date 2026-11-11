<?php
/**
 * PayBen - Dashboard Layout Wrapper (Top)
 *
 * Convenience wrapper that initializes session, authentication, HTML head,
 * opens the .app-wrapper flex container, renders the dark sidebar, opens the
 * .main-content column, renders the top navbar, and starts .content-body.
 *
 * Usage Example:
 * <?php
 * $pageTitle   = 'Mobile Recharge';
 * $currentPage = 'recharge';
 * require_once __DIR__ . '/../includes/dashboard_layout_top.php';
 * ?>
 * <h2>Recharge Page Content</h2>
 * <?php
 * require_once __DIR__ . '/../includes/dashboard_layout_bottom.php';
 * ?>
 *
 * @var string|null $pageTitle   Page title prefix
 * @var string|null $currentPage Identifier for active sidebar link
 */

require_once __DIR__ . '/header.php';
?>
<div class="app-wrapper">
    <!-- Left Navigation Sidebar -->
    <?php require_once __DIR__ . '/sidebar.php'; ?>

    <!-- Main Content Column -->
    <div class="main-content">
        <!-- Top Navbar -->
        <?php require_once __DIR__ . '/navbar.php'; ?>

        <!-- Primary Page Content Area -->
        <main class="content-body">
