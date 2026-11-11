<?php
// wallet page

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';

// Enforce authenticated user session
requireLogin();

$currentUser = getCurrentUser();
$userId = (int)$currentUser['id'];
$pdo = getDBConnection();

// Fetch or initialize user's wallet
$walletStmt = $pdo->prepare("SELECT * FROM wallets WHERE user_id = ? LIMIT 1");
$walletStmt->execute([$userId]);
$wallet = $walletStmt->fetch();

if (!$wallet) {
    try {
        $initWallet = $pdo->prepare("INSERT INTO wallets (user_id, balance, created_at) VALUES (?, 0.00, NOW())");
        $initWallet->execute([$userId]);
        $walletId = (int)$pdo->lastInsertId();
        $currentBalance = 0.00;
    } catch (PDOException $e) {
        $walletId = 0;
        $currentBalance = 0.00;
    }
} else {
    $walletId = (int)$wallet['id'];
    $currentBalance = (float)$wallet['balance'];
}

// -----------------------------------------------------------------------------
// POST Handler: Wallet Top-Up (Simulated)
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'topup_wallet') {
    // 1. CSRF Token Validation
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!validateCSRFToken($csrfToken)) {
        setFlashMessage('danger', 'Security verification failed (invalid CSRF token). Please try again.');
        header('Location: ' . APP_URL . '/pages/wallet.php');
        exit;
    }

    // 2. Validate Amount (Min: ₹100, Max: ₹50,000)
    $rawAmount = trim($_POST['amount'] ?? '');
    if (!is_numeric($rawAmount)) {
        setFlashMessage('danger', 'Please enter a valid numeric amount to add.');
        header('Location: ' . APP_URL . '/pages/wallet.php');
        exit;
    }

    $amount = round((float)$rawAmount, 2);
    if ($amount < 100.00 || $amount > 50000.00) {
        setFlashMessage('warning', 'Top-up amount must be between ₹100.00 and ₹50,000.00.');
        header('Location: ' . APP_URL . '/pages/wallet.php');
        exit;
    }

    // 3. Atomic Database Transaction
    try {
        $pdo->beginTransaction();

        // Lock wallet record for update to prevent race conditions
        $lockStmt = $pdo->prepare("SELECT id, balance FROM wallets WHERE user_id = ? FOR UPDATE");
        $lockStmt->execute([$userId]);
        $lockedWallet = $lockStmt->fetch();

        if (!$lockedWallet) {
            $createStmt = $pdo->prepare("INSERT INTO wallets (user_id, balance, created_at) VALUES (?, 0.00, NOW())");
            $createStmt->execute([$userId]);
            $wId = (int)$pdo->lastInsertId();
            $oldBalance = 0.00;
        } else {
            $wId = (int)$lockedWallet['id'];
            $oldBalance = (float)$lockedWallet['balance'];
        }

        $newBalance = round($oldBalance + $amount, 2);

        // Update Wallet Balance
        $updateWallet = $pdo->prepare("UPDATE wallets SET balance = ?, updated_at = NOW() WHERE id = ?");
        $updateWallet->execute([$newBalance, $wId]);

        // Generate Identifiers
        $refId = 'WTX' . date('Ymd') . strtoupper(substr(uniqid(), -6));
        $transactionId = generateTransactionID('WT');

        // Record double-entry credit in wallet_transactions
        $insertWalletTx = $pdo->prepare(
            "INSERT INTO wallet_transactions (user_id, wallet_id, type, amount, description, reference_id, balance_after, created_at)
             VALUES (?, ?, 'credit', ?, ?, ?, ?, NOW())"
        );
        $insertWalletTx->execute([
            $userId,
            $wId,
            $amount,
            'Wallet top-up (SIMULATED TOP-UP)',
            $refId,
            $newBalance
        ]);

        // Record in platform transactions log
        $insertTx = $pdo->prepare(
            "INSERT INTO transactions (user_id, transaction_id, type, mobile_or_consumer_number, operator_or_provider, amount, status, payment_method, description, created_at)
             VALUES (?, ?, 'wallet_topup', ?, ?, ?, 'SUCCESS', ?, ?, NOW())"
        );
        $userMobile = $currentUser['mobile'] ?? '9876543210';
        $paymentMethod = sanitize($_POST['payment_method'] ?? 'Instant UPI (Simulated)');
        $insertTx->execute([
            $userId,
            $transactionId,
            $userMobile,
            'PayBen Wallet',
            $amount,
            $paymentMethod,
            'SIMULATED TOP-UP - Instant Wallet Credit'
        ]);

        $pdo->commit();

        setFlashMessage('success', 'SIMULATED TOP-UP SUCCESSFUL! Added ' . formatCurrency($amount) . ' to your wallet. Your new balance is ' . formatCurrency($newBalance) . '.');
        header('Location: ' . APP_URL . '/pages/wallet.php');
        exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Wallet top-up error for user {$userId}: " . $e->getMessage());
        setFlashMessage('danger', 'Top-up failed due to an unexpected system error. Please try again.');
        header('Location: ' . APP_URL . '/pages/wallet.php');
        exit;
    }
}

// -----------------------------------------------------------------------------
// Lifetime Summary Statistics
// -----------------------------------------------------------------------------
$statsStmt = $pdo->prepare(
    "SELECT 
        COALESCE(SUM(CASE WHEN type = 'credit' THEN amount ELSE 0 END), 0) AS total_credited,
        COALESCE(SUM(CASE WHEN type = 'debit' THEN amount ELSE 0 END), 0) AS total_debited,
        COUNT(*) AS total_tx_count
     FROM wallet_transactions 
     WHERE user_id = ?"
);
$statsStmt->execute([$userId]);
$walletStats = $statsStmt->fetch() ?: ['total_credited' => 0, 'total_debited' => 0, 'total_tx_count' => 0];

// -----------------------------------------------------------------------------
// Pagination & Wallet Transaction History
// -----------------------------------------------------------------------------
$perPage = 20;
$totalRecords = (int)$walletStats['total_tx_count'];
$totalPages = max(1, (int)ceil($totalRecords / $perPage));
$page = isset($_GET['page']) ? max(1, min((int)$_GET['page'], $totalPages)) : 1;
$offset = ($page - 1) * $perPage;

$historyStmt = $pdo->prepare(
    "SELECT * FROM wallet_transactions 
     WHERE user_id = ? 
     ORDER BY created_at DESC, id DESC 
     LIMIT ? OFFSET ?"
);
$historyStmt->bindValue(1, $userId, PDO::PARAM_INT);
$historyStmt->bindValue(2, $perPage, PDO::PARAM_INT);
$historyStmt->bindValue(3, $offset, PDO::PARAM_INT);
$historyStmt->execute();
$transactions = $historyStmt->fetchAll();

// Refresh current user for navbar balance synchronization
$currentUser = getCurrentUser();

$pageTitle = 'My Digital Wallet';
$currentPage = 'wallet';

require_once dirname(__DIR__) . '/includes/dashboard_layout_top.php';
?>

<div class="container-fluid px-0">
    <!-- Page Header Title & Breadcrumb -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-primary bg-opacity-10 text-primary px-2 py-1">
                    <i class="bi bi-wallet2 me-1"></i> Digital Passbook
                </span>
                <span class="badge bg-info bg-opacity-10 text-info px-2 py-1">
                    <i class="bi bi-shield-check me-1"></i> 100% Secure
                </span>
            </div>
            <h1 class="h3 fw-bold text-dark mb-0">Wallet Management</h1>
            <p class="text-muted small mb-0">View real-time balance, load simulated funds, and inspect transaction audits.</p>
        </div>
        <div>
            <button type="button" class="btn btn-primary shadow-sm px-4 py-2" data-bs-toggle="modal" data-bs-target="#addMoneyModal">
                <i class="bi bi-plus-circle-fill me-2 fs-6"></i> Add Money
            </button>
        </div>
    </div>

    <!-- Top Grid: Balance Card & Lifetime Metrics -->
    <div class="row g-4 mb-4">
        <div class="col-lg-6 col-xl-5">
            <div class="wallet-card h-100 d-flex flex-column justify-content-between">
                <div>
                    <div class="wallet-card-header">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-white bg-opacity-20 text-white rounded-pill px-3 py-1 fw-semibold small">
                                <i class="bi bi-google text-warning me-1"></i> PayBen UPI Primary
                            </span>
                            <span class="badge bg-light text-dark rounded-pill px-2 py-1 small font-monospace">
                                <?= ($currentUser['mobile'] ?? '9876543210') ?>@payben
                            </span>
                        </div>
                        <div class="text-white-50 small">
                            <i class="bi bi-shield-lock-fill text-success me-1"></i> 256-Bit SSL
                        </div>
                    </div>

                    <div class="wallet-balance-label">Available Balance</div>
                    <div class="wallet-balance-amount text-white mb-2">
                        <?= formatCurrency($currentUser['wallet_balance'] ?? $currentBalance) ?>
                    </div>
                    <p class="text-white-50 small mb-4">
                        Ready for instant recharges, utility bill settlements, and fast 1-click checkouts.
                    </p>
                </div>

                <div class="wallet-card-footer flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-3">
                        <button type="button" class="btn btn-light btn-sm rounded-pill fw-bold px-3 py-2 text-primary" data-bs-toggle="modal" data-bs-target="#addMoneyModal">
                            <i class="bi bi-plus-lg me-1"></i> Add Money
                        </button>
                        <a href="<?= APP_URL ?>/pages/transactions.php" class="btn btn-outline-light btn-sm rounded-pill px-3 py-2">
                            <i class="bi bi-clock-history me-1"></i> History
                        </a>
                    </div>
                    <span class="badge bg-warning bg-opacity-25 text-warning border border-warning border-opacity-50 px-2 py-1 small">
                        <i class="bi bi-info-circle me-1"></i> SIMULATED MODE
                    </span>
                </div>
            </div>
        </div>
        <div class="col-lg-6 col-xl-7">
            <div class="row g-3 h-100">
                <!-- Metric: Total Credited -->
                <div class="col-sm-6">
                    <div class="card border-0 shadow-sm rounded-3 h-100 p-3 bg-white">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-muted small fw-semibold text-uppercase">Total Deposited</span>
                            <div class="rounded-circle bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                                <i class="bi bi-arrow-down-left fs-5"></i>
                            </div>
                        </div>
                        <div class="h4 fw-bold text-success mb-1">
                            <?= formatCurrency($walletStats['total_credited']) ?>
                        </div>
                        <span class="text-muted small">All credits & top-ups to date</span>
                    </div>
                </div>

                <!-- Metric: Total Spent -->
                <div class="col-sm-6">
                    <div class="card border-0 shadow-sm rounded-3 h-100 p-3 bg-white">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-muted small fw-semibold text-uppercase">Total Spent</span>
                            <div class="rounded-circle bg-danger bg-opacity-10 text-danger d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                                <i class="bi bi-arrow-up-right fs-5"></i>
                            </div>
                        </div>
                        <div class="h4 fw-bold text-danger mb-1">
                            <?= formatCurrency($walletStats['total_debited']) ?>
                        </div>
                        <span class="text-muted small">Recharges & utility bill payments</span>
                    </div>
                </div>

                <!-- Quick Inline Add Money Card -->
                <div class="col-12">
                    <div class="card border-0 shadow-sm rounded-3 p-3 bg-white">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                            <div>
                                <h6 class="fw-bold mb-1 text-dark">
                                    <i class="bi bi-lightning-charge-fill text-warning me-1"></i> Instant Simulated Top-Up
                                </h6>
                                <p class="text-muted small mb-0">Select a popular amount to test wallet funding immediately.</p>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <button type="button" class="btn btn-outline-primary btn-sm rounded-pill fw-semibold" data-quick-amount="500" data-target-input="topup_amount" data-bs-toggle="modal" data-bs-target="#addMoneyModal">
                                    + ₹500
                                </button>
                                <button type="button" class="btn btn-outline-primary btn-sm rounded-pill fw-semibold" data-quick-amount="1000" data-target-input="topup_amount" data-bs-toggle="modal" data-bs-target="#addMoneyModal">
                                    + ₹1,000
                                </button>
                                <button type="button" class="btn btn-outline-primary btn-sm rounded-pill fw-semibold" data-quick-amount="2000" data-target-input="topup_amount" data-bs-toggle="modal" data-bs-target="#addMoneyModal">
                                    + ₹2,000
                                </button>
                                <button type="button" class="btn btn-outline-primary btn-sm rounded-pill fw-semibold" data-quick-amount="5000" data-target-input="topup_amount" data-bs-toggle="modal" data-bs-target="#addMoneyModal">
                                    + ₹5,000
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden bg-white">
        <div class="card-header bg-white border-bottom py-3 px-3 px-md-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
            <div>
                <h5 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-receipt-cutoff text-primary me-2"></i> Wallet Transaction History
                </h5>
                <span class="text-muted small">Chronological ledger of all deposits, debits, and balance changes.</span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-light text-secondary border px-3 py-2">
                    Total Records: <strong><?= number_format($totalRecords) ?></strong>
                </span>
            </div>
        </div>

        <div class="card-body p-0">
            <?php if (empty($transactions)): ?>
                <!-- Friendly Empty State -->
                <div class="text-center py-5 px-3">
                    <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                        <i class="bi bi-wallet2 fs-1 text-muted"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">No Wallet Transactions Yet</h5>
                    <p class="text-muted small mx-auto mb-3" style="max-width: 420px;">
                        Your passbook is currently empty. Use the simulated top-up tool to add funds and test mobile recharges or utility payments!
                    </p>
                    <button type="button" class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addMoneyModal">
                        <i class="bi bi-plus-circle me-1"></i> Add Your First ₹500
                    </button>
                </div>
            <?php else: ?>
                <!-- Responsive Data Table -->
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col" class="ps-3 ps-md-4">Date &amp; Time</th>
                                <th scope="col">Description</th>
                                <th scope="col">Reference ID</th>
                                <th scope="col">Type</th>
                                <th scope="col" class="text-end">Amount</th>
                                <th scope="col" class="text-end pe-3 pe-md-4">Balance After</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $tx): ?>
                                <?php
                                $isCredit = strtolower($tx['type']) === 'credit';
                                $amountClass = $isCredit ? 'text-success' : 'text-danger';
                                $sign = $isCredit ? '+' : '-';
                                $badgeClass = $isCredit ? 'badge-status-success' : 'badge-status-failed';
                                $iconClass = $isCredit ? 'bi-arrow-down-left' : 'bi-arrow-up-right';
                                $formattedDate = date('d M Y, h:i A', strtotime($tx['created_at']));
                                ?>
                                <tr>
                                    <!-- Date & Time -->
                                    <td class="ps-3 ps-md-4 text-nowrap">
                                        <div class="fw-semibold text-dark"><?= date('d M Y', strtotime($tx['created_at'])) ?></div>
                                        <div class="text-muted small"><?= date('h:i A', strtotime($tx['created_at'])) ?></div>
                                    </td>

                                    <!-- Description -->
                                    <td>
                                        <div class="fw-semibold text-dark text-truncate" style="max-width: 320px;" title="<?= sanitize($tx['description']) ?>">
                                            <?= sanitize($tx['description']) ?>
                                        </div>
                                    </td>

                                    <!-- Reference ID -->
                                    <td>
                                        <code class="text-secondary small bg-light px-2 py-1 rounded">
                                            <?= sanitize($tx['reference_id'] ?? 'N/A') ?>
                                        </code>
                                    </td>

                                    <!-- Type (Credit/Debit) -->
                                    <td>
                                        <span class="badge <?= $badgeClass ?>">
                                            <i class="bi <?= $iconClass ?>"></i>
                                            <?= ucfirst($tx['type']) ?>
                                        </span>
                                    </td>

                                    <!-- Amount -->
                                    <td class="text-end text-nowrap">
                                        <span class="fw-bold fs-6 <?= $amountClass ?>">
                                            <?= $sign ?> <?= formatCurrency($tx['amount']) ?>
                                        </span>
                                    </td>

                                    <!-- Balance After -->
                                    <td class="text-end pe-3 pe-md-4 text-nowrap">
                                        <span class="fw-bold text-dark">
                                            <?= formatCurrency($tx['balance_after']) ?>
                                        </span>
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
                            Showing <strong><?= ($offset + 1) ?></strong> to <strong><?= min($offset + $perPage, $totalRecords) ?></strong> of <strong><?= $totalRecords ?></strong> transactions
                        </div>
                        <nav aria-label="Wallet Transactions Pagination">
                            <ul class="pagination pagination-sm mb-0">
                                <!-- Previous Button -->
                                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?page=<?= ($page - 1) ?>" aria-label="Previous">
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </li>

                                <!-- Page Numbers -->
                                <?php
                                $startPage = max(1, $page - 2);
                                $endPage = min($totalPages, $page + 2);
                                for ($p = $startPage; $p <= $endPage; $p++):
                                ?>
                                    <li class="page-item <?= ($p === $page) ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $p ?>"><?= $p ?></a>
                                    </li>
                                <?php endfor; ?>

                                <!-- Next Button -->
                                <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?page=<?= ($page + 1) ?>" aria-label="Next">
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
     Bootstrap Modal: Add Money to Wallet (Simulated)
     =========================================================================== -->
<div class="modal fade" id="addMoneyModal" tabindex="-1" aria-labelledby="addMoneyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-light border-bottom py-3 px-4">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                        <i class="bi bi-wallet2 fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0" id="addMoneyModalLabel">Add Money to Wallet</h5>
                        <span class="badge bg-warning bg-opacity-25 text-warning border border-warning border-opacity-50 small" style="font-size: 0.65rem;">
                            SIMULATED TOP-UP
                        </span>
                    </div>
                </div>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= APP_URL ?>/pages/wallet.php" method="POST" id="walletTopupForm">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <input type="hidden" name="action" value="topup_wallet">

                <div class="modal-body p-4">
                    <!-- Current Balance Info Banner -->
                    <div class="p-3 rounded-3 bg-light border mb-4 d-flex justify-content-between align-items-center">
                        <span class="text-muted small">Current Wallet Balance:</span>
                        <strong class="text-primary fs-6"><?= formatCurrency($currentUser['wallet_balance'] ?? $currentBalance) ?></strong>
                    </div>

                    <!-- Amount Input -->
                    <div class="mb-3">
                        <label for="topup_amount" class="form-label fw-semibold">Enter Amount to Add</label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-white border-end-0 text-primary fw-bold">₹</span>
                            <input type="number" 
                                   class="form-control border-start-0 ps-0 fw-bold fs-4" 
                                   id="topup_amount" 
                                   name="amount" 
                                   min="100" 
                                   max="50000" 
                                   step="1" 
                                   placeholder="500" 
                                   required>
                        </div>
                        <div class="form-text d-flex justify-content-between mt-1">
                            <span>Minimum: ₹100.00</span>
                            <span>Maximum: ₹50,000.00</span>
                        </div>
                    </div>

                    <!-- Quick Amount Chips -->
                    <div class="mb-4">
                        <label class="form-label text-muted small fw-semibold mb-2">Popular Amounts</label>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3 py-1 fw-semibold" data-quick-amount="200" data-target-input="topup_amount">
                                ₹200
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3 py-1 fw-semibold" data-quick-amount="500" data-target-input="topup_amount">
                                ₹500
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3 py-1 fw-semibold active" data-quick-amount="1000" data-target-input="topup_amount">
                                ₹1,000
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3 py-1 fw-semibold" data-quick-amount="2000" data-target-input="topup_amount">
                                ₹2,000
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3 py-1 fw-semibold" data-quick-amount="5000" data-target-input="topup_amount">
                                ₹5,000
                            </button>
                        </div>
                    </div>

                    <!-- Payment Mode Selection (Simulated) -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Payment Source (Simulated)</label>
                        <div class="d-flex flex-column gap-2">
                            <label class="border rounded-3 p-3 d-flex align-items-center gap-3 cursor-pointer hover-bg-light">
                                <input type="radio" name="payment_method" value="Instant UPI (Simulated)" class="form-check-input mt-0" checked>
                                <div class="rounded-circle bg-success bg-opacity-10 text-success p-2">
                                    <i class="bi bi-qr-code-scan"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold text-dark small">Instant UPI (Simulated)</div>
                                    <div class="text-muted" style="font-size: 0.75rem;">PhonePe, Paytm, BHIM, PayBen</div>
                                </div>
                                <span class="badge bg-success bg-opacity-10 text-success small">Fastest</span>
                            </label>

                            <label class="border rounded-3 p-3 d-flex align-items-center gap-3 cursor-pointer hover-bg-light">
                                <input type="radio" name="payment_method" value="Net Banking (Simulated)" class="form-check-input mt-0">
                                <div class="rounded-circle bg-primary bg-opacity-10 text-primary p-2">
                                    <i class="bi bi-bank"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold text-dark small">Net Banking (Simulated)</div>
                                    <div class="text-muted" style="font-size: 0.75rem;">HDFC, SBI, ICICI, Axis &amp; 50+ Banks</div>
                                </div>
                            </label>

                            <label class="border rounded-3 p-3 d-flex align-items-center gap-3 cursor-pointer hover-bg-light">
                                <input type="radio" name="payment_method" value="Debit / Credit Card (Simulated)" class="form-check-input mt-0">
                                <div class="rounded-circle bg-info bg-opacity-10 text-info p-2">
                                    <i class="bi bi-credit-card-2-front"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold text-dark small">Debit / Credit Card (Simulated)</div>
                                    <div class="text-muted" style="font-size: 0.75rem;">Visa, MasterCard, RuPay</div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Sandbox Notice -->
                    <div class="alert alert-info border-0 rounded-3 small mb-0 d-flex gap-2 align-items-center">
                        <i class="bi bi-info-circle-fill fs-5 text-info"></i>
                        <div>
                            <strong>Simulation Notice:</strong> This is a sandbox demonstration. Your wallet will be credited instantly without actual banking deduction.
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top py-3 px-4">
                    <button type="button" class="btn btn-outline-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 fw-bold">
                        <i class="bi bi-shield-check me-1"></i> Proceed to Add Money
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Prefill initial quick amount on modal trigger
document.addEventListener('DOMContentLoaded', function() {
    const input = document.getElementById('topup_amount');
    if (input && !input.value) {
        input.value = '1000';
    }
});
</script>

<?php
require_once dirname(__DIR__) . '/includes/dashboard_layout_bottom.php';
?>
