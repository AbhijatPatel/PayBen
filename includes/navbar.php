<?php

if (!isset($currentUser) && function_exists('getCurrentUser')) {
    $currentUser = getCurrentUser();
}

$userLoggedIn = function_exists('isLoggedIn') ? isLoggedIn() : (!empty($currentUser['id'] ?? null));
?>

<header class="app-header navbar navbar-expand-lg bg-white shadow-sm border-bottom sticky-top">
    <div class="container-fluid px-2 px-md-3">
        <div class="d-flex align-items-center gap-2">
            <?php if ($userLoggedIn || !empty($hasSidebar)): ?>
                <button class="btn btn-light btn-sm d-lg-none d-flex align-items-center justify-content-center p-2 rounded-2 border" 
                        type="button" 
                        id="mobileSidebarToggle" 
                        aria-label="Toggle Sidebar Menu"
                        style="width: 38px; height: 38px;">
                    <i class="bi bi-list fs-4 text-dark"></i>
                </button>

                <button class="btn btn-light btn-sm d-none d-lg-inline-flex align-items-center justify-content-center p-2 rounded-2 border" 
                        type="button" 
                        id="sidebarToggle" 
                        title="Toggle Sidebar Collapse"
                        style="width: 38px; height: 38px;">
                    <i class="bi bi-layout-sidebar-inset fs-5 text-secondary"></i>
                </button>
            <?php endif; ?>

            <a class="navbar-brand d-flex align-items-center gap-2 text-decoration-none m-0" href="<?= APP_URL ?>/">
                <div class="rounded-3 d-flex align-items-center justify-content-center shadow-sm text-white" 
                     style="width: 38px; height: 38px; background: linear-gradient(135deg, #2563eb 0%, #10b981 100%);">
                    <i class="bi bi-lightning-charge-fill fs-5"></i>
                </div>
                <div class="d-flex flex-column" style="line-height: 1.15;">
                    <div class="d-flex align-items-center gap-1">
                        <span class="fs-4 fw-bold text-dark" style="letter-spacing: -0.5px;">Pay<span style="color: #2563eb;">Ben</span></span>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill" style="font-size: 0.65rem; padding: 2px 7px;">UPI</span>
                    </div>
                    <span class="text-muted fw-medium" style="font-size: 0.68rem; letter-spacing: 0.3px;">Instant Recharge &amp; Bills</span>
                </div>
            </a>
        </div>

        <div class="d-none d-md-flex align-items-center mx-auto" style="max-width: 440px; width: 100%;">
            <div class="gpay-search-bar d-flex align-items-center gap-2 w-100" onclick="document.getElementById('upiPayBtn') ? document.getElementById('upiPayBtn').click() : window.location.href='<?= APP_URL ?>/dashboard.php#quickPay'">
                <i class="bi bi-search text-muted"></i>
                <span class="text-muted small flex-grow-1">Pay contacts, mobile number, or UPI ID...</span>
                <span class="badge bg-white text-primary border rounded-pill px-2 py-1 shadow-xs d-flex align-items-center gap-1">
                    <i class="bi bi-qr-code-scan"></i> Scan
                </span>
            </div>
        </div>

        <!-- Mobile Navbar Toggler (for public menu / right controls on extra small screens) -->
        <button class="navbar-toggler border-0 shadow-none p-1" 
                type="button" 
                data-bs-toggle="collapse" 
                data-bs-target="#paybenNavbarCollapse" 
                aria-controls="paybenNavbarCollapse" 
                aria-expanded="false" 
                aria-label="Toggle Navigation Menu">
            <i class="bi bi-three-dots-vertical fs-4 text-secondary"></i>
        </button>

        <!-- Navbar Content & Actions -->
        <div class="collapse navbar-collapse mt-2 mt-lg-0" id="paybenNavbarCollapse">
            <!-- Center Navigation Links (Optional public shortcuts) -->
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-3">
                <?php if (!$userLoggedIn): ?>
                    <li class="nav-item">
                        <a class="nav-link fw-semibold text-secondary px-2" href="<?= APP_URL ?>/">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-semibold text-secondary px-2" href="<?= APP_URL ?>/pages/recharge.php">Recharge</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-semibold text-secondary px-2" href="<?= APP_URL ?>/pages/electricity.php">Bill Payment</a>
                    </li>
                <?php endif; ?>
            </ul>

            <!-- Right-Side Actions -->
            <div class="d-flex align-items-center gap-2 gap-md-3 ms-auto flex-wrap flex-lg-nowrap justify-content-end">
                <?php if ($userLoggedIn): ?>
                    <!-- Wallet Balance Pill -->
                    <a href="<?= APP_URL ?>/pages/wallet.php" 
                       class="text-decoration-none d-inline-flex align-items-center gap-2 px-3 py-1 bg-light rounded-pill border border-primary-subtle text-dark" 
                       title="Wallet Balance - Click to Manage">
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 26px; height: 26px;">
                            <i class="bi bi-wallet2" style="font-size: 0.75rem;"></i>
                        </div>
                        <div class="d-flex flex-column text-start" style="line-height: 1.15;">
                            <span class="text-muted text-uppercase fw-bold" style="font-size: 0.65rem; letter-spacing: 0.5px;">Wallet</span>
                            <span class="fw-bold text-primary" style="font-size: 0.92rem;"><?= formatCurrency($currentUser['wallet_balance'] ?? 0.00) ?></span>
                        </div>
                        <i class="bi bi-plus-circle-fill text-primary ms-1" style="font-size: 0.85rem;" title="Add Money"></i>
                    </a>

                    <!-- Notifications Dropdown -->
                    <div class="dropdown">
                        <button class="btn btn-light rounded-circle position-relative p-0 d-flex align-items-center justify-content-center shadow-none border" 
                                type="button" 
                                id="headerNotifications" 
                                data-bs-toggle="dropdown" 
                                aria-expanded="false" 
                                style="width: 38px; height: 38px;" 
                                title="Notifications">
                            <i class="bi bi-bell fs-5 text-secondary"></i>
                            <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle" style="transform: translate(-35%, 35%) !important;">
                                <span class="visually-hidden">New alerts</span>
                            </span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end shadow-lg border-0 py-0" aria-labelledby="headerNotifications" style="width: 320px; border-radius: 12px; overflow: hidden;">
                            <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
                                <h6 class="mb-0 fw-bold text-dark">Notifications</h6>
                                <span class="badge bg-primary rounded-pill">Recent</span>
                            </div>
                            <div class="list-group list-group-flush small" style="max-height: 270px; overflow-y: auto;">
                                <a href="<?= APP_URL ?>/pages/transactions.php" class="list-group-item list-group-item-action py-3 d-flex gap-3 align-items-start border-0 border-bottom">
                                    <div class="rounded-circle bg-success-subtle text-success p-2 d-flex align-items-center justify-content-center mt-1" style="width: 32px; height: 32px; flex-shrink: 0;">
                                        <i class="bi bi-check2-circle fs-5"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold text-dark">Account Ready</div>
                                        <div class="text-muted small">Your PayBen wallet is ready for fast recharges & bill payments.</div>
                                        <div class="text-muted" style="font-size: 0.7rem;">Active</div>
                                    </div>
                                </a>
                            </div>
                            <div class="p-2 text-center bg-light border-top">
                                <a href="<?= APP_URL ?>/pages/transactions.php" class="text-decoration-none small text-primary fw-semibold">View All Transactions</a>
                            </div>
                        </div>
                    </div>

                    <!-- User Account Menu Dropdown -->
                    <div class="dropdown">
                        <button class="btn btn-light rounded-pill px-2 py-1 d-flex align-items-center gap-2 border shadow-none" 
                                type="button" 
                                id="navbarUserDropdown" 
                                data-bs-toggle="dropdown" 
                                aria-expanded="false">
                            <div class="sidebar-user-avatar" style="width: 32px; height: 32px; font-size: 0.85rem;">
                                <?= strtoupper(substr($currentUser['name'] ?? 'U', 0, 1)) ?>
                            </div>
                            <div class="d-none d-md-flex flex-column text-start me-1" style="line-height: 1.15;">
                                <span class="fw-semibold text-dark text-truncate" style="max-width: 140px; font-size: 0.85rem;">
                                    <?= sanitize($currentUser['name'] ?? 'User') ?>
                                </span>
                                <small class="text-muted" style="font-size: 0.7rem;">
                                    <?= ucfirst(sanitize($currentUser['role'] ?? 'user')) ?>
                                </small>
                            </div>
                            <i class="bi bi-chevron-down text-muted small"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 mt-2 py-2" aria-labelledby="navbarUserDropdown" style="min-width: 220px; border-radius: 12px;">
                            <li class="px-3 py-2 border-bottom mb-1">
                                <div class="fw-bold text-dark"><?= sanitize($currentUser['name'] ?? 'User') ?></div>
                                <small class="text-muted text-truncate d-block" style="max-width: 190px;">
                                    <?= sanitize($currentUser['email'] ?? '') ?>
                                </small>
                            </li>
                            <li>
                                <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="<?= APP_URL ?>/pages/profile.php">
                                    <i class="bi bi-person text-primary fs-5"></i> Profile
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="<?= APP_URL ?>/pages/profile.php#password">
                                    <i class="bi bi-key text-info fs-5"></i> Change Password
                                </a>
                            </li>
                            <?php if (function_exists('isAdmin') && isAdmin()): ?>
                                <li>
                                    <a class="dropdown-item py-2 d-flex align-items-center gap-2 text-warning fw-semibold" href="<?= APP_URL ?>/admin/index.php">
                                        <i class="bi bi-shield-check fs-5"></i> Admin Dashboard
                                    </a>
                                </li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li>
                                <a class="dropdown-item py-2 d-flex align-items-center gap-2 text-danger" href="<?= APP_URL ?>/pages/logout.php">
                                    <i class="bi bi-box-arrow-right fs-5"></i> Logout
                                </a>
                            </li>
                        </ul>
                    </div>
                <?php else: ?>
                    <!-- Unauthenticated: Login & Register CTA Buttons -->
                    <div class="d-flex align-items-center gap-2">
                        <a href="<?= APP_URL ?>/pages/login.php" class="btn btn-outline-primary px-3 fw-medium d-inline-flex align-items-center gap-1">
                            <i class="bi bi-box-arrow-in-right"></i> Login
                        </a>
                        <a href="<?= APP_URL ?>/pages/register.php" class="btn btn-primary px-3 fw-medium d-inline-flex align-items-center gap-1">
                            <i class="bi bi-person-plus"></i> Register
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>
