<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

// Enforce admin privileges - unauthorized users are redirected to admin/login.php
requireAdmin();

$currentAdmin = getCurrentAdmin();
$adminPageTitle = isset($pageTitle) ? htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') . ' | PayBen Admin' : 'PayBen Admin Portal';
$flash = function_exists('getFlashMessage') ? getFlashMessage() : null;
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="csrf-token" content="<?php echo htmlspecialchars(generateCSRFToken(), ENT_QUOTES, 'UTF-8'); ?>">
    <title><?php echo $adminPageTitle; ?></title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Base Fintech Stylesheet -->
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/style.css">

    <!-- Specialized Admin Dark Fintech Theme Styles -->
    <style>
        :root {
            --admin-bg-base: #0b0f19;
            --admin-bg-surface: #111827;
            --admin-bg-card: #1f2937;
            --admin-bg-card-hover: #243042;
            --admin-border: #374151;
            --admin-border-subtle: rgba(255, 255, 255, 0.08);
            --admin-primary: #4f46e5;
            --admin-primary-hover: #4338ca;
            --admin-accent: #7c3aed;
            --admin-sidebar-width: 260px;
            --admin-header-height: 70px;
        }

        body.admin-body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: var(--admin-bg-base);
            color: #f3f4f6;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
            min-height: 100vh;
        }

        /* Admin Wrapper & Layout */
        .admin-wrapper {
            display: flex;
            min-height: 100vh;
            width: 100%;
        }

        /* Admin Sidebar */
        .admin-sidebar {
            width: var(--admin-sidebar-width);
            background: linear-gradient(180deg, #090d16 0%, #0d121f 100%);
            border-right: 1px solid var(--admin-border-subtle);
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 1040;
            display: flex;
            flex-direction: column;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 4px 0 24px rgba(0, 0, 0, 0.4);
        }

        .admin-sidebar-header {
            height: var(--admin-header-height);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 1.25rem;
            border-bottom: 1px solid var(--admin-border-subtle);
        }

        .admin-sidebar-brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: #ffffff;
            font-size: 1.25rem;
            font-weight: 800;
        }

        .admin-brand-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(79, 70, 229, 0.4);
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .brand-title {
            line-height: 1.1;
            letter-spacing: -0.02em;
        }

        .brand-accent {
            color: #818cf8;
        }

        .brand-subtitle .badge {
            font-size: 0.6rem;
            letter-spacing: 0.08em;
            padding: 0.2rem 0.45rem;
            font-weight: 700;
        }

        .admin-user-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--admin-border-subtle);
        }

        .admin-avatar {
            width: 36px;
            height: 36px;
            background: rgba(79, 70, 229, 0.2);
            color: #818cf8;
            border: 1px solid rgba(79, 70, 229, 0.4);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .admin-sidebar-scroll {
            overflow-y: auto;
            padding: 0 0.85rem;
        }

        .sidebar-section-label {
            font-size: 0.65rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            color: #6b7280;
            padding: 0.5rem 0.65rem 0.25rem;
        }

        .admin-nav-item {
            margin-bottom: 0.25rem;
        }

        .admin-nav-link {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            padding: 0.68rem 0.85rem;
            color: #9ca3af;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .admin-nav-link i {
            font-size: 1.15rem;
            width: 22px;
            text-align: center;
            color: #9ca3af;
            transition: color 0.2s ease;
        }

        .admin-nav-link:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.05);
        }

        .admin-nav-link:hover i {
            color: #818cf8;
        }

        .admin-nav-link.active {
            color: #ffffff;
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            box-shadow: 0 4px 14px rgba(79, 70, 229, 0.35);
            font-weight: 600;
        }

        .admin-nav-link.active i {
            color: #ffffff;
        }

        .admin-nav-link.external-link:hover {
            color: #38bdf8;
        }

        /* Main Content Layout */
        .admin-main {
            flex: 1;
            margin-left: var(--admin-sidebar-width);
            min-width: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            background-color: var(--admin-bg-base);
            transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Top Navbar */
        .admin-navbar {
            height: var(--admin-header-height);
            background-color: rgba(17, 24, 39, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--admin-border-subtle);
            position: sticky;
            top: 0;
            z-index: 1020;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 1.75rem;
        }

        .admin-content {
            padding: 1.75rem;
            flex: 1;
        }

        /* Card System for Admin */
        .admin-card {
            background-color: var(--admin-bg-surface);
            border: 1px solid var(--admin-border);
            border-radius: 14px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.25);
            transition: transform 0.2s ease, border-color 0.2s ease;
        }

        .admin-card:hover {
            border-color: rgba(99, 102, 241, 0.4);
        }

        .stat-card {
            position: relative;
            overflow: hidden;
            border-radius: 14px;
            padding: 1.5rem;
            background: linear-gradient(145deg, #162032 0%, #111827 100%);
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 140px;
            height: 140px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(79, 70, 229, 0.12) 0%, transparent 70%);
            pointer-events: none;
        }

        .stat-icon {
            width: 52px;
            height: 52px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            flex-shrink: 0;
        }

        .stat-icon.primary {
            background: rgba(79, 70, 229, 0.15);
            color: #818cf8;
            border: 1px solid rgba(79, 70, 229, 0.3);
        }

        .stat-icon.success {
            background: rgba(16, 185, 129, 0.15);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .stat-icon.info {
            background: rgba(14, 165, 233, 0.15);
            color: #38bdf8;
            border: 1px solid rgba(14, 165, 233, 0.3);
        }

        .stat-icon.warning {
            background: rgba(245, 158, 11, 0.15);
            color: #fbbf24;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }

        .stat-value {
            font-size: 1.85rem;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: -0.02em;
            line-height: 1.2;
        }

        .stat-label {
            font-size: 0.85rem;
            color: #9ca3af;
            font-weight: 500;
        }

        /* Modern Dark Table System */
        .admin-table {
            color: #e5e7eb;
            vertical-align: middle;
            margin-bottom: 0;
        }

        .admin-table thead th {
            background-color: #1a2233;
            color: #94a3b8;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 700;
            border-bottom: 1px solid var(--admin-border);
            padding: 0.85rem 1rem;
            white-space: nowrap;
        }

        .admin-table tbody td {
            padding: 0.85rem 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            background-color: transparent;
        }

        .admin-table tbody tr:hover td {
            background-color: rgba(255, 255, 255, 0.02);
        }

        .badge-status-success {
            background: rgba(16, 185, 129, 0.15);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.3);
            font-weight: 600;
            padding: 0.35rem 0.65rem;
            border-radius: 6px;
        }

        .badge-status-pending {
            background: rgba(245, 158, 11, 0.15);
            color: #fbbf24;
            border: 1px solid rgba(245, 158, 11, 0.3);
            font-weight: 600;
            padding: 0.35rem 0.65rem;
            border-radius: 6px;
        }

        .badge-status-failed {
            background: rgba(239, 68, 68, 0.15);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.3);
            font-weight: 600;
            padding: 0.35rem 0.65rem;
            border-radius: 6px;
        }

        .badge-status-active {
            background: rgba(16, 185, 129, 0.15);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.3);
            font-weight: 600;
            padding: 0.35rem 0.65rem;
            border-radius: 6px;
        }

        .badge-status-inactive {
            background: rgba(107, 114, 128, 0.2);
            color: #9ca3af;
            border: 1px solid rgba(107, 114, 128, 0.3);
            font-weight: 600;
            padding: 0.35rem 0.65rem;
            border-radius: 6px;
        }

        /* Form Controls Dark Mode */
        .admin-card .form-control,
        .admin-card .form-select,
        .modal-content .form-control,
        .modal-content .form-select {
            background-color: #111827;
            border: 1px solid var(--admin-border);
            color: #f3f4f6;
            border-radius: 8px;
        }

        .admin-card .form-control:focus,
        .admin-card .form-select:focus,
        .modal-content .form-control:focus,
        .modal-content .form-select:focus {
            background-color: #111827;
            border-color: #6366f1;
            color: #ffffff;
            box-shadow: 0 0 0 0.25rem rgba(99, 102, 241, 0.25);
        }

        .modal-content {
            background-color: #162032;
            border: 1px solid var(--admin-border);
            color: #f3f4f6;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.6);
            border-radius: 14px;
        }

        .modal-header {
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            padding: 1.25rem 1.5rem;
        }

        .modal-footer {
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            padding: 1.25rem 1.5rem;
        }

        /* Code & Monospace Badges */
        .font-mono {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.85rem;
        }

        /* Mobile Sidebar Overlay */
        .admin-sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.65);
            backdrop-filter: blur(4px);
            z-index: 1030;
        }

        @media (max-width: 991.98px) {
            .admin-sidebar {
                transform: translateX(-100%);
            }
            .admin-sidebar.show {
                transform: translateX(0);
            }
            .admin-sidebar-overlay.show {
                display: block;
            }
            .admin-main {
                margin-left: 0;
            }
        }
    </style>
</head>
<body class="admin-body">

<div class="admin-wrapper">
    <!-- Sidebar Navigation -->
    <?php require_once __DIR__ . '/admin-sidebar.php'; ?>

    <!-- Main Content Area -->
    <div class="admin-main">
        <!-- Top Navbar -->
        <header class="admin-navbar">
            <div class="d-flex align-items-center gap-3">
                <button type="button" class="btn btn-sm btn-outline-secondary d-lg-none" id="mobileSidebarToggle" aria-label="Toggle navigation">
                    <i class="bi bi-list fs-5"></i>
                </button>
                <div class="d-none d-sm-flex align-items-center gap-2">
                    <span class="badge bg-primary bg-opacity-25 text-primary border border-primary border-opacity-25 px-2 py-1 small">
                        <i class="bi bi-shield-check me-1"></i> Admin Portal
                    </span>
                    <span class="text-secondary small">&bull;</span>
                    <span class="text-white-50 small"><?php echo date('l, d M Y'); ?></span>
                </div>
            </div>

            <div class="d-flex align-items-center gap-3">
                <!-- Main Customer Site Link -->
                <a href="<?php echo APP_URL; ?>/index.php" target="_blank" class="btn btn-sm btn-outline-secondary rounded-pill px-3 d-none d-md-inline-flex align-items-center gap-1" title="Open public portal in new tab">
                    <i class="bi bi-box-arrow-up-right"></i>
                    <span>Customer Site</span>
                </a>

                <!-- Admin Profile Dropdown -->
                <div class="dropdown">
                    <button class="btn btn-outline-secondary dropdown-toggle d-flex align-items-center gap-2 rounded-pill px-3 py-1 shadow-none" type="button" id="adminDropdownMenu" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="admin-avatar" style="width: 28px; height: 28px; font-size: 0.85rem;">
                            <i class="bi bi-person-fill-gear"></i>
                        </span>
                        <span class="fw-semibold small text-truncate" style="max-width: 140px;">
                            <?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Administrator', ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border border-secondary border-opacity-25 rounded-3 mt-2 py-2" aria-labelledby="adminDropdownMenu" style="background-color: #1e293b; min-width: 220px;">
                        <li class="px-3 py-2 border-bottom border-secondary border-opacity-25">
                            <p class="mb-0 fw-bold text-white small"><?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin', ENT_QUOTES, 'UTF-8'); ?></p>
                            <p class="mb-0 text-muted small text-truncate"><?php echo htmlspecialchars($_SESSION['admin_email'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle mt-1 small">
                                <?php echo htmlspecialchars($_SESSION['admin_role'] ?? 'admin', ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                        </li>
                        <li>
                            <a class="dropdown-item py-2 text-light" href="<?php echo APP_URL; ?>/admin/dashboard.php">
                                <i class="bi bi-speedometer2 text-primary me-2"></i> Dashboard
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item py-2 text-light" href="<?php echo APP_URL; ?>/admin/users.php">
                                <i class="bi bi-people-fill text-info me-2"></i> User Directory
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item py-2 text-light" href="<?php echo APP_URL; ?>/admin/transactions.php">
                                <i class="bi bi-arrow-left-right text-success me-2"></i> Transactions
                            </a>
                        </li>
                        <li><hr class="dropdown-divider border-secondary border-opacity-25"></li>
                        <li>
                            <a class="dropdown-item py-2 text-danger" href="<?php echo APP_URL; ?>/admin/logout.php">
                                <i class="bi bi-box-arrow-right me-2"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- Flash Message Notification Banner -->
        <?php if (!empty($flash)): ?>
            <div class="px-4 pt-3">
                <div class="alert alert-<?php echo htmlspecialchars($flash['type'] ?? 'info', ENT_QUOTES, 'UTF-8'); ?> alert-dismissible fade show shadow-sm border border-<?php echo htmlspecialchars($flash['type'] ?? 'info', ENT_QUOTES, 'UTF-8'); ?> border-opacity-25 rounded-3 d-flex align-items-center gap-2 mb-0" role="alert">
                    <?php
                        $icon = match($flash['type'] ?? 'info') {
                            'success' => 'bi-check-circle-fill',
                            'danger' => 'bi-exclamation-triangle-fill',
                            'warning' => 'bi-exclamation-circle-fill',
                            default => 'bi-info-circle-fill',
                        };
                    ?>
                    <i class="bi <?php echo $icon; ?> fs-5"></i>
                    <div class="fw-medium"><?php echo htmlspecialchars($flash['message'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        <?php endif; ?>

        <!-- Page Main Body Container -->
        <main class="admin-content">
