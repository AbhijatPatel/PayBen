<?php
// transactions page

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';

// Enforce authenticated user session
requireLogin();

$currentUser = getCurrentUser();
$userId = (int)$currentUser['id'];
$pdo = getDBConnection();

// -----------------------------------------------------------------------------
// Filter Input Sanitization
// -----------------------------------------------------------------------------
$filterType = sanitize($_GET['type'] ?? 'all');
$filterStatus = sanitize($_GET['status'] ?? 'all');
$searchQuery = trim($_GET['search'] ?? '');
$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo = trim($_GET['date_to'] ?? '');

// Validate date formats if provided (YYYY-MM-DD)
if (!empty($dateFrom) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
    $dateFrom = '';
}
if (!empty($dateTo) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
    $dateTo = '';
}

// -----------------------------------------------------------------------------
// Dynamic SQL Query Builder (Prepared Statements)
// -----------------------------------------------------------------------------
$whereConditions = ["user_id = :user_id"];
$params = [':user_id' => $userId];

// 1. Transaction Type Filter
$validTypes = ['mobile_recharge', 'dth_recharge', 'electricity', 'water', 'gas', 'wallet_topup'];
if (!empty($filterType) && in_array($filterType, $validTypes, true)) {
    $whereConditions[] = "type = :type";
    $params[':type'] = $filterType;
} else {
    $filterType = 'all';
}

// 2. Status Filter
$validStatuses = ['SUCCESS', 'PENDING', 'FAILED'];
if (!empty($filterStatus) && in_array(strtoupper($filterStatus), $validStatuses, true)) {
    $whereConditions[] = "status = :status";
    $params[':status'] = strtoupper($filterStatus);
} else {
    $filterStatus = 'all';
}

// 3. Search Query Filter (Matches Transaction ID, Mobile/Consumer Number, or Operator)
if (!empty($searchQuery)) {
    $whereConditions[] = "(transaction_id LIKE :search_id OR mobile_or_consumer_number LIKE :search_num OR operator_or_provider LIKE :search_op OR description LIKE :search_desc)";
    $params[':search_id'] = '%' . $searchQuery . '%';
    $params[':search_num'] = '%' . $searchQuery . '%';
    $params[':search_op'] = '%' . $searchQuery . '%';
    $params[':search_desc'] = '%' . $searchQuery . '%';
}

// 4. Date Range Filters
if (!empty($dateFrom)) {
    $whereConditions[] = "created_at >= :date_from";
    $params[':date_from'] = $dateFrom . ' 00:00:00';
}
if (!empty($dateTo)) {
    $whereConditions[] = "created_at <= :date_to";
    $params[':date_to'] = $dateTo . ' 23:59:59';
}

$whereSql = implode(' AND ', $whereConditions);

// -----------------------------------------------------------------------------
// Summary Totals for User
// -----------------------------------------------------------------------------
$userStatsStmt = $pdo->prepare(
    "SELECT 
        COUNT(*) AS total_count,
        COALESCE(SUM(CASE WHEN status = 'SUCCESS' THEN amount ELSE 0 END), 0) AS total_success_amount,
        COALESCE(SUM(CASE WHEN status = 'SUCCESS' THEN 1 ELSE 0 END), 0) AS success_count,
        COALESCE(SUM(CASE WHEN status = 'PENDING' THEN 1 ELSE 0 END), 0) AS pending_count
     FROM transactions 
     WHERE user_id = ?"
);
$userStatsStmt->execute([$userId]);
$userStats = $userStatsStmt->fetch() ?: ['total_count' => 0, 'total_success_amount' => 0, 'success_count' => 0, 'pending_count' => 0];

// -----------------------------------------------------------------------------
// Pagination & Record Retrieval
// -----------------------------------------------------------------------------
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE {$whereSql}");
foreach ($params as $key => $val) {
    $countStmt->bindValue($key, $val);
}
$countStmt->execute();
$totalRecords = (int)$countStmt->fetchColumn();

$perPage = 20;
$totalPages = max(1, (int)ceil($totalRecords / $perPage));
$page = isset($_GET['page']) ? max(1, min((int)$_GET['page'], $totalPages)) : 1;
$offset = ($page - 1) * $perPage;

$query = "SELECT * FROM transactions WHERE {$whereSql} ORDER BY created_at DESC, id DESC LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($query);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$transactions = $stmt->fetchAll();

// Helper: Format type labels and icons
function getTransactionTypeInfo(string $type): array {
    return match($type) {
        'mobile_recharge' => ['label' => 'Mobile Recharge', 'icon' => 'bi-phone', 'color' => 'text-primary'],
        'dth_recharge'    => ['label' => 'DTH Recharge', 'icon' => 'bi-tv', 'color' => 'text-info'],
        'electricity'     => ['label' => 'Electricity Bill', 'icon' => 'bi-lightning-charge', 'color' => 'text-warning'],
        'water'           => ['label' => 'Water Bill', 'icon' => 'bi-droplet', 'color' => 'text-primary'],
        'gas'             => ['label' => 'Piped Gas Bill', 'icon' => 'bi-fire', 'color' => 'text-danger'],
        'wallet_topup'    => ['label' => 'Wallet Top-up', 'icon' => 'bi-wallet2', 'color' => 'text-success'],
        default           => ['label' => ucwords(str_replace('_', ' ', $type)), 'icon' => 'bi-receipt', 'color' => 'text-secondary']
    };
}

$pageTitle = 'Transaction History & Receipts';
$currentPage = 'transactions';

require_once dirname(__DIR__) . '/includes/dashboard_layout_top.php';
?>

<!-- Receipt Print Specific CSS -->
<style>
@media print {
    /* Hide all non-receipt elements */
    body * {
        visibility: hidden !important;
    }
    #receiptPrintArea, #receiptPrintArea * {
        visibility: visible !important;
    }
    #receiptPrintArea {
        position: absolute;
        left: 0;
        top: 0;
        width: 100% !important;
        margin: 0 !important;
        padding: 24px !important;
        background: #ffffff !important;
        color: #000000 !important;
    }
    .no-print, .modal-header, .modal-footer, .app-header, .sidebar, .sidebar-overlay, .app-footer {
        display: none !important;
    }
    .modal {
        position: static;
        overflow: visible;
        background: none;
    }
    .modal-dialog {
        max-width: 100% !important;
        margin: 0 !important;
    }
    .modal-content {
        border: none !important;
        box-shadow: none !important;
    }
}
</style>

<div class="container-fluid px-0">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-primary bg-opacity-10 text-primary px-2 py-1">
                    <i class="bi bi-clock-history me-1"></i> Audit Trail
                </span>
                <span class="badge bg-success bg-opacity-10 text-success px-2 py-1">
                    <i class="bi bi-patch-check-fill me-1"></i> BBPS Certified
                </span>
            </div>
            <h1 class="h3 fw-bold text-dark mb-0">Transaction History</h1>
            <p class="text-muted small mb-0">Search, filter, and download printable tax receipts for all payments.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= APP_URL ?>/pages/wallet.php" class="btn btn-primary px-3 py-2 rounded-pill shadow-sm">
                <i class="bi bi-wallet2 me-1"></i> My Wallet
            </a>
        </div>
    </div>

    <!-- Summary Metrics Grid -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100">
                <span class="text-muted small fw-semibold text-uppercase">Total Orders</span>
                <div class="h4 fw-bold text-dark mt-1 mb-0"><?= number_format($userStats['total_count']) ?></div>
                <span class="text-muted small mt-1">All time lifetime count</span>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100">
                <span class="text-muted small fw-semibold text-uppercase">Total Paid</span>
                <div class="h4 fw-bold text-primary mt-1 mb-0"><?= formatCurrency($userStats['total_success_amount']) ?></div>
                <span class="text-muted small mt-1">Successful transactions</span>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100">
                <span class="text-muted small fw-semibold text-uppercase">Successful</span>
                <div class="h4 fw-bold text-success mt-1 mb-0"><?= number_format($userStats['success_count']) ?></div>
                <span class="text-muted small mt-1">Completed settlements</span>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100">
                <span class="text-muted small fw-semibold text-uppercase">Pending / In-Review</span>
                <div class="h4 fw-bold text-warning mt-1 mb-0"><?= number_format($userStats['pending_count']) ?></div>
                <span class="text-muted small mt-1">Awaiting provider sync</span>
            </div>
        </div>
    </div>
    <div class="card border-0 shadow-sm rounded-3 bg-white mb-4">
        <div class="card-body p-3 p-md-4">
            <form action="<?= APP_URL ?>/pages/transactions.php" method="GET" class="row g-3 align-items-end" id="transactionFilterForm">
                <!-- Transaction Type Dropdown -->
                <div class="col-12 col-sm-6 col-md-3 col-xl-2">
                    <label for="filterType" class="form-label small fw-bold text-secondary mb-1">
                        <i class="bi bi-grid me-1"></i> Service Type
                    </label>
                    <select name="type" id="filterType" class="form-select form-select-sm">
                        <option value="all" <?= ($filterType === 'all') ? 'selected' : '' ?>>All Services</option>
                        <option value="mobile_recharge" <?= ($filterType === 'mobile_recharge') ? 'selected' : '' ?>>Mobile Recharge</option>
                        <option value="dth_recharge" <?= ($filterType === 'dth_recharge') ? 'selected' : '' ?>>DTH Recharge</option>
                        <option value="electricity" <?= ($filterType === 'electricity') ? 'selected' : '' ?>>Electricity Bill</option>
                        <option value="water" <?= ($filterType === 'water') ? 'selected' : '' ?>>Water Bill</option>
                        <option value="gas" <?= ($filterType === 'gas') ? 'selected' : '' ?>>Piped Gas</option>
                        <option value="wallet_topup" <?= ($filterType === 'wallet_topup') ? 'selected' : '' ?>>Wallet Top-up</option>
                    </select>
                </div>

                <!-- Status Dropdown -->
                <div class="col-12 col-sm-6 col-md-3 col-xl-2">
                    <label for="filterStatus" class="form-label small fw-bold text-secondary mb-1">
                        <i class="bi bi-flag me-1"></i> Status
                    </label>
                    <select name="status" id="filterStatus" class="form-select form-select-sm">
                        <option value="all" <?= ($filterStatus === 'all') ? 'selected' : '' ?>>All Statuses</option>
                        <option value="SUCCESS" <?= ($filterStatus === 'SUCCESS') ? 'selected' : '' ?>>SUCCESS</option>
                        <option value="PENDING" <?= ($filterStatus === 'PENDING') ? 'selected' : '' ?>>PENDING</option>
                        <option value="FAILED" <?= ($filterStatus === 'FAILED') ? 'selected' : '' ?>>FAILED</option>
                    </select>
                </div>

                <!-- Search Input -->
                <div class="col-12 col-sm-6 col-md-3 col-xl-3">
                    <label for="searchQuery" class="form-label small fw-bold text-secondary mb-1">
                        <i class="bi bi-search me-1"></i> Transaction ID / Number
                    </label>
                    <input type="text" 
                           class="form-control form-control-sm" 
                           id="searchQuery" 
                           name="search" 
                           value="<?= sanitize($searchQuery) ?>" 
                           placeholder="e.g. TXN..., 98765...">
                </div>

                <!-- Date Range: From -->
                <div class="col-6 col-sm-3 col-md-3 col-xl-2">
                    <label for="dateFrom" class="form-label small fw-bold text-secondary mb-1">
                        <i class="bi bi-calendar-event me-1"></i> From
                    </label>
                    <input type="date" 
                           class="form-control form-control-sm" 
                           id="dateFrom" 
                           name="date_from" 
                           value="<?= sanitize($dateFrom) ?>">
                </div>

                <!-- Date Range: To -->
                <div class="col-6 col-sm-3 col-md-3 col-xl-2">
                    <label for="dateTo" class="form-label small fw-bold text-secondary mb-1">
                        <i class="bi bi-calendar-check me-1"></i> To
                    </label>
                    <input type="date" 
                           class="form-control form-control-sm" 
                           id="dateTo" 
                           name="date_to" 
                           value="<?= sanitize($dateTo) ?>">
                </div>

                <!-- Action Buttons: Search & Reset -->
                <div class="col-12 col-sm-6 col-md-3 col-xl-1 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold" title="Apply Filters">
                        <i class="bi bi-funnel-fill me-1"></i> Search
                    </button>
                    <?php if ($filterType !== 'all' || $filterStatus !== 'all' || !empty($searchQuery) || !empty($dateFrom) || !empty($dateTo)): ?>
                        <a href="<?= APP_URL ?>/pages/transactions.php" class="btn btn-outline-secondary btn-sm" title="Clear Filters">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden bg-white">
        <div class="card-header bg-white border-bottom py-3 px-3 px-md-4 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 text-dark">
                <i class="bi bi-list-columns-reverse text-primary me-2"></i> Payment Records
            </h5>
            <span class="badge bg-light text-secondary border px-3 py-2">
                Matching: <strong><?= number_format($totalRecords) ?></strong>
            </span>
        </div>

        <div class="card-body p-0">
            <?php if (empty($transactions)): ?>
                <div class="text-center py-5 px-3">
                    <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                        <i class="bi bi-search fs-1 text-muted"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">No Transactions Found</h5>
                    <p class="text-muted small mx-auto mb-3" style="max-width: 440px;">
                        No transaction records matched your filter criteria. Try clearing the filters or searching with a different transaction ID or date range.
                    </p>
                    <div class="d-flex justify-content-center gap-2">
                        <a href="<?= APP_URL ?>/pages/transactions.php" class="btn btn-outline-primary rounded-pill px-4">
                            <i class="bi bi-arrow-clockwise me-1"></i> Reset Filters
                        </a>
                        <a href="<?= APP_URL ?>/pages/recharge.php" class="btn btn-primary rounded-pill px-4">
                            <i class="bi bi-phone me-1"></i> Make a Recharge
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col" class="ps-3 ps-md-4">Transaction ID</th>
                                <th scope="col">Type</th>
                                <th scope="col">Number / Consumer</th>
                                <th scope="col">Operator / Provider</th>
                                <th scope="col" class="text-end">Amount</th>
                                <th scope="col" class="text-center">Status</th>
                                <th scope="col">Date &amp; Time</th>
                                <th scope="col" class="text-center pe-3 pe-md-4">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $tx): ?>
                                <?php
                                $typeInfo = getTransactionTypeInfo($tx['type']);
                                $status = strtoupper($tx['status']);
                                $badgeClass = match($status) {
                                    'SUCCESS' => 'badge-status-success',
                                    'PENDING' => 'badge-status-pending',
                                    'FAILED'  => 'badge-status-failed',
                                    default   => 'badge-secondary'
                                };
                                $statusIcon = match($status) {
                                    'SUCCESS' => 'bi-check-circle-fill',
                                    'PENDING' => 'bi-hourglass-split',
                                    'FAILED'  => 'bi-x-circle-fill',
                                    default   => 'bi-info-circle-fill'
                                };

                                // JSON payload for modal viewer
                                $jsonTx = htmlspecialchars(json_encode([
                                    'id' => $tx['id'],
                                    'transaction_id' => $tx['transaction_id'],
                                    'type' => $tx['type'],
                                    'type_label' => $typeInfo['label'],
                                    'type_icon' => $typeInfo['icon'],
                                    'number' => $tx['mobile_or_consumer_number'],
                                    'provider' => $tx['operator_or_provider'],
                                    'amount' => (float)$tx['amount'],
                                    'formatted_amount' => formatCurrency($tx['amount']),
                                    'status' => $status,
                                    'badge_class' => $badgeClass,
                                    'status_icon' => $statusIcon,
                                    'payment_method' => $tx['payment_method'],
                                    'description' => $tx['description'] ?? 'Standard transaction settlement',
                                    'created_at' => $tx['created_at'],
                                    'formatted_date' => date('d M Y, h:i A', strtotime($tx['created_at'])),
                                    'user_name' => $currentUser['full_name'] ?? 'PayBen Customer'
                                ]), ENT_QUOTES, 'UTF-8');
                                ?>
                                <tr>
                                    <!-- Transaction ID -->
                                    <td class="ps-3 ps-md-4">
                                        <div class="d-flex align-items-center gap-1">
                                            <code class="fw-bold text-primary small bg-light px-2 py-1 rounded">
                                                <?= sanitize($tx['transaction_id']) ?>
                                            </code>
                                        </div>
                                    </td>

                                    <!-- Service Type -->
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="bi <?= $typeInfo['icon'] ?> <?= $typeInfo['color'] ?> fs-5"></i>
                                            <span class="fw-semibold text-dark"><?= $typeInfo['label'] ?></span>
                                        </div>
                                    </td>

                                    <!-- Mobile / Consumer Number -->
                                    <td>
                                        <span class="text-dark fw-semibold"><?= sanitize($tx['mobile_or_consumer_number']) ?></span>
                                    </td>

                                    <!-- Operator / Provider -->
                                    <td>
                                        <div class="text-truncate text-secondary" style="max-width: 220px;" title="<?= sanitize($tx['operator_or_provider']) ?>">
                                            <?= sanitize($tx['operator_or_provider']) ?>
                                        </div>
                                    </td>

                                    <!-- Amount -->
                                    <td class="text-end text-nowrap">
                                        <span class="fw-bold fs-6 text-dark">
                                            <?= formatCurrency($tx['amount']) ?>
                                        </span>
                                    </td>

                                    <!-- Status -->
                                    <td class="text-center">
                                        <span class="badge <?= $badgeClass ?>">
                                            <i class="bi <?= $statusIcon ?>"></i>
                                            <?= $status ?>
                                        </span>
                                    </td>

                                    <!-- Date & Time -->
                                    <td class="text-nowrap">
                                        <div class="text-dark small fw-semibold"><?= date('d M Y', strtotime($tx['created_at'])) ?></div>
                                        <div class="text-muted" style="font-size: 0.75rem;"><?= date('h:i A', strtotime($tx['created_at'])) ?></div>
                                    </td>

                                    <!-- Action: View Button -->
                                    <td class="text-center pe-3 pe-md-4">
                                        <button type="button" 
                                                class="btn btn-outline-primary btn-sm rounded-pill px-3 view-tx-btn" 
                                                data-tx='<?= $jsonTx ?>'
                                                title="View Details & Receipt">
                                            <i class="bi bi-eye me-1"></i> View
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer -->
                <?php if ($totalPages > 1): ?>
                    <div class="card-footer bg-white border-top py-3 px-3 px-md-4 d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
                        <div class="text-muted small">
                            Showing <strong><?= ($offset + 1) ?></strong> to <strong><?= min($offset + $perPage, $totalRecords) ?></strong> of <strong><?= $totalRecords ?></strong> records
                        </div>
                        <nav aria-label="Transactions Pagination">
                            <ul class="pagination pagination-sm mb-0">
                                <?php
                                // Preserve GET query parameters except page
                                $queryParams = $_GET;
                                unset($queryParams['page']);
                                $buildUrl = function($p) use ($queryParams) {
                                    $queryParams['page'] = $p;
                                    return '?' . http_build_query($queryParams);
                                };
                                ?>

                                <!-- Previous -->
                                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                    <a class="page-link" href="<?= $buildUrl($page - 1) ?>" aria-label="Previous">
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </li>

                                <!-- Numbers -->
                                <?php
                                $startP = max(1, $page - 2);
                                $endP = min($totalPages, $page + 2);
                                for ($p = $startP; $p <= $endP; $p++):
                                ?>
                                    <li class="page-item <?= ($p === $page) ? 'active' : '' ?>">
                                        <a class="page-link" href="<?= $buildUrl($p) ?>"><?= $p ?></a>
                                    </li>
                                <?php endfor; ?>

                                <!-- Next -->
                                <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                                    <a class="page-link" href="<?= $buildUrl($page + 1) ?>" aria-label="Next">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ===========================================================================
     3. Transaction Detail & Printable Tax Receipt Modal
     =========================================================================== -->
<div class="modal fade" id="transactionDetailModal" tabindex="-1" aria-labelledby="transactionDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-light border-bottom py-3 px-4">
                <div class="d-flex align-items-center gap-2">
                    <span class="navbar-brand-logo" style="width: 32px; height: 32px; font-size: 1rem;">
                        <i class="bi bi-lightning-charge-fill"></i>
                    </span>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0" id="transactionDetailModalLabel">Transaction Details</h5>
                        <span class="text-muted small">Official PayBen Payment Voucher</span>
                    </div>
                </div>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="receiptPrintArea">
                <!-- Receipt Official Header (Visible on screen and print) -->
                <div class="d-flex justify-content-between align-items-start border-bottom pb-3 mb-4">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="fw-bold fs-4 text-dark">Pay<span class="text-primary">Ben</span></span>
                            <span class="badge bg-secondary bg-opacity-10 text-secondary border small">TAX INVOICE</span>
                        </div>
                        <p class="text-muted small mb-0">PayBen Technologies Pvt. Ltd. &bull; GSTIN: 27AABCP1234F1Z5</p>
                        <p class="text-muted small mb-0">Fintech Tower, BKC, Mumbai &bull; support@payben.com</p>
                    </div>
                    <div class="text-end">
                        <div id="modalStatusBadge" class="badge mb-2 fs-6">SUCCESS</div>
                        <div class="text-muted small">Receipt Date:</div>
                        <div class="fw-bold text-dark small" id="modalReceiptDate">-</div>
                    </div>
                </div>

                <!-- Prominent Amount Display -->
                <div class="text-center p-3 rounded-4 bg-light border mb-4">
                    <span class="text-muted small text-uppercase fw-semibold d-block mb-1">Total Amount Paid</span>
                    <h2 class="display-6 fw-bold text-dark mb-0" id="modalAmount">₹0.00</h2>
                    <span class="text-success small fw-semibold" id="modalPaymentStatusSubtext">
                        <i class="bi bi-check-circle-fill me-1"></i> Payment Settled Successfully
                    </span>
                </div>

                <!-- Comprehensive Details Table -->
                <div class="table-responsive mb-4">
                    <table class="table table-bordered mb-0">
                        <tbody>
                            <tr>
                                <th class="bg-light text-secondary small w-35">Transaction Reference ID</th>
                                <td class="fw-bold text-primary font-monospace" id="modalTxId">-</td>
                            </tr>
                            <tr>
                                <th class="bg-light text-secondary small">Service Category</th>
                                <td class="fw-semibold text-dark" id="modalServiceType">-</td>
                            </tr>
                            <tr>
                                <th class="bg-light text-secondary small">Service Provider / Operator</th>
                                <td class="fw-semibold text-dark" id="modalProvider">-</td>
                            </tr>
                            <tr>
                                <th class="bg-light text-secondary small">Mobile / Consumer Number</th>
                                <td class="fw-bold text-dark" id="modalNumber">-</td>
                            </tr>
                            <tr>
                                <th class="bg-light text-secondary small">Payment Method</th>
                                <td class="text-dark" id="modalPaymentMethod">-</td>
                            </tr>
                            <tr>
                                <th class="bg-light text-secondary small">Description / Narration</th>
                                <td class="text-muted small" id="modalDescription">-</td>
                            </tr>
                            <tr>
                                <th class="bg-light text-secondary small">Customer Account Name</th>
                                <td class="text-dark" id="modalCustomerName">-</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Security & BBPS Assurance Footer -->
                <div class="p-3 rounded-3 bg-light border d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-shield-check text-success fs-4"></i>
                        <span class="small text-muted">
                            BBPS Bharat BillPay Certified &bull; 256-Bit SSL Secured
                        </span>
                    </div>
                    <span class="text-muted font-monospace" style="font-size: 0.7rem;">
                        AUTH-TOKEN: <span id="modalAuthToken">SEC-<?= date('Ymd') ?>-OK</span>
                    </span>
                </div>

                <!-- Disclaimer for Print -->
                <div class="mt-3 text-center text-muted" style="font-size: 0.7rem;">
                    * This is a computer-generated tax invoice and proof of digital settlement. No physical signature is required.
                </div>
            </div>
            <div class="modal-footer bg-light border-top py-3 px-4 d-flex justify-content-between">
                <button type="button" class="btn btn-outline-secondary px-3" data-bs-dismiss="modal">
                    Close
                </button>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-primary px-4 fw-bold" onclick="printReceipt()">
                        <i class="bi bi-printer-fill me-1"></i> Download Receipt
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
/**
 * Initialize Transaction Detail Modal population on View button click
 */
document.addEventListener('DOMContentLoaded', function() {
    const modalEl = document.getElementById('transactionDetailModal');
    if (!modalEl) return;
    const bsModal = new bootstrap.Modal(modalEl);

    document.querySelectorAll('.view-tx-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            try {
                const data = JSON.parse(this.getAttribute('data-tx'));

                document.getElementById('modalTxId').textContent = data.transaction_id || '-';
                document.getElementById('modalServiceType').textContent = data.type_label || data.type;
                document.getElementById('modalProvider').textContent = data.provider || '-';
                document.getElementById('modalNumber').textContent = data.number || '-';
                document.getElementById('modalAmount').textContent = data.formatted_amount || '₹0.00';
                document.getElementById('modalPaymentMethod').textContent = data.payment_method || 'Wallet';
                document.getElementById('modalDescription').textContent = data.description || 'N/A';
                document.getElementById('modalReceiptDate').textContent = data.formatted_date || '-';
                document.getElementById('modalCustomerName').textContent = data.user_name || 'PayBen User';

                // Status Badge
                const statusBadge = document.getElementById('modalStatusBadge');
                statusBadge.className = 'badge mb-2 fs-6 ' + (data.badge_class || 'badge-secondary');
                statusBadge.innerHTML = `<i class="bi ${data.status_icon}"></i> ${data.status}`;

                // Status subtext
                const subtext = document.getElementById('modalPaymentStatusSubtext');
                if (data.status === 'SUCCESS') {
                    subtext.className = 'text-success small fw-semibold';
                    subtext.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> Payment Settled Successfully';
                } else if (data.status === 'PENDING') {
                    subtext.className = 'text-warning small fw-semibold';
                    subtext.innerHTML = '<i class="bi bi-hourglass-split me-1"></i> Transaction in Review / Processing';
                } else {
                    subtext.className = 'text-danger small fw-semibold';
                    subtext.innerHTML = '<i class="bi bi-x-circle-fill me-1"></i> Transaction Failed / Refunded';
                }

                // Random auth token display
                document.getElementById('modalAuthToken').textContent = 'AUTH-' + data.transaction_id + '-OK';

                bsModal.show();
            } catch (err) {
                console.error('Failed to parse transaction JSON:', err);
            }
        });
    });
});

/**
 * Trigger browser print dialog for receipt
 */
function printReceipt() {
    window.print();
}
</script>

<?php
require_once dirname(__DIR__) . '/includes/dashboard_layout_bottom.php';
?>
