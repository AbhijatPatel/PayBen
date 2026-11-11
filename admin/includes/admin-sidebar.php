<?php
$currentPage = $currentPage ?? 'dashboard';
?>
<!-- Sidebar Backdrop for Mobile Screens -->
<div class="admin-sidebar-overlay" id="sidebarOverlay"></div>

<!-- Admin Sidebar Navigation -->
<aside class="admin-sidebar" id="sidebar">
    <!-- Sidebar Header / Brand -->
    <div class="admin-sidebar-header">
        <a href="<?php echo APP_URL; ?>/admin/dashboard.php" class="admin-sidebar-brand text-decoration-none">
            <span class="admin-brand-icon">
                <i class="bi bi-shield-lock-fill"></i>
            </span>
            <div class="admin-brand-text">
                <div class="brand-title">Pay<span class="brand-accent">Ben</span></div>
                <div class="brand-subtitle"><span class="badge bg-primary-subtle text-primary border border-primary-subtle">ADMIN CONSOLE</span></div>
            </div>
        </a>
        <button type="button" class="btn btn-sm btn-link text-secondary d-lg-none" id="mobileSidebarClose" aria-label="Close sidebar">
            <i class="bi bi-x-lg fs-5"></i>
        </button>
    </div>

    <!-- Admin User Mini Card -->
    <div class="admin-user-card px-3 py-3 mx-3 my-3 rounded-3">
        <div class="d-flex align-items-center gap-2">
            <div class="admin-avatar">
                <i class="bi bi-person-fill-gear fs-5"></i>
            </div>
            <div class="overflow-hidden">
                <div class="admin-user-name text-truncate text-white fw-semibold small">
                    <?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Administrator', ENT_QUOTES, 'UTF-8'); ?>
                </div>
                <div class="admin-user-role small text-muted text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.05em;">
                    <i class="bi bi-circle-fill text-success me-1" style="font-size: 0.5rem;"></i>
                    <?php echo htmlspecialchars($_SESSION['admin_role'] ?? 'Admin', ENT_QUOTES, 'UTF-8'); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Menu List -->
    <div class="admin-sidebar-scroll flex-grow-1">
        <div class="sidebar-section-label">MAIN NAVIGATION</div>
        <ul class="admin-nav-list list-unstyled mb-0">
            <li class="admin-nav-item">
                <a href="<?php echo APP_URL; ?>/admin/dashboard.php" class="admin-nav-link <?php echo ($currentPage === 'dashboard') ? 'active' : ''; ?>">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="admin-nav-item">
                <a href="<?php echo APP_URL; ?>/admin/users.php" class="admin-nav-link <?php echo ($currentPage === 'users') ? 'active' : ''; ?>">
                    <i class="bi bi-people-fill"></i>
                    <span>Users</span>
                </a>
            </li>
            <li class="admin-nav-item">
                <a href="<?php echo APP_URL; ?>/admin/transactions.php" class="admin-nav-link <?php echo ($currentPage === 'transactions') ? 'active' : ''; ?>">
                    <i class="bi bi-arrow-left-right"></i>
                    <span>Transactions</span>
                </a>
            </li>
        </ul>

        <div class="sidebar-section-label mt-3">CATALOG & BILLING</div>
        <ul class="admin-nav-list list-unstyled mb-0">
            <li class="admin-nav-item">
                <a href="<?php echo APP_URL; ?>/admin/manage-plans.php?tab=plans" class="admin-nav-link <?php echo ($currentPage === 'plans') ? 'active' : ''; ?>">
                    <i class="bi bi-journal-text"></i>
                    <span>Manage Plans</span>
                </a>
            </li>
            <li class="admin-nav-item">
                <a href="<?php echo APP_URL; ?>/admin/manage-plans.php?tab=operators" class="admin-nav-link <?php echo ($currentPage === 'operators') ? 'active' : ''; ?>">
                    <i class="bi bi-broadcast-pin"></i>
                    <span>Manage Operators</span>
                </a>
            </li>
            <li class="admin-nav-item">
                <a href="<?php echo APP_URL; ?>/admin/manage-bills.php" class="admin-nav-link <?php echo ($currentPage === 'bills') ? 'active' : ''; ?>">
                    <i class="bi bi-receipt-cutoff"></i>
                    <span>Manage Bills</span>
                </a>
            </li>
        </ul>

        <div class="sidebar-section-label mt-3">SYSTEM & LINKS</div>
        <ul class="admin-nav-list list-unstyled mb-0">
            <li class="admin-nav-item">
                <a href="<?php echo APP_URL; ?>/index.php" target="_blank" class="admin-nav-link external-link">
                    <i class="bi bi-box-arrow-up-right"></i>
                    <span>View Customer Site</span>
                </a>
            </li>
            <li class="admin-nav-item">
                <a href="<?php echo APP_URL; ?>/admin/logout.php" class="admin-nav-link text-danger-emphasis">
                    <i class="bi bi-box-arrow-right text-danger"></i>
                    <span>Sign Out</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Admin Sidebar Footer -->
    <div class="admin-sidebar-bottom p-3 border-top border-secondary border-opacity-10 text-center">
        <span class="badge bg-dark-subtle text-secondary border border-secondary border-opacity-25 py-1 px-2 small">
            <i class="bi bi-cpu-fill me-1 text-primary"></i> PayBen v<?php echo APP_VERSION; ?>
        </span>
    </div>
</aside>
