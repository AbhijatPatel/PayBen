<?php
if (!isset($currentPage)) {
    $currentPage = basename($_SERVER['PHP_SELF'], '.php');
}

if (!isset($currentUser) && function_exists('getCurrentUser')) {
    $currentUser = getCurrentUser();
}

function isNavItemActive($routes, string $current): bool {
    if (is_array($routes)) {
        return in_array($current, $routes, true);
    }
    return $current === $routes;
}

$isAdminUser = function_exists('isAdmin') ? isAdmin() : (isset($currentUser['role']) && strtolower($currentUser['role']) === 'admin');
?>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="<?= APP_URL ?>/pages/dashboard.php" class="sidebar-brand text-decoration-none">
            <div class="sidebar-brand-icon">
                <i class="bi bi-lightning-charge-fill"></i>
            </div>
            <span class="sidebar-brand-text">PayBen</span>
        </a>
        <button type="button" 
                class="btn btn-sm btn-link text-white-50 d-lg-none p-1 text-decoration-none" 
                id="sidebarCloseBtn" 
                aria-label="Close Sidebar"
                onclick="document.getElementById('sidebar')?.classList.remove('show'); document.getElementById('sidebarOverlay')?.classList.remove('show');">
            <i class="bi bi-x-lg fs-5"></i>
        </button>
    </div>

    <ul class="sidebar-menu">
        <!-- Section: Overview -->
        <li class="sidebar-heading">Overview</li>

        <!-- Dashboard -->
        <?php $isActive = isNavItemActive('dashboard', $currentPage); ?>
        <li class="sidebar-item <?= $isActive ? 'active' : '' ?>">
            <a href="<?= APP_URL ?>/pages/dashboard.php" class="sidebar-link <?= $isActive ? 'active' : '' ?>">
                <i class="bi bi-speedometer2"></i>
                <span>Dashboard</span>
            </a>
        </li>

        <!-- Section: Recharge & Bill Payments -->
        <li class="sidebar-heading">Recharge & Utilities</li>

        <!-- Mobile Recharge -->
        <?php $isActive = isNavItemActive(['recharge', 'mobile', 'mobile-recharge'], $currentPage); ?>
        <li class="sidebar-item <?= $isActive ? 'active' : '' ?>">
            <a href="<?= APP_URL ?>/pages/recharge.php" class="sidebar-link <?= $isActive ? 'active' : '' ?>">
                <i class="bi bi-phone"></i>
                <span>Mobile Recharge</span>
            </a>
        </li>

        <!-- DTH Recharge -->
        <?php $isActive = isNavItemActive(['dth', 'dth-recharge'], $currentPage); ?>
        <li class="sidebar-item <?= $isActive ? 'active' : '' ?>">
            <a href="<?= APP_URL ?>/pages/dth.php" class="sidebar-link <?= $isActive ? 'active' : '' ?>">
                <i class="bi bi-tv"></i>
                <span>DTH Recharge</span>
            </a>
        </li>

        <!-- Electricity Bill -->
        <?php $isActive = isNavItemActive(['electricity', 'electricity-bill'], $currentPage); ?>
        <li class="sidebar-item <?= $isActive ? 'active' : '' ?>">
            <a href="<?= APP_URL ?>/pages/electricity.php" class="sidebar-link <?= $isActive ? 'active' : '' ?>">
                <i class="bi bi-lightning"></i>
                <span>Electricity Bill</span>
            </a>
        </li>

        <!-- Water Bill -->
        <?php $isActive = isNavItemActive(['water', 'water-bill'], $currentPage); ?>
        <li class="sidebar-item <?= $isActive ? 'active' : '' ?>">
            <a href="<?= APP_URL ?>/pages/water.php" class="sidebar-link <?= $isActive ? 'active' : '' ?>">
                <i class="bi bi-droplet"></i>
                <span>Water Bill</span>
            </a>
        </li>

        <!-- Gas Bill -->
        <?php $isActive = isNavItemActive(['gas', 'gas-bill'], $currentPage); ?>
        <li class="sidebar-item <?= $isActive ? 'active' : '' ?>">
            <a href="<?= APP_URL ?>/pages/gas.php" class="sidebar-link <?= $isActive ? 'active' : '' ?>">
                <i class="bi bi-fire"></i>
                <span>Gas Bill</span>
            </a>
        </li>

        <!-- Section: Finance & Activity -->
        <li class="sidebar-heading">Finance</li>

        <!-- Wallet -->
        <?php $isActive = isNavItemActive(['wallet', 'add-money'], $currentPage); ?>
        <li class="sidebar-item <?= $isActive ? 'active' : '' ?>">
            <a href="<?= APP_URL ?>/pages/wallet.php" class="sidebar-link <?= $isActive ? 'active' : '' ?>">
                <i class="bi bi-wallet2"></i>
                <span>Wallet</span>
                <?php if (!empty($currentUser['wallet_balance'])): ?>
                    <span class="sidebar-badge bg-primary text-white">
                        <?= formatCurrency($currentUser['wallet_balance']) ?>
                    </span>
                <?php endif; ?>
            </a>
        </li>

        <!-- Transactions -->
        <?php $isActive = isNavItemActive(['transactions', 'history'], $currentPage); ?>
        <li class="sidebar-item <?= $isActive ? 'active' : '' ?>">
            <a href="<?= APP_URL ?>/pages/transactions.php" class="sidebar-link <?= $isActive ? 'active' : '' ?>">
                <i class="bi bi-receipt"></i>
                <span>Transactions</span>
            </a>
        </li>

        <!-- Section: Settings -->
        <li class="sidebar-heading">Account</li>

        <!-- Profile -->
        <?php $isActive = isNavItemActive(['profile', 'settings'], $currentPage); ?>
        <li class="sidebar-item <?= $isActive ? 'active' : '' ?>">
            <a href="<?= APP_URL ?>/pages/profile.php" class="sidebar-link <?= $isActive ? 'active' : '' ?>">
                <i class="bi bi-person"></i>
                <span>Profile</span>
            </a>
        </li>

        <?php if ($isAdminUser): ?>
            <!-- Section: Administration -->
            <li class="sidebar-heading">Administration</li>
            <?php $isActive = isNavItemActive(['admin', 'admin-users', 'admin-transactions', 'admin-operators'], $currentPage); ?>
            <li class="sidebar-item <?= $isActive ? 'active' : '' ?>">
                <a href="<?= APP_URL ?>/admin/index.php" class="sidebar-link <?= $isActive ? 'active' : '' ?> text-warning">
                    <i class="bi bi-shield-lock text-warning"></i>
                    <span>Admin Panel</span>
                </a>
            </li>
        <?php endif; ?>
    </ul>

    <!-- Bottom User Profile Card -->
    <?php if (!empty($currentUser)): ?>
        <div class="sidebar-user">
            <div class="sidebar-user-avatar">
                <?= strtoupper(substr($currentUser['name'] ?? 'U', 0, 1)) ?>
            </div>
            <div class="sidebar-user-info">
                <div class="sidebar-user-name" title="<?= sanitize($currentUser['name'] ?? 'User') ?>">
                    <?= sanitize($currentUser['name'] ?? 'User') ?>
                </div>
                <div class="sidebar-user-role">
                    <?= ucfirst(sanitize($currentUser['role'] ?? 'user')) ?>
                </div>
            </div>
            <a href="<?= APP_URL ?>/pages/logout.php" 
               class="text-white-50 ms-auto p-1 text-decoration-none hover-text-white" 
               title="Sign Out">
                <i class="bi bi-box-arrow-right fs-5"></i>
            </a>
        </div>
    <?php endif; ?>
</aside>

<!-- Backdrop overlay for mobile sidebar drawer -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>
