<?php
// Admin transactions

$pageTitle = 'Transactions Ledger';
$currentPage = 'transactions';

require_once __DIR__ . '/includes/admin-header.php';

$pdo = getDBConnection();

// Read Filter and Pagination Parameters
$search = trim($_GET['search'] ?? '');
$typeFilter = trim($_GET['type'] ?? 'all');
$statusFilter = trim($_GET['status'] ?? 'all');
$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo = trim($_GET['date_to'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Build WHERE Clause with Prepared Parameters
$whereClauses = [];
$params = [];

if (!empty($search)) {
    $whereClauses[] = "(t.transaction_id LIKE ? OR t.mobile_or_consumer_number LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)";
    $wildcard = "%{$search}%";
    $params[] = $wildcard;
    $params[] = $wildcard;
    $params[] = $wildcard;
    $params[] = $wildcard;
}

if (!empty($typeFilter) && $typeFilter !== 'all') {
    $whereClauses[] = "t.type = ?";
    $params[] = $typeFilter;
}

if (!empty($statusFilter) && $statusFilter !== 'all') {
    $whereClauses[] = "t.status = ?";
    $params[] = $statusFilter;
}

if (!empty($dateFrom)) {
    $whereClauses[] = "DATE(t.created_at) >= ?";
    $params[] = $dateFrom;
}

if (!empty($dateTo)) {
    $whereClauses[] = "DATE(t.created_at) <= ?";
    $params[] = $dateTo;
}

$whereSql = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

// 1. Calculate Aggregate Totals for the Filtered Subset
$statsQuery = "
    SELECT 
        COUNT(*) AS total_count,
        COALESCE(SUM(t.amount), 0.00) AS total_sum,
        COALESCE(SUM(CASE WHEN t.status = 'SUCCESS' THEN t.amount ELSE 0.00 END), 0.00) AS success_sum,
        COALESCE(SUM(CASE WHEN t.status = 'SUCCESS' THEN 1 ELSE 0 END), 0) AS success_count
    FROM transactions t
    LEFT JOIN users u ON t.user_id = u.id
    {$whereSql}
";
$statsStmt = $pdo->prepare($statsQuery);
$statsStmt->execute($params);
$aggregateStats = $statsStmt->fetch();

$totalRecords = (int)($aggregateStats['total_count'] ?? 0);
$filteredVolume = (float)($aggregateStats['total_sum'] ?? 0.00);
$filteredSuccessSum = (float)($aggregateStats['success_sum'] ?? 0.00);
$filteredSuccessCount = (int)($aggregateStats['success_count'] ?? 0);
$totalPages = max(1, (int)ceil($totalRecords / $perPage));

// 2. Fetch Filtered Transactions with User Join
$dataQuery = "
    SELECT 
        t.id, t.user_id, t.transaction_id, t.type, t.mobile_or_consumer_number,
        t.operator_or_provider, t.amount, t.status, t.payment_method, t.description, t.created_at,
        u.full_name AS user_name, u.email AS user_email, u.mobile AS user_mobile
    FROM transactions t
    LEFT JOIN users u ON t.user_id = u.id
    {$whereSql}
    ORDER BY t.id DESC
    LIMIT {$perPage} OFFSET {$offset}
";
$dataStmt = $pdo->prepare($dataQuery);
$dataStmt->execute($params);
$transactions = $dataStmt->fetchAll();
?>

<!-- Page Header & Action Controls -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h2 class="fw-bold text-white mb-1 tracking-tight">Transactions Management</h2>
        <p class="text-secondary small mb-0">Master transaction journal auditing recharges, bill payments, and wallet top-ups.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <span class="badge bg-secondary bg-opacity-25 text-light px-3 py-2 border border-secondary border-opacity-25">
            <i class="bi bi-clock-history me-1 text-primary"></i> Matching Records: <strong><?php echo number_format($totalRecords); ?></strong>
        </span>
    </div>
</div>

<!-- Filter Summary Strip -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card py-3 px-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-label small">Filtered Transactions</div>
                    <div class="fs-4 fw-bold text-white mt-1"><?php echo number_format($totalRecords); ?></div>
                </div>
                <div class="stat-icon info" style="width: 44px; height: 44px; font-size: 1.25rem;">
                    <i class="bi bi-arrow-left-right"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card py-3 px-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-label small">Filtered Settled Volume</div>
                    <div class="fs-4 fw-bold text-success mt-1"><?php echo formatCurrency($filteredSuccessSum); ?></div>
                </div>
                <div class="stat-icon success" style="width: 44px; height: 44px; font-size: 1.25rem;">
                    <i class="bi bi-currency-rupee"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card py-3 px-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-label small">Success Rate in View</div>
                    <div class="fs-4 fw-bold text-warning mt-1">
                        <?php echo $totalRecords > 0 ? round(($filteredSuccessCount / $totalRecords) * 100, 1) : 0; ?>%
                    </div>
                </div>
                <div class="stat-icon warning" style="width: 44px; height: 44px; font-size: 1.25rem;">
                    <i class="bi bi-check2-circle"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Comprehensive Multi-Attribute Filter Toolbar -->
<div class="admin-card p-3 mb-4">
    <form method="GET" action="<?php echo APP_URL; ?>/admin/transactions.php" class="row g-2 align-items-end">
        <!-- Search Keyword -->
        <div class="col-lg-3 col-md-6">
            <label class="form-label text-secondary small fw-semibold mb-1">Search Keyword</label>
            <div class="input-group">
                <span class="input-group-text bg-dark border-secondary border-opacity-50 text-secondary">
                    <i class="bi bi-search"></i>
                </span>
                <input type="text" 
                       name="search" 
                       class="form-control" 
                       placeholder="Txn ID, Mobile, User..." 
                       value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>">
            </div>
        </div>

        <!-- Service Type Filter -->
        <div class="col-lg-2 col-md-3">
            <label class="form-label text-secondary small fw-semibold mb-1">Service Type</label>
            <select name="type" class="form-select">
                <option value="all" <?php echo ($typeFilter === 'all') ? 'selected' : ''; ?>>All Types</option>
                <option value="mobile_recharge" <?php echo ($typeFilter === 'mobile_recharge') ? 'selected' : ''; ?>>Mobile Recharge</option>
                <option value="dth_recharge" <?php echo ($typeFilter === 'dth_recharge') ? 'selected' : ''; ?>>DTH Recharge</option>
                <option value="electricity" <?php echo ($typeFilter === 'electricity') ? 'selected' : ''; ?>>Electricity Bill</option>
                <option value="water" <?php echo ($typeFilter === 'water') ? 'selected' : ''; ?>>Water Bill</option>
                <option value="gas" <?php echo ($typeFilter === 'gas') ? 'selected' : ''; ?>>Piped Gas</option>
                <option value="wallet_topup" <?php echo ($typeFilter === 'wallet_topup') ? 'selected' : ''; ?>>Wallet Top-up</option>
            </select>
        </div>

        <!-- Status Filter -->
        <div class="col-lg-2 col-md-3">
            <label class="form-label text-secondary small fw-semibold mb-1">Status</label>
            <select name="status" class="form-select">
                <option value="all" <?php echo ($statusFilter === 'all') ? 'selected' : ''; ?>>All Statuses</option>
                <option value="SUCCESS" <?php echo ($statusFilter === 'SUCCESS') ? 'selected' : ''; ?>>SUCCESS</option>
                <option value="PENDING" <?php echo ($statusFilter === 'PENDING') ? 'selected' : ''; ?>>PENDING</option>
                <option value="FAILED" <?php echo ($statusFilter === 'FAILED') ? 'selected' : ''; ?>>FAILED</option>
            </select>
        </div>

        <!-- Date Range: From -->
        <div class="col-lg-2 col-md-3">
            <label class="form-label text-secondary small fw-semibold mb-1">From Date</label>
            <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($dateFrom, ENT_QUOTES, 'UTF-8'); ?>">
        </div>

        <!-- Date Range: To -->
        <div class="col-lg-2 col-md-3">
            <label class="form-label text-secondary small fw-semibold mb-1">To Date</label>
            <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($dateTo, ENT_QUOTES, 'UTF-8'); ?>">
        </div>

        <!-- Filter Action Buttons -->
        <div class="col-lg-1 col-md-6 d-flex gap-1">
            <button type="submit" class="btn btn-primary w-100 rounded-3" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); border: none;" title="Apply search and filters">
                <i class="bi bi-funnel-fill"></i>
            </button>
            <?php if (!empty($search) || $typeFilter !== 'all' || $statusFilter !== 'all' || !empty($dateFrom) || !empty($dateTo)): ?>
                <a href="<?php echo APP_URL; ?>/admin/transactions.php" class="btn btn-outline-secondary rounded-3" title="Clear all active filters">
                    <i class="bi bi-x-lg"></i>
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Transactions Table Card -->
<div class="admin-card overflow-hidden mb-4">
    <div class="table-responsive">
        <table class="table admin-table mb-0">
            <thead>
                <tr>
                    <th>Txn ID</th>
                    <th>Customer / User</th>
                    <th>Type</th>
                    <th>Operator / Board</th>
                    <th>Number / Identifier</th>
                    <th>Amount</th>
                    <th>Payment Via</th>
                    <th>Status</th>
                    <th>Timestamp</th>
                    <th class="text-end" style="width: 100px;">Details</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($transactions)): ?>
                    <tr>
                        <td colspan="10" class="text-center py-5 text-secondary">
                            <i class="bi bi-journal-x fs-1 d-block mb-2 text-muted"></i>
                            <div class="fw-semibold">No transactions found matching your filter selection.</div>
                            <div class="small text-muted mt-1">Try resetting the date range or search keyword.</div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($transactions as $tx): ?>
                        <tr>
                            <td>
                                <span class="font-mono text-primary fw-semibold"><?php echo htmlspecialchars($tx['transaction_id'], ENT_QUOTES, 'UTF-8'); ?></span>
                            </td>
                            <td>
                                <div class="fw-bold text-white small"><?php echo htmlspecialchars($tx['user_name'] ?? 'User #' . $tx['user_id'], ENT_QUOTES, 'UTF-8'); ?></div>
                                <div class="text-muted" style="font-size: 0.75rem;"><?php echo htmlspecialchars($tx['user_email'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                            </td>
                            <td>
                                <?php
                                    $badge = match($tx['type']) {
                                        'mobile_recharge' => '<span class="badge bg-primary-subtle text-primary border border-primary-subtle"><i class="bi bi-phone me-1"></i> Mobile</span>',
                                        'dth_recharge'    => '<span class="badge bg-purple-subtle text-light border border-secondary-subtle" style="background: rgba(147, 51, 234, 0.2);"><i class="bi bi-tv me-1"></i> DTH</span>',
                                        'electricity'     => '<span class="badge bg-warning-subtle text-warning border border-warning-subtle"><i class="bi bi-lightning-charge me-1"></i> Electricity</span>',
                                        'water'           => '<span class="badge bg-info-subtle text-info border border-info-subtle"><i class="bi bi-droplet me-1"></i> Water</span>',
                                        'gas'             => '<span class="badge bg-danger-subtle text-danger border border-danger-subtle"><i class="bi bi-fire me-1"></i> Gas</span>',
                                        'wallet_topup'    => '<span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-wallet2 me-1"></i> Top-up</span>',
                                        default           => '<span class="badge bg-secondary">' . htmlspecialchars($tx['type'], ENT_QUOTES, 'UTF-8') . '</span>'
                                    };
                                    echo $badge;
                                ?>
                            </td>
                            <td>
                                <span class="text-light small text-truncate d-inline-block" style="max-width: 140px;" title="<?php echo htmlspecialchars($tx['operator_or_provider'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars($tx['operator_or_provider'] ?? '—', ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </td>
                            <td>
                                <span class="font-mono text-secondary small"><?php echo htmlspecialchars($tx['mobile_or_consumer_number'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></span>
                            </td>
                            <td>
                                <span class="fw-bold text-white font-mono"><?php echo formatCurrency($tx['amount']); ?></span>
                            </td>
                            <td>
                                <span class="badge bg-dark border border-secondary border-opacity-25 text-light small">
                                    <i class="bi bi-credit-card-2-front me-1 text-secondary"></i>
                                    <?php echo htmlspecialchars($tx['payment_method'], ENT_QUOTES, 'UTF-8'); ?>
                                </span>
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
                            <td class="text-end">
                                <button type="button" 
                                        class="btn btn-sm btn-outline-secondary rounded-pill px-3 view-tx-btn"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#txnDetailModal"
                                        data-id="<?php echo $tx['id']; ?>"
                                        data-txnid="<?php echo htmlspecialchars($tx['transaction_id'], ENT_QUOTES, 'UTF-8'); ?>"
                                        data-username="<?php echo htmlspecialchars($tx['user_name'] ?? 'User #' . $tx['user_id'], ENT_QUOTES, 'UTF-8'); ?>"
                                        data-useremail="<?php echo htmlspecialchars($tx['user_email'] ?? '—', ENT_QUOTES, 'UTF-8'); ?>"
                                        data-usermobile="<?php echo htmlspecialchars($tx['user_mobile'] ?? '—', ENT_QUOTES, 'UTF-8'); ?>"
                                        data-type="<?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $tx['type'])), ENT_QUOTES, 'UTF-8'); ?>"
                                        data-provider="<?php echo htmlspecialchars($tx['operator_or_provider'] ?? '—', ENT_QUOTES, 'UTF-8'); ?>"
                                        data-number="<?php echo htmlspecialchars($tx['mobile_or_consumer_number'] ?? '—', ENT_QUOTES, 'UTF-8'); ?>"
                                        data-amount="<?php echo formatCurrency($tx['amount']); ?>"
                                        data-status="<?php echo $tx['status']; ?>"
                                        data-method="<?php echo htmlspecialchars($tx['payment_method'], ENT_QUOTES, 'UTF-8'); ?>"
                                        data-desc="<?php echo htmlspecialchars($tx['description'] ?? 'No description provided.', ENT_QUOTES, 'UTF-8'); ?>"
                                        data-date="<?php echo date('l, d F Y, h:i:s A', strtotime($tx['created_at'])); ?>"
                                        title="View comprehensive audit details">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Table Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="p-3 border-top border-secondary border-opacity-10 d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
            <div class="text-secondary small">
                Showing Page <strong class="text-white"><?php echo $page; ?></strong> of <strong class="text-white"><?php echo $totalPages; ?></strong> (<?php echo $totalRecords; ?> filtered records)
            </div>
            <nav aria-label="Transactions pagination">
                <ul class="pagination pagination-sm mb-0">
                    <!-- Previous -->
                    <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link bg-dark border-secondary border-opacity-25 text-light" href="<?php echo APP_URL; ?>/admin/transactions.php?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                            <i class="bi bi-chevron-left"></i> Prev
                        </a>
                    </li>

                    <!-- Page Numbers -->
                    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                        <?php if ($p == 1 || $p == $totalPages || ($p >= $page - 2 && $p <= $page + 2)): ?>
                            <li class="page-item <?php echo ($p === $page) ? 'active' : ''; ?>">
                                <a class="page-link <?php echo ($p === $page) ? 'bg-primary border-primary text-white' : 'bg-dark border-secondary border-opacity-25 text-light'; ?>" 
                                   href="<?php echo APP_URL; ?>/admin/transactions.php?<?php echo http_build_query(array_merge($_GET, ['page' => $p])); ?>">
                                    <?php echo $p; ?>
                                </a>
                            </li>
                        <?php elseif ($p == $page - 3 || $p == $page + 3): ?>
                            <li class="page-item disabled"><span class="page-link bg-dark border-secondary border-opacity-25 text-secondary">&hellip;</span></li>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <!-- Next -->
                    <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                        <a class="page-link bg-dark border-secondary border-opacity-25 text-light" href="<?php echo APP_URL; ?>/admin/transactions.php?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                            Next <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>

<!-- Modal: Comprehensive Transaction Audit Details -->
<div class="modal fade" id="txnDetailModal" tabindex="-1" aria-labelledby="txnDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold text-white d-flex align-items-center gap-2" id="txnDetailModalLabel">
                    <i class="bi bi-receipt text-primary"></i> Transaction Audit Dossier
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="modalPrintArea">
                <!-- Status & Amount Banner -->
                <div class="p-3 rounded-3 mb-3 d-flex justify-content-between align-items-center" style="background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.08);">
                    <div>
                        <div class="text-secondary small">Transaction Amount</div>
                        <div class="fs-2 fw-bold text-white mt-1 font-mono" id="modalTxnAmount">₹0.00</div>
                        <div class="text-muted small" id="modalTxnId">—</div>
                    </div>
                    <div id="modalTxnStatus">
                        <span class="badge-status-success">SUCCESS</span>
                    </div>
                </div>

                <!-- Attributes Grid -->
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <div class="p-2 rounded bg-dark border border-secondary border-opacity-25">
                            <div class="text-secondary small">Customer Account</div>
                            <div class="text-white fw-bold" id="modalTxnUser">—</div>
                            <div class="text-muted small" id="modalTxnUserEmail">—</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-2 rounded bg-dark border border-secondary border-opacity-25">
                            <div class="text-secondary small">Service Category</div>
                            <div class="text-white fw-semibold" id="modalTxnType">—</div>
                            <div class="text-muted small" id="modalTxnProvider">—</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-2 rounded bg-dark border border-secondary border-opacity-25">
                            <div class="text-secondary small">Account / Consumer / Mobile #</div>
                            <div class="text-white fw-bold font-mono" id="modalTxnNumber">—</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-2 rounded bg-dark border border-secondary border-opacity-25">
                            <div class="text-secondary small">Payment Instrument</div>
                            <div class="text-white fw-semibold" id="modalTxnMethod">—</div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="p-2 rounded bg-dark border border-secondary border-opacity-25">
                            <div class="text-secondary small">Description / Pack Information</div>
                            <div class="text-light small" id="modalTxnDesc">—</div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="p-2 rounded bg-dark border border-secondary border-opacity-25">
                            <div class="text-secondary small">Settlement Timestamp</div>
                            <div class="text-white small font-mono" id="modalTxnDate">—</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer d-flex justify-content-between">
                <button type="button" class="btn btn-outline-info rounded-pill px-3" onclick="window.print()">
                    <i class="bi bi-printer me-1"></i> Print Record
                </button>
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const txnModal = document.getElementById('txnDetailModal');
    if (txnModal) {
        txnModal.addEventListener('show.bs.modal', function (event) {
            const btn = event.relatedTarget;
            if (!btn) return;

            document.getElementById('modalTxnId').textContent = 'Reference ID: ' + btn.getAttribute('data-txnid');
            document.getElementById('modalTxnAmount').textContent = btn.getAttribute('data-amount');
            document.getElementById('modalTxnUser').textContent = btn.getAttribute('data-username');
            document.getElementById('modalTxnUserEmail').textContent = btn.getAttribute('data-useremail') + ' | ' + btn.getAttribute('data-usermobile');
            document.getElementById('modalTxnType').textContent = btn.getAttribute('data-type');
            document.getElementById('modalTxnProvider').textContent = btn.getAttribute('data-provider');
            document.getElementById('modalTxnNumber').textContent = btn.getAttribute('data-number');
            document.getElementById('modalTxnMethod').textContent = btn.getAttribute('data-method');
            document.getElementById('modalTxnDesc').textContent = btn.getAttribute('data-desc');
            document.getElementById('modalTxnDate').textContent = btn.getAttribute('data-date');

            const status = btn.getAttribute('data-status');
            const statusContainer = document.getElementById('modalTxnStatus');
            if (status === 'SUCCESS') {
                statusContainer.innerHTML = '<span class="badge-status-success fs-6"><i class="bi bi-check-circle-fill me-1"></i> SUCCESS</span>';
            } else if (status === 'PENDING') {
                statusContainer.innerHTML = '<span class="badge-status-pending fs-6"><i class="bi bi-hourglass-split me-1"></i> PENDING</span>';
            } else {
                statusContainer.innerHTML = '<span class="badge-status-failed fs-6"><i class="bi bi-x-circle-fill me-1"></i> FAILED</span>';
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
