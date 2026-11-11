<?php
// Admin dashboard

$pageTitle = 'Executive Dashboard';
$currentPage = 'dashboard';

require_once __DIR__ . '/includes/admin-header.php';

// Fetch aggregate platform analytics
$pdo = getDBConnection();

$totalUsers = 0;
$totalTransactions = 0;
$successfulTransactions = 0;
$totalPaymentVolume = 0.00;
$pendingTransactions = 0;
$totalWalletBalance = 0.00;

try {
    // 1. Total Registered Users
    $stmt = $pdo->query("SELECT COUNT(*) AS count FROM users");
    $totalUsers = (int)($stmt->fetch()['count'] ?? 0);

    // 2. Total Transactions
    $stmt = $pdo->query("SELECT COUNT(*) AS count FROM transactions");
    $totalTransactions = (int)($stmt->fetch()['count'] ?? 0);

    // 3. Successful Transactions
    $stmt = $pdo->query("SELECT COUNT(*) AS count FROM transactions WHERE status = 'SUCCESS'");
    $successfulTransactions = (int)($stmt->fetch()['count'] ?? 0);

    // 4. Total Payment Volume (Success amount)
    $stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0.00) AS volume FROM transactions WHERE status = 'SUCCESS'");
    $totalPaymentVolume = (float)($stmt->fetch()['volume'] ?? 0.00);

    // 5. Total Wallet Reserve across all users
    $stmt = $pdo->query("SELECT COALESCE(SUM(balance), 0.00) AS total_reserve FROM wallets");
    $totalWalletBalance = (float)($stmt->fetch()['total_reserve'] ?? 0.00);

    // 6. Pending Transactions
    $stmt = $pdo->query("SELECT COUNT(*) AS count FROM transactions WHERE status = 'PENDING'");
    $pendingTransactions = (int)($stmt->fetch()['count'] ?? 0);

    // 7. Recent Users (Last 10)
    $stmt = $pdo->query("
        SELECT u.id, u.full_name, u.email, u.mobile, u.status, u.created_at, COALESCE(w.balance, 0.00) AS wallet_balance
        FROM users u
        LEFT JOIN wallets w ON u.id = w.user_id
        ORDER BY u.id DESC
        LIMIT 10
    ");
    $recentUsers = $stmt->fetchAll();

    // 8. Recent Transactions (Last 10)
    $stmt = $pdo->query("
        SELECT t.*, u.full_name AS user_name, u.email AS user_email
        FROM transactions t
        LEFT JOIN users u ON t.user_id = u.id
        ORDER BY t.id DESC
        LIMIT 10
    ");
    $recentTransactions = $stmt->fetchAll();

    // 9. Service breakdown statistics
    $stmt = $pdo->query("
        SELECT type, COUNT(*) AS count, COALESCE(SUM(amount), 0.00) AS volume
        FROM transactions
        GROUP BY type
    ");
    $serviceBreakdown = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Dashboard query error: " . $e->getMessage());
    $recentUsers = [];
    $recentTransactions = [];
    $serviceBreakdown = [];
}

$successRate = $totalTransactions > 0 ? round(($successfulTransactions / $totalTransactions) * 100, 1) : 0;
?>

<!-- Dashboard Page Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h2 class="fw-bold text-white mb-1 tracking-tight">Executive Dashboard</h2>
        <p class="text-secondary small mb-0">Overview of users, transaction throughput, payment volumes, and system health.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="<?php echo APP_URL; ?>/admin/manage-plans.php?tab=plans" class="btn btn-sm btn-outline-primary rounded-pill px-3">
            <i class="bi bi-plus-circle me-1"></i> Add Plan
        </a>
        <a href="<?php echo APP_URL; ?>/admin/transactions.php" class="btn btn-sm btn-primary rounded-pill px-3" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); border: none;">
            <i class="bi bi-receipt me-1"></i> All Transactions
        </a>
    </div>
</div>

<!-- Key Performance Stats Cards (4 in a Row) -->
<div class="row g-3 mb-4">
    <!-- Stat 1: Registered Users -->
    <div class="col-xl-3 col-sm-6">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-label">Total Registered Users</div>
                    <div class="stat-value mt-1"><?php echo number_format($totalUsers); ?></div>
                    <div class="small text-muted mt-2 d-flex align-items-center gap-1">
                        <i class="bi bi-person-check-fill text-success"></i>
                        <span>Customer base</span>
                    </div>
                </div>
                <div class="stat-icon primary">
                    <i class="bi bi-people-fill"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Stat 2: Total Transactions -->
    <div class="col-xl-3 col-sm-6">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-label">Total Transactions</div>
                    <div class="stat-value mt-1"><?php echo number_format($totalTransactions); ?></div>
                    <div class="small text-muted mt-2 d-flex align-items-center gap-1">
                        <i class="bi bi-activity text-info"></i>
                        <span>All orders processed</span>
                    </div>
                </div>
                <div class="stat-icon info">
                    <i class="bi bi-arrow-left-right"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Stat 3: Successful Transactions -->
    <div class="col-xl-3 col-sm-6">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-label">Successful Transactions</div>
                    <div class="stat-value mt-1 text-success"><?php echo number_format($successfulTransactions); ?></div>
                    <div class="small text-muted mt-2 d-flex align-items-center gap-1">
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-1 py-0" style="font-size: 0.7rem;">
                            <?php echo $successRate; ?>%
                        </span>
                        <span>Success rate</span>
                    </div>
                </div>
                <div class="stat-icon success">
                    <i class="bi bi-check2-circle"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Stat 4: Total Payment Volume -->
    <div class="col-xl-3 col-sm-6">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-label">Total Payment Volume</div>
                    <div class="stat-value mt-1 text-warning" style="font-size: 1.6rem;"><?php echo formatCurrency($totalPaymentVolume); ?></div>
                    <div class="small text-muted mt-2 d-flex align-items-center gap-1">
                        <i class="bi bi-shield-check text-warning"></i>
                        <span>Settled transactions</span>
                    </div>
                </div>
                <div class="stat-icon warning">
                    <i class="bi bi-currency-rupee"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Secondary Stats & Service Distribution Row -->
<div class="row g-3 mb-4">
    <!-- Quick System Snapshot Cards -->
    <div class="col-lg-4">
        <div class="admin-card p-4 h-100">
            <h6 class="text-white fw-bold mb-3 d-flex align-items-center gap-2">
                <i class="bi bi-wallet2 text-primary"></i> Platform Liquidity & Health
            </h6>
            <div class="p-3 rounded-3 mb-3" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06);">
                <div class="text-secondary small">Total User Wallets Balance</div>
                <div class="fs-4 fw-bold text-white mt-1"><?php echo formatCurrency($totalWalletBalance); ?></div>
                <div class="text-muted small mt-1">Floating digital balance held by users</div>
            </div>
            <div class="d-flex justify-content-between align-items-center py-2 border-bottom border-secondary border-opacity-10">
                <span class="text-secondary small"><i class="bi bi-hourglass-split text-warning me-1"></i> Pending Transactions</span>
                <span class="badge bg-warning-subtle text-warning border border-warning-subtle"><?php echo $pendingTransactions; ?></span>
            </div>
            <div class="d-flex justify-content-between align-items-center py-2 border-bottom border-secondary border-opacity-10">
                <span class="text-secondary small"><i class="bi bi-shield-lock-fill text-success me-1"></i> System Security Status</span>
                <span class="badge bg-success-subtle text-success border border-success-subtle">Operational</span>
            </div>
            <div class="d-flex justify-content-between align-items-center pt-2">
                <span class="text-secondary small"><i class="bi bi-database-check text-info me-1"></i> Database Mode</span>
                <span class="badge bg-info-subtle text-info border border-info-subtle">InnoDB / UTF8MB4</span>
            </div>
        </div>
    </div>

    <!-- Service Volume Distribution -->
    <div class="col-lg-8">
        <div class="admin-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="text-white fw-bold mb-0 d-flex align-items-center gap-2">
                    <i class="bi bi-pie-chart text-info"></i> Transaction Activity by Service
                </h6>
                <span class="text-secondary small">Breakdown across categories</span>
            </div>

            <?php if (empty($serviceBreakdown)): ?>
                <div class="text-center py-4 text-secondary small">
                    <i class="bi bi-inbox fs-2 d-block mb-1"></i>
                    No transaction breakdown recorded yet.
                </div>
            <?php else: ?>
                <div class="row g-2">
                    <?php 
                    $typeLabels = [
                        'mobile_recharge' => ['label' => 'Mobile Recharge', 'icon' => 'bi-phone', 'color' => '#6366f1'],
                        'dth_recharge'    => ['label' => 'DTH Recharge', 'icon' => 'bi-tv', 'color' => '#8b5cf6'],
                        'electricity'     => ['label' => 'Electricity Bills', 'icon' => 'bi-lightning-charge', 'color' => '#f59e0b'],
                        'water'           => ['label' => 'Water Bills', 'icon' => 'bi-droplet', 'color' => '#06b6d4'],
                        'gas'             => ['label' => 'Piped Gas Bills', 'icon' => 'bi-fire', 'color' => '#ec4899'],
                        'wallet_topup'    => ['label' => 'Wallet Topups', 'icon' => 'bi-wallet-fill', 'color' => '#10b981'],
                    ];

                    foreach ($serviceBreakdown as $sb): 
                        $cfg = $typeLabels[$sb['type']] ?? ['label' => ucfirst(str_replace('_', ' ', $sb['type'])), 'icon' => 'bi-tag', 'color' => '#94a3b8'];
                        $pct = $totalTransactions > 0 ? round(($sb['count'] / $totalTransactions) * 100, 1) : 0;
                    ?>
                        <div class="col-md-6">
                            <div class="p-3 rounded-3" style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.06);">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="text-white small fw-semibold d-flex align-items-center gap-2">
                                        <i class="bi <?php echo $cfg['icon']; ?>" style="color: <?php echo $cfg['color']; ?>;"></i>
                                        <?php echo $cfg['label']; ?>
                                    </span>
                                    <span class="badge bg-dark border border-secondary border-opacity-25 small"><?php echo $sb['count']; ?> Txns</span>
                                </div>
                                <div class="progress my-2" style="height: 6px; background-color: rgba(255,255,255,0.08);">
                                    <div class="progress-bar" role="progressbar" style="width: <?php echo $pct; ?>%; background-color: <?php echo $cfg['color']; ?>;" aria-valuenow="<?php echo $pct; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center text-secondary" style="font-size: 0.75rem;">
                                    <span><?php echo $pct; ?>% of total volume</span>
                                    <span class="text-white fw-medium"><?php echo formatCurrency($sb['volume']); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Recent Transactions Table (Last 10) -->
<div class="admin-card mb-4 overflow-hidden">
    <div class="p-3 px-4 border-bottom border-secondary border-opacity-10 d-flex justify-content-between align-items-center">
        <div>
            <h6 class="text-white fw-bold mb-0 d-flex align-items-center gap-2">
                <i class="bi bi-clock-history text-primary"></i> Recent Transactions (Last 10)
            </h6>
            <div class="text-secondary small">Real-time payment settlements & recharge requests</div>
        </div>
        <a href="<?php echo APP_URL; ?>/admin/transactions.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
            View All <i class="bi bi-arrow-right ms-1"></i>
        </a>
    </div>

    <div class="table-responsive">
        <table class="table admin-table mb-0">
            <thead>
                <tr>
                    <th>Txn ID</th>
                    <th>User</th>
                    <th>Service Type</th>
                    <th>Operator / Provider</th>
                    <th>Number</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Date & Time</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentTransactions)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4 text-secondary">
                            <i class="bi bi-receipt fs-3 d-block mb-1"></i>
                            No transactions recorded yet in database.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recentTransactions as $tx): ?>
                        <tr>
                            <td>
                                <span class="font-mono text-primary fw-semibold"><?php echo htmlspecialchars($tx['transaction_id'], ENT_QUOTES, 'UTF-8'); ?></span>
                            </td>
                            <td>
                                <div class="fw-semibold text-white small"><?php echo htmlspecialchars($tx['user_name'] ?? 'System User #' . $tx['user_id'], ENT_QUOTES, 'UTF-8'); ?></div>
                                <div class="text-muted" style="font-size: 0.75rem;"><?php echo htmlspecialchars($tx['user_email'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                            </td>
                            <td>
                                <?php
                                    $typeBadge = match($tx['type']) {
                                        'mobile_recharge' => '<span class="badge bg-primary-subtle text-primary border border-primary-subtle"><i class="bi bi-phone me-1"></i> Mobile</span>',
                                        'dth_recharge'    => '<span class="badge bg-purple-subtle text-light border border-secondary-subtle" style="background: rgba(147, 51, 234, 0.2);"><i class="bi bi-tv me-1"></i> DTH</span>',
                                        'electricity'     => '<span class="badge bg-warning-subtle text-warning border border-warning-subtle"><i class="bi bi-lightning-charge me-1"></i> Electricity</span>',
                                        'water'           => '<span class="badge bg-info-subtle text-info border border-info-subtle"><i class="bi bi-droplet me-1"></i> Water</span>',
                                        'gas'             => '<span class="badge bg-danger-subtle text-danger border border-danger-subtle"><i class="bi bi-fire me-1"></i> Gas</span>',
                                        'wallet_topup'    => '<span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-wallet2 me-1"></i> Top-up</span>',
                                        default           => '<span class="badge bg-secondary">' . htmlspecialchars($tx['type'], ENT_QUOTES, 'UTF-8') . '</span>'
                                    };
                                    echo $typeBadge;
                                ?>
                            </td>
                            <td>
                                <span class="text-light small"><?php echo htmlspecialchars($tx['operator_or_provider'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></span>
                            </td>
                            <td>
                                <span class="font-mono text-secondary small"><?php echo htmlspecialchars($tx['mobile_or_consumer_number'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></span>
                            </td>
                            <td>
                                <span class="fw-bold text-white"><?php echo formatCurrency($tx['amount']); ?></span>
                            </td>
                            <td>
                                <?php if ($tx['status'] === 'SUCCESS'): ?>
                                    <span class="badge-status-success"><i class="bi bi-check-circle-fill me-1"></i> SUCCESS</span>
                                <?php elseif ($tx['status'] === 'PENDING'): ?>
                                    <span class="badge-status-pending"><i class="bi bi-hourglass-split me-1"></i> PENDING</span>
                                <?php else: ?>
                                    <span class="badge-status-failed"><i class="bi bi-x-circle-fill me-1"></i> FAILED</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="text-secondary small font-mono"><?php echo date('d M Y, h:i A', strtotime($tx['created_at'])); ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Recent Registered Users Table (Last 10) -->
<div class="admin-card overflow-hidden">
    <div class="p-3 px-4 border-bottom border-secondary border-opacity-10 d-flex justify-content-between align-items-center">
        <div>
            <h6 class="text-white fw-bold mb-0 d-flex align-items-center gap-2">
                <i class="bi bi-people-fill text-info"></i> Recent Users (Last 10 Registered)
            </h6>
            <div class="text-secondary small">Newly registered customer accounts</div>
        </div>
        <a href="<?php echo APP_URL; ?>/admin/users.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
            Manage All Users <i class="bi bi-arrow-right ms-1"></i>
        </a>
    </div>

    <div class="table-responsive">
        <table class="table admin-table mb-0">
            <thead>
                <tr>
                    <th>User ID</th>
                    <th>Full Name</th>
                    <th>Email Address</th>
                    <th>Mobile Number</th>
                    <th>Wallet Balance</th>
                    <th>Account Status</th>
                    <th>Joined Date</th>
                    <th class="text-end">Quick Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentUsers)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4 text-secondary">
                            <i class="bi bi-person-x fs-3 d-block mb-1"></i>
                            No users registered yet.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recentUsers as $user): ?>
                        <tr>
                            <td>
                                <span class="font-mono text-secondary">#<?php echo $user['id']; ?></span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="admin-avatar" style="width: 32px; height: 32px; font-size: 0.8rem; background: rgba(79, 70, 229, 0.15); color: #818cf8;">
                                        <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                                    </div>
                                    <span class="fw-semibold text-white"><?php echo htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                                </div>
                            </td>
                            <td>
                                <span class="text-light small"><?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?></span>
                            </td>
                            <td>
                                <span class="font-mono text-secondary small"><?php echo htmlspecialchars($user['mobile'], ENT_QUOTES, 'UTF-8'); ?></span>
                            </td>
                            <td>
                                <span class="text-success fw-semibold font-mono"><?php echo formatCurrency($user['wallet_balance']); ?></span>
                            </td>
                            <td>
                                <?php if ($user['status'] === 'active'): ?>
                                    <span class="badge-status-active"><i class="bi bi-check-circle-fill me-1"></i> Active</span>
                                <?php else: ?>
                                    <span class="badge-status-inactive"><i class="bi bi-pause-circle-fill me-1"></i> Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="text-secondary small font-mono"><?php echo date('d M Y', strtotime($user['created_at'])); ?></span>
                            </td>
                            <td class="text-end">
                                <a href="<?php echo APP_URL; ?>/admin/users.php?search=<?php echo urlencode($user['email']); ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-3" title="Inspect user profile in User Directory">
                                    <i class="bi bi-eye me-1"></i> Inspect
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
