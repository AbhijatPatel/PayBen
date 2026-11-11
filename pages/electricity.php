<?php
// electricity page

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';

// Enforce authenticated user access
requireLogin();

// Retrieve authenticated user profile with wallet balance
$currentUser = getCurrentUser();
if ($currentUser && !isset($currentUser['name']) && isset($currentUser['full_name'])) {
    $currentUser['name'] = $currentUser['full_name'];
}

// Page configuration constants
$serviceType   = 'electricity';
$txPrefix      = 'EB';
$currentPage   = 'electricity';
$pageTitle     = 'Electricity Bill Payment';

// State management variables
$providerId     = isset($_POST['provider_id']) ? (int)$_POST['provider_id'] : 0;
$consumerNumber = isset($_POST['consumer_number']) ? sanitize($_POST['consumer_number']) : '';
$errorMessage   = null;
$infoMessage    = null;
$fetchedBill    = null;
$paymentSuccess = false;
$receiptData    = null;

// ==============================================================================
// 1. POST ACTION HANDLERS: Bill Fetching & Payment Settlement
// ==============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';

    // Validate CSRF Protection
    if (!validateCSRFToken($csrfToken)) {
        $errorMessage = 'Security validation failed (Invalid CSRF token). Please refresh and try again.';
    } elseif ($action === 'fetch_bill') {
        // ----------------------------------------------------------------------
        // Action: Fetch Pending Electricity Bill
        // ----------------------------------------------------------------------
        if ($providerId <= 0) {
            $errorMessage = 'Please select a valid electricity provider from the list.';
        } elseif (empty($consumerNumber)) {
            $errorMessage = 'Please enter your Consumer Account / Connection Number.';
        } else {
            try {
                $pdo = getDBConnection();

                // 1. Verify active electricity provider
                $pStmt = $pdo->prepare("SELECT * FROM utility_providers WHERE id = ? AND type = ? AND status = 'active' LIMIT 1");
                $pStmt->execute([$providerId, $serviceType]);
                $selectedProvider = $pStmt->fetch();

                if (!$selectedProvider) {
                    $errorMessage = 'The selected electricity provider is invalid or currently inactive.';
                } else {
                    // 2. Fetch pending unpaid bill from sample_bills
                    $bStmt = $pdo->prepare("SELECT b.*, p.name AS provider_name, p.state AS provider_state, p.code AS provider_code 
                                            FROM sample_bills b 
                                            JOIN utility_providers p ON b.provider_id = p.id 
                                            WHERE b.provider_id = ? AND b.consumer_number = ? AND p.type = ? AND b.status = 'unpaid' 
                                            LIMIT 1");
                    $bStmt->execute([$providerId, $consumerNumber, $serviceType]);
                    $bill = $bStmt->fetch();

                    if ($bill) {
                        $fetchedBill = $bill;
                    } else {
                        // Check if bill exists but has already been paid
                        $chkPaidStmt = $pdo->prepare("SELECT b.*, p.name AS provider_name 
                                                      FROM sample_bills b 
                                                      JOIN utility_providers p ON b.provider_id = p.id 
                                                      WHERE b.provider_id = ? AND b.consumer_number = ? AND p.type = ? AND b.status = 'paid' 
                                                      LIMIT 1");
                        $chkPaidStmt->execute([$providerId, $consumerNumber, $serviceType]);
                        $paidBill = $chkPaidStmt->fetch();

                        if ($paidBill) {
                            $infoMessage = "Great news! The electricity bill for Consumer Number <strong>" . sanitize($consumerNumber) . "</strong> with <strong>" . sanitize($paidBill['provider_name']) . "</strong> has already been paid and settled. No pending dues found.";
                        } else {
                            $errorMessage = "No pending bill found for this consumer number with the selected electricity provider. Please verify your consumer number or try one of the demo accounts.";
                        }
                    }
                }
            } catch (PDOException $e) {
                error_log("Electricity bill fetch error: " . $e->getMessage());
                $errorMessage = "A database error occurred while fetching your bill. Please try again shortly.";
            }
        }
    } elseif ($action === 'pay_bill') {
        // ----------------------------------------------------------------------
        // Action: Process Electricity Bill Payment via PayBen Wallet
        // ----------------------------------------------------------------------
        $billId = isset($_POST['bill_id']) ? (int)$_POST['bill_id'] : 0;

        if ($billId <= 0 || $providerId <= 0 || empty($consumerNumber)) {
            $errorMessage = 'Invalid payment parameters. Please re-fetch your bill to continue.';
        } else {
            $pdo = getDBConnection();
            try {
                // Begin atomic database transaction
                $pdo->beginTransaction();

                // 1. Lock the bill row for update to prevent concurrent duplicate payments
                $bStmt = $pdo->prepare("SELECT b.*, p.name AS provider_name, p.state AS provider_state, p.code AS provider_code 
                                        FROM sample_bills b 
                                        JOIN utility_providers p ON b.provider_id = p.id 
                                        WHERE b.id = ? AND b.provider_id = ? AND b.consumer_number = ? AND p.type = ? AND b.status = 'unpaid' 
                                        FOR UPDATE");
                $bStmt->execute([$billId, $providerId, $consumerNumber, $serviceType]);
                $bill = $bStmt->fetch();

                if (!$bill) {
                    throw new Exception('The bill has already been settled, expired, or could not be found.');
                }

                $billAmount = (float)$bill['bill_amount'];

                // 2. Lock and retrieve user's wallet record
                $wStmt = $pdo->prepare("SELECT id, balance FROM wallets WHERE user_id = ? FOR UPDATE");
                $wStmt->execute([$currentUser['id']]);
                $wallet = $wStmt->fetch();

                if (!$wallet) {
                    throw new Exception('No digital wallet account found for your profile. Please contact customer support.');
                }

                $currentBalance = (float)$wallet['balance'];

                // 3. Validate wallet balance >= bill_amount
                if ($currentBalance < $billAmount) {
                    $deficit = $billAmount - $currentBalance;
                    throw new Exception('Insufficient wallet balance. You have ' . formatCurrency($currentBalance) . ', but ' . formatCurrency($billAmount) . ' is required. Please add at least ' . formatCurrency($deficit) . ' to your wallet to settle this bill.');
                }

                // 4. Deduct bill amount from user's wallet
                $newBalance = $currentBalance - $billAmount;
                $updWallet = $pdo->prepare("UPDATE wallets SET balance = ? WHERE id = ?");
                $updWallet->execute([$newBalance, $wallet['id']]);

                // 5. Generate distinct transaction reference ID
                $transactionId = generateTransactionID($txPrefix);

                // 6. Record debit movement in wallet_transactions ledger
                $wtxDescription = "Electricity Bill Payment - " . $bill['provider_name'] . " (Consumer: " . $consumerNumber . ")";
                $wtxStmt = $pdo->prepare("INSERT INTO wallet_transactions (user_id, wallet_id, type, amount, description, reference_id, balance_after, created_at) 
                                          VALUES (?, ?, 'debit', ?, ?, ?, ?, NOW())");
                $wtxStmt->execute([
                    $currentUser['id'],
                    $wallet['id'],
                    $billAmount,
                    $wtxDescription,
                    $transactionId,
                    $newBalance
                ]);

                // 7. Record entry in unified transactions table
                $txStmt = $pdo->prepare("INSERT INTO transactions (user_id, transaction_id, type, mobile_or_consumer_number, operator_or_provider, amount, status, payment_method, description, created_at) 
                                        VALUES (?, ?, ?, ?, ?, ?, 'SUCCESS', 'Wallet', ?, NOW())");
                $txStmt->execute([
                    $currentUser['id'],
                    $transactionId,
                    $serviceType,
                    $consumerNumber,
                    $bill['provider_name'],
                    $billAmount,
                    $wtxDescription
                ]);

                // 8. Mark sample_bills record status as 'paid'
                $updBill = $pdo->prepare("UPDATE sample_bills SET status = 'paid' WHERE id = ?");
                $updBill->execute([$billId]);

                // Commit entire atomic transaction
                $pdo->commit();

                // Payment successfully settled
                $paymentSuccess = true;
                $receiptData = [
                    'transaction_id'  => $transactionId,
                    'bbps_ref_no'     => 'BBPS' . date('Ymd') . strtoupper(substr(md5($transactionId), 0, 8)),
                    'provider_name'   => $bill['provider_name'],
                    'provider_state'  => $bill['provider_state'],
                    'consumer_number' => $consumerNumber,
                    'consumer_name'   => $bill['consumer_name'],
                    'bill_amount'     => $billAmount,
                    'due_date'        => $bill['due_date'],
                    'bill_date'       => $bill['bill_date'],
                    'paid_at'         => date('d M Y, h:i A'),
                    'payment_method'  => 'PayBen Digital Wallet',
                    'balance_before'  => $currentBalance,
                    'balance_after'   => $newBalance,
                    'service_type'    => 'Electricity Bill'
                ];

                // Refresh session wallet balance
                $currentUser['wallet_balance'] = $newBalance;

            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log("Electricity bill payment error: " . $e->getMessage());
                $errorMessage = $e->getMessage();

                // Re-fetch bill details to preserve user state on error
                if ($billId > 0) {
                    $reStmt = $pdo->prepare("SELECT b.*, p.name AS provider_name, p.state AS provider_state, p.code AS provider_code 
                                             FROM sample_bills b 
                                             JOIN utility_providers p ON b.provider_id = p.id 
                                             WHERE b.id = ? AND b.status = 'unpaid' LIMIT 1");
                    $reStmt->execute([$billId]);
                    $fetchedBill = $reStmt->fetch() ?: null;
                }
            }
        }
    }
}

// ==============================================================================
// 2. LOAD ACTIVE ELECTRICITY PROVIDERS & DEMO BILLS
// ==============================================================================
$providers = [];
$demoBills = [];
try {
    $pdo = getDBConnection();
    // Fetch active providers sorted by state
    $pListStmt = $pdo->prepare("SELECT * FROM utility_providers WHERE type = ? AND status = 'active' ORDER BY state ASC, name ASC");
    $pListStmt->execute([$serviceType]);
    $providers = $pListStmt->fetchAll();

    // Fetch demo bills for quick one-click testing
    $demoStmt = $pdo->prepare("SELECT b.consumer_number, b.consumer_name, b.bill_amount, b.status, p.id AS provider_id, p.name AS provider_name 
                               FROM sample_bills b 
                               JOIN utility_providers p ON b.provider_id = p.id 
                               WHERE p.type = ? 
                               ORDER BY b.id ASC 
                               LIMIT 4");
    $demoStmt->execute([$serviceType]);
    $demoBills = $demoStmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed to load electricity providers: " . $e->getMessage());
}

// Extra styles for receipt printing and fintech bill styling
$extraHead = '
<style>
    .service-banner {
        background: linear-gradient(135deg, #1e1b4b 0%, #312e81 40%, #4338ca 100%);
        border-radius: var(--radius-lg);
        color: #ffffff;
        padding: 1.75rem 2rem;
        position: relative;
        overflow: hidden;
        box-shadow: var(--shadow-md);
    }
    .service-banner::after {
        content: "";
        position: absolute;
        top: -40px;
        right: -40px;
        width: 180px;
        height: 180px;
        background: radial-gradient(circle, rgba(251, 191, 36, 0.25) 0%, rgba(251, 191, 36, 0) 70%);
        border-radius: 50%;
        pointer-events: none;
    }
    .bill-detail-card {
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-sm);
        transition: transform var(--transition-base), box-shadow var(--transition-base);
    }
    .bill-amount-display {
        background: linear-gradient(135deg, rgba(67, 97, 238, 0.05) 0%, rgba(114, 9, 183, 0.08) 100%);
        border: 1px dashed rgba(67, 97, 238, 0.3);
        border-radius: var(--radius-md);
        padding: 1.25rem;
    }
    .simulated-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.3rem 0.75rem;
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        border-radius: 50px;
        background: rgba(245, 158, 11, 0.12);
        color: #b45309;
        border: 1px solid rgba(245, 158, 11, 0.3);
    }
    .receipt-container {
        max-width: 680px;
        margin: 0 auto;
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-lg);
        overflow: hidden;
    }
    .receipt-header {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        color: #ffffff;
        padding: 2rem 2.5rem;
        text-align: center;
        position: relative;
    }
    .receipt-status-circle {
        width: 72px;
        height: 72px;
        border-radius: 50%;
        background: #06d6a0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #ffffff;
        font-size: 2.2rem;
        box-shadow: 0 0 25px rgba(6, 214, 160, 0.45);
        margin-bottom: 1rem;
    }
    .receipt-watermark {
        position: absolute;
        bottom: 10px;
        right: 20px;
        font-size: 4rem;
        font-weight: 900;
        color: rgba(255, 255, 255, 0.04);
        pointer-events: none;
        user-select: none;
    }
    .receipt-table th {
        font-size: 0.82rem;
        color: var(--text-secondary);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        padding: 0.75rem 1rem;
        border-bottom: 1px solid var(--border-color);
    }
    .receipt-table td {
        padding: 0.75rem 1rem;
        font-size: 0.92rem;
        border-bottom: 1px solid var(--border-light);
    }
    .quick-fill-chip {
        cursor: pointer;
        transition: all 0.2s ease;
        border: 1px dashed #cbd5e1;
        background: #f8fafc;
    }
    .quick-fill-chip:hover {
        background: #eff6ff;
        border-color: #3b82f6;
        color: #1d4ed8;
        transform: translateY(-1px);
    }
    @media print {
        body {
            background: #ffffff !important;
        }
        .sidebar, .app-header, .app-footer, .no-print, .btn, .service-banner {
            display: none !important;
        }
        .main-content {
            margin-left: 0 !important;
            padding: 0 !important;
        }
        .content-body {
            padding: 0 !important;
        }
        .receipt-container {
            border: 1px solid #000000 !important;
            box-shadow: none !important;
            width: 100% !important;
            max-width: 100% !important;
        }
    }
</style>
';

require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="app-wrapper">
    <!-- Left Dashboard Sidebar Navigation -->
    <?php require_once dirname(__DIR__) . '/includes/sidebar.php'; ?>
    <div class="main-content">
        <!-- Top Application Navbar -->
        <?php require_once dirname(__DIR__) . '/includes/navbar.php'; ?>
        <main class="content-body">
            <div class="container-fluid px-0">

                <!-- Service Breadcrumb & Header Banner -->
                <div class="service-banner mb-4">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="badge bg-warning text-dark px-2 py-1 rounded-pill fw-bold" style="font-size: 0.75rem;">
                                    <i class="bi bi-lightning-charge-fill me-1"></i> BBPS UTILITY
                                </span>
                                <span class="simulated-pill">
                                    <i class="bi bi-cpu-fill"></i> Simulated Sandbox
                                </span>
                            </div>
                            <h2 class="fw-bold mb-1 text-white d-flex align-items-center gap-2">
                                <span>Electricity Bill Payment</span>
                            </h2>
                            <p class="text-white-50 mb-0 small">
                                Instant online bill fetch and receipt settlement across Indian power boards with zero convenience fee.
                            </p>
                        </div>

                        <!-- Live Wallet Indicator -->
                        <div class="bg-white bg-opacity-10 border border-white border-opacity-20 rounded-3 p-3 text-start text-md-end text-white">
                            <span class="text-white-50 small d-block mb-1">Available PayBen Wallet</span>
                            <div class="fs-4 fw-bold text-white">
                                <?= formatCurrency($currentUser['wallet_balance'] ?? 0.00) ?>
                            </div>
                            <a href="<?= APP_URL ?>/pages/wallet.php" class="text-white text-decoration-underline small opacity-75 hover-opacity-100">
                                <i class="bi bi-plus-circle me-1"></i> Add Funds
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Alert Feedback Notifications -->
                <?php if (!empty($errorMessage)): ?>
                    <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 rounded-3 mb-4 d-flex align-items-start gap-3" role="alert">
                        <i class="bi bi-exclamation-octagon-fill fs-4 text-danger flex-shrink-0 mt-1"></i>
                        <div>
                            <h6 class="fw-bold mb-1 text-danger">Action Required</h6>
                            <div><?= $errorMessage ?></div>
                        </div>
                        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($infoMessage)): ?>
                    <div class="alert alert-info alert-dismissible fade show shadow-sm border-0 rounded-3 mb-4 d-flex align-items-start gap-3" role="alert">
                        <i class="bi bi-info-circle-fill fs-4 text-info flex-shrink-0 mt-1"></i>
                        <div>
                            <h6 class="fw-bold mb-1 text-info">Bill Status Information</h6>
                            <div><?= $infoMessage ?></div>
                        </div>
                        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- ========================================================== -->
                <!-- SUCCESS RECEIPT VIEW (Rendered after successful payment)   -->
                <!-- ========================================================== -->
                <?php if ($paymentSuccess && !empty($receiptData)): ?>
                    <div class="receipt-container mb-5">
                        <div class="receipt-header">
                            <div class="receipt-status-circle">
                                <i class="bi bi-check-lg"></i>
                            </div>
                            <span class="badge bg-warning text-dark px-3 py-1 rounded-pill fw-bold mb-2">SIMULATED PAYMENT</span>
                            <h3 class="fw-bold mb-1 text-white">Bill Payment Successful!</h3>
                            <p class="text-white-50 small mb-0">
                                Your electricity bill payment has been successfully debited and settled.
                            </p>
                            <div class="receipt-watermark">PAID</div>
                        </div>

                        <div class="p-4 p-md-5">
                            <!-- Amount Highlight -->
                            <div class="text-center py-3 mb-4 rounded-3 bg-light border">
                                <span class="text-muted small text-uppercase fw-bold tracking-wider d-block mb-1">Total Amount Paid</span>
                                <div class="display-6 fw-extrabold text-primary">
                                    <?= formatCurrency($receiptData['bill_amount']) ?>
                                </div>
                                <span class="badge bg-success-subtle text-success border border-success-subtle mt-1 px-3 py-1">
                                    <i class="bi bi-patch-check-fill me-1"></i> Payment Settled (Simulated)
                                </span>
                            </div>

                            <!-- Detailed Audit Ledger Table -->
                            <div class="table-responsive mb-4">
                                <table class="table receipt-table mb-0">
                                    <tbody>
                                        <tr>
                                            <th>Transaction Reference ID</th>
                                            <td class="text-end fw-bold text-dark font-monospace">
                                                <?= sanitize($receiptData['transaction_id']) ?>
                                                <button class="btn btn-sm btn-link p-0 ms-1 text-primary text-decoration-none no-print" 
                                                        onclick="navigator.clipboard.writeText('<?= $receiptData['transaction_id'] ?>'); alert('Copied transaction ID!');" 
                                                        title="Copy Transaction ID">
                                                    <i class="bi bi-copy"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Simulated BBPS Reference</th>
                                            <td class="text-end font-monospace text-secondary">
                                                <?= sanitize($receiptData['bbps_ref_no']) ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Electricity Board / Provider</th>
                                            <td class="text-end fw-semibold text-dark">
                                                <?= sanitize($receiptData['provider_name']) ?> (<?= sanitize($receiptData['provider_state']) ?>)
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Consumer Number / Account</th>
                                            <td class="text-end fw-semibold text-dark">
                                                <?= sanitize($receiptData['consumer_number']) ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Consumer Name</th>
                                            <td class="text-end fw-semibold text-dark">
                                                <?= sanitize($receiptData['consumer_name']) ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Original Bill Date</th>
                                            <td class="text-end text-secondary">
                                                <?= date('d M Y', strtotime($receiptData['bill_date'])) ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Original Due Date</th>
                                            <td class="text-end text-secondary">
                                                <?= date('d M Y', strtotime($receiptData['due_date'])) ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Settlement Date & Time</th>
                                            <td class="text-end text-secondary">
                                                <?= $receiptData['paid_at'] ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Payment Method</th>
                                            <td class="text-end text-dark">
                                                <i class="bi bi-wallet2 text-primary me-1"></i> PayBen Digital Wallet
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Remaining Wallet Balance</th>
                                            <td class="text-end fw-bold text-success">
                                                <?= formatCurrency($receiptData['balance_after']) ?>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- BBPS Compliance Assurance Stamp -->
                            <div class="p-3 bg-light rounded-3 d-flex align-items-center gap-3 border mb-4">
                                <div class="bg-primary text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                    <i class="bi bi-shield-lock-fill fs-5"></i>
                                </div>
                                <div class="small">
                                    <span class="fw-bold text-dark d-block">BBPS Simulated Transaction Acknowledgment</span>
                                    <span class="text-muted">This transaction was processed in the PayBen testing sandbox. No real fiat funds were transferred.</span>
                                </div>
                            </div>

                            <!-- Print / Action Controls -->
                            <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center no-print">
                                <a href="<?= APP_URL ?>/pages/electricity.php" class="btn btn-outline-secondary px-3 rounded-pill fw-semibold">
                                    <i class="bi bi-arrow-left me-1"></i> Pay Another Bill
                                </a>
                                <div class="d-flex gap-2">
                                    <button onclick="window.print()" class="btn btn-outline-primary px-3 rounded-pill fw-semibold">
                                        <i class="bi bi-printer me-1"></i> Print Receipt
                                    </button>
                                    <a href="<?= APP_URL ?>/pages/transactions.php" class="btn btn-primary px-4 rounded-pill fw-semibold">
                                        <i class="bi bi-receipt me-1"></i> View History
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                <!-- ========================================================== -->
                <!-- STANDARD FORM & BILL DETAILS VIEW                          -->
                <!-- ========================================================== -->
                <?php else: ?>
                    <div class="row g-4">
                        <!-- Left Main Column: Form & Bill Details -->
                        <div class="col-lg-8">
                            <!-- Step 1: Bill Fetch Section -->
                            <div class="card shadow-sm border-0 rounded-4 mb-4">
                                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-primary rounded-circle p-2 d-inline-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                                            1
                                        </span>
                                        <h5 class="card-title fw-bold mb-0 text-dark">Fetch Electricity Bill</h5>
                                    </div>
                                    <span class="badge bg-light text-secondary border">Instant BBPS Lookup</span>
                                </div>

                                <div class="card-body p-4">
                                    <form method="POST" action="<?= APP_URL ?>/pages/electricity.php" id="billFetchForm">
                                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                        <input type="hidden" name="action" value="fetch_bill">

                                        <!-- Electricity Provider Dropdown -->
                                        <div class="mb-3">
                                            <label for="provider_id" class="form-label fw-bold text-dark small text-uppercase">
                                                Electricity Distribution Board / Provider <span class="text-danger">*</span>
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light text-muted border-end-0">
                                                    <i class="bi bi-building"></i>
                                                </span>
                                                <select class="form-select border-start-0 ps-0" id="provider_id" name="provider_id" required>
                                                    <option value="">-- Choose Your Electricity Provider --</option>
                                                    <?php
                                                    $currentState = '';
                                                    foreach ($providers as $prov):
                                                        if ($currentState !== $prov['state']) {
                                                            if ($currentState !== '') echo '</optgroup>';
                                                            $currentState = $prov['state'];
                                                            echo '<optgroup label="' . htmlspecialchars($currentState) . '">';
                                                        }
                                                        $selected = ($providerId === (int)$prov['id']) ? 'selected' : '';
                                                        echo '<option value="' . $prov['id'] . '" ' . $selected . '>';
                                                        echo htmlspecialchars($prov['name']);
                                                        echo '</option>';
                                                    endforeach;
                                                    if ($currentState !== '') echo '</optgroup>';
                                                    ?>
                                                </select>
                                            </div>
                                            <div class="form-text small text-muted">
                                                Select state board (e.g. Tata Power Mumbai, BSES Rajdhani, BESCOM, Torrent Power).
                                            </div>
                                        </div>

                                        <!-- Consumer Number Input -->
                                        <div class="mb-4">
                                            <label for="consumer_number" class="form-label fw-bold text-dark small text-uppercase">
                                                Consumer Account / Connection Number <span class="text-danger">*</span>
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light text-muted border-end-0">
                                                    <i class="bi bi-hash"></i>
                                                </span>
                                                <input type="text" 
                                                       class="form-control border-start-0 ps-0" 
                                                       id="consumer_number" 
                                                       name="consumer_number" 
                                                       value="<?= sanitize($consumerNumber) ?>" 
                                                       placeholder="e.g. 1001234567 or 2008765432" 
                                                       maxlength="20" 
                                                       required>
                                            </div>
                                            <div class="form-text small text-muted">
                                                Found at the top of your monthly physical electricity statement.
                                            </div>
                                        </div>

                                        <!-- Quick Demo Fill Helpers -->
                                        <?php if (!empty($demoBills)): ?>
                                            <div class="mb-4 p-3 bg-light rounded-3 border">
                                                <span class="small fw-bold text-muted d-block mb-2">
                                                    <i class="bi bi-magic me-1 text-primary"></i> Quick Test Sandbox Accounts (Click to Auto-Fill):
                                                </span>
                                                <div class="d-flex flex-wrap gap-2">
                                                    <?php foreach ($demoBills as $demo): ?>
                                                        <button type="button" 
                                                                class="btn btn-sm quick-fill-chip rounded-pill py-1 px-3 text-start d-flex align-items-center gap-1"
                                                                onclick="fillDemoData(<?= (int)$demo['provider_id'] ?>, '<?= sanitize($demo['consumer_number']) ?>')">
                                                            <i class="bi bi-person-fill text-secondary"></i>
                                                            <span class="fw-semibold"><?= sanitize($demo['consumer_name']) ?></span>
                                                            <span class="badge bg-secondary bg-opacity-25 text-dark ms-1"><?= sanitize($demo['consumer_number']) ?></span>
                                                            <?php if ($demo['status'] === 'paid'): ?>
                                                                <span class="badge bg-success-subtle text-success ms-1">Paid Test</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-warning-subtle text-warning ms-1"><?= formatCurrency($demo['bill_amount']) ?></span>
                                                            <?php endif; ?>
                                                        </button>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                        <!-- Submit Button -->
                                        <div class="d-flex align-items-center gap-3">
                                            <button type="submit" class="btn btn-primary px-4 py-2 rounded-pill fw-semibold shadow-sm d-inline-flex align-items-center gap-2">
                                                <i class="bi bi-search"></i> Fetch Bill Details
                                            </button>
                                            <?php if (!empty($consumerNumber)): ?>
                                                <a href="<?= APP_URL ?>/pages/electricity.php" class="btn btn-light px-3 py-2 rounded-pill text-secondary fw-semibold">
                                                    Reset Form
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <!-- Step 2: Bill Details Section (Shown when bill is retrieved) -->
                            <?php if (!empty($fetchedBill)): ?>
                                <div class="card shadow-sm border-0 rounded-4 mb-4 border-top border-4 border-warning">
                                    <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-warning text-dark rounded-circle p-2 d-inline-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                                                2
                                            </span>
                                            <h5 class="card-title fw-bold mb-0 text-dark">Pending Bill Details</h5>
                                        </div>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-1 rounded-pill">
                                            <i class="bi bi-clock-history me-1"></i> Due for Payment
                                        </span>
                                    </div>

                                    <div class="card-body p-4">
                                        <!-- Consumer Summary Grid -->
                                        <div class="row g-3 mb-4">
                                            <div class="col-sm-6">
                                                <div class="p-3 bg-light rounded-3 h-100 border">
                                                    <span class="text-muted small text-uppercase d-block mb-1">Consumer Name</span>
                                                    <span class="fs-6 fw-bold text-dark"><?= sanitize($fetchedBill['consumer_name']) ?></span>
                                                </div>
                                            </div>
                                            <div class="col-sm-6">
                                                <div class="p-3 bg-light rounded-3 h-100 border">
                                                    <span class="text-muted small text-uppercase d-block mb-1">Consumer / CA Number</span>
                                                    <span class="fs-6 fw-bold text-dark font-monospace"><?= sanitize($fetchedBill['consumer_number']) ?></span>
                                                </div>
                                            </div>
                                            <div class="col-sm-6">
                                                <div class="p-3 bg-light rounded-3 h-100 border">
                                                    <span class="text-muted small text-uppercase d-block mb-1">Electricity Board</span>
                                                    <span class="fs-6 fw-bold text-dark"><?= sanitize($fetchedBill['provider_name']) ?></span>
                                                    <span class="text-muted small d-block"><?= sanitize($fetchedBill['provider_state']) ?></span>
                                                </div>
                                            </div>
                                            <div class="col-sm-6">
                                                <div class="p-3 bg-light rounded-3 h-100 border">
                                                    <span class="text-muted small text-uppercase d-block mb-1">Bill Date &amp; Due Date</span>
                                                    <span class="fs-6 fw-bold text-dark"><?= date('d M Y', strtotime($fetchedBill['bill_date'])) ?></span>
                                                    <span class="text-danger small d-block fw-semibold">
                                                        <i class="bi bi-calendar-event me-1"></i> Due: <?= date('d M Y', strtotime($fetchedBill['due_date'])) ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Bill Amount Breakdown & Payment Card -->
                                        <div class="bill-amount-display mb-4">
                                            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
                                                <div>
                                                    <span class="text-muted small text-uppercase fw-bold tracking-wider d-block">Total Payable Amount</span>
                                                    <div class="display-6 fw-extrabold text-primary">
                                                        <?= formatCurrency($fetchedBill['bill_amount']) ?>
                                                    </div>
                                                    <div class="small text-muted mt-1">
                                                        <i class="bi bi-check2-circle text-success me-1"></i> Convenience Fee: <strong>₹0.00 (Zero Fee)</strong>
                                                    </div>
                                                </div>
                                                <div class="text-sm-end">
                                                    <span class="simulated-pill mb-1">
                                                        <i class="bi bi-shield-check"></i> BBPS Verified
                                                    </span>
                                                    <div class="text-muted small">Status: <strong>Unpaid</strong></div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Step 3: Payment Section with Wallet Balance Validation -->
                                        <?php
                                        $userWalletBalance = (float)($currentUser['wallet_balance'] ?? 0.00);
                                        $billAmountVal = (float)$fetchedBill['bill_amount'];
                                        $isBalanceSufficient = $userWalletBalance >= $billAmountVal;
                                        ?>

                                        <div class="p-3 rounded-3 mb-4 <?= $isBalanceSufficient ? 'bg-success-subtle border border-success-subtle' : 'bg-danger-subtle border border-danger-subtle' ?>">
                                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                                <div class="d-flex align-items-center gap-2">
                                                    <i class="bi <?= $isBalanceSufficient ? 'bi-check-circle-fill text-success' : 'bi-exclamation-triangle-fill text-danger' ?> fs-5"></i>
                                                    <div>
                                                        <span class="fw-bold text-dark d-block small">PayBen Digital Wallet Balance:</span>
                                                        <span class="fs-5 fw-bold <?= $isBalanceSufficient ? 'text-success' : 'text-danger' ?>">
                                                            <?= formatCurrency($userWalletBalance) ?>
                                                        </span>
                                                    </div>
                                                </div>
                                                <?php if (!$isBalanceSufficient): ?>
                                                    <a href="<?= APP_URL ?>/pages/wallet.php" class="btn btn-sm btn-danger px-3 rounded-pill fw-semibold">
                                                        <i class="bi bi-plus-circle me-1"></i> Add Funds (Need <?= formatCurrency($billAmountVal - $userWalletBalance) ?>)
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-success small fw-semibold">
                                                        Balance after settlement: <strong><?= formatCurrency($userWalletBalance - $billAmountVal) ?></strong>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <!-- Payment Processing Form -->
                                        <form method="POST" action="<?= APP_URL ?>/pages/electricity.php" id="billPaymentForm">
                                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                            <input type="hidden" name="action" value="pay_bill">
                                            <input type="hidden" name="bill_id" value="<?= (int)$fetchedBill['id'] ?>">
                                            <input type="hidden" name="provider_id" value="<?= (int)$fetchedBill['provider_id'] ?>">
                                            <input type="hidden" name="consumer_number" value="<?= sanitize($fetchedBill['consumer_number']) ?>">

                                            <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 pt-2">
                                                <div class="small text-muted">
                                                    <i class="bi bi-info-circle text-primary me-1"></i>
                                                    Clicking pay initiates an instant debit from your wallet balance.
                                                </div>
                                                <button type="submit" 
                                                        class="btn btn-fintech-gradient px-4 py-2 rounded-pill fw-bold shadow d-inline-flex align-items-center justify-content-center gap-2"
                                                        <?= !$isBalanceSufficient ? 'disabled' : '' ?>>
                                                    <i class="bi bi-wallet2"></i> Pay Bill <?= formatCurrency($fetchedBill['bill_amount']) ?>
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Right Info Sidebar Column -->
                        <div class="col-lg-4">
                            <!-- Digital Wallet Status Card -->
                            <div class="card shadow-sm border-0 rounded-4 mb-4">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-center justify-content-between mb-3">
                                        <span class="text-muted small text-uppercase fw-bold">Active Wallet</span>
                                        <span class="badge bg-success-subtle text-success rounded-pill px-2 py-1">Active</span>
                                    </div>
                                    <div class="display-6 fw-bold text-dark mb-1">
                                        <?= formatCurrency($currentUser['wallet_balance'] ?? 0.00) ?>
                                    </div>
                                    <p class="text-muted small mb-3">
                                        Instant deductions with automated debit receipt generation.
                                    </p>
                                    <a href="<?= APP_URL ?>/pages/wallet.php" class="btn btn-outline-primary btn-sm w-100 rounded-pill fw-semibold">
                                        <i class="bi bi-plus-lg me-1"></i> Top-up Digital Wallet
                                    </a>
                                </div>
                            </div>

                            <!-- Why Pay on PayBen? Benefits Card -->
                            <div class="card shadow-sm border-0 rounded-4 mb-4">
                                <div class="card-header bg-white py-3 border-bottom">
                                    <h6 class="fw-bold mb-0 text-dark">Why Pay via PayBen?</h6>
                                </div>
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-start gap-3 mb-3">
                                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                                            <i class="bi bi-lightning-charge-fill"></i>
                                        </div>
                                        <div>
                                            <span class="fw-bold text-dark d-block small">Instant BBPS Settlement</span>
                                            <span class="text-muted small">Real-time status confirmation sent to your registered account.</span>
                                        </div>
                                    </div>

                                    <div class="d-flex align-items-start gap-3 mb-3">
                                        <div class="bg-success bg-opacity-10 text-success rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                                            <i class="bi bi-percent"></i>
                                        </div>
                                        <div>
                                            <span class="fw-bold text-dark d-block small">Zero Convenience Fees</span>
                                            <span class="text-muted small">Pay exact billed amount with no hidden processing surcharges.</span>
                                        </div>
                                    </div>

                                    <div class="d-flex align-items-start gap-3">
                                        <div class="bg-info bg-opacity-10 text-info rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                                            <i class="bi bi-shield-check"></i>
                                        </div>
                                        <div>
                                            <span class="fw-bold text-dark d-block small">Digital Passbook</span>
                                            <span class="text-muted small">Instant downloadable and printable tax invoices for every payment.</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Testing Notes Card -->
                            <div class="card shadow-sm border-0 rounded-4 bg-light">
                                <div class="card-body p-4">
                                    <h6 class="fw-bold text-dark mb-2 d-flex align-items-center gap-2">
                                        <i class="bi bi-cpu text-warning"></i> Sandbox Simulation Note
                                    </h6>
                                    <p class="text-muted small mb-0">
                                        This utility bill payment module runs inside a test sandbox. All bill lookups query the simulated <code>sample_bills</code> database ledger. Balance movements are logged securely in your PayBen double-entry wallet ledger without contacting live bank gateways.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </main>

        <!-- Standard Bottom Application Footer -->
        <footer class="app-footer">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
                <span>&copy; <?= date('Y') ?> PayBen Technologies. All rights reserved.</span>
                <span class="text-muted small">
                    <i class="bi bi-shield-lock-fill text-success me-1"></i> 256-Bit SSL Encrypted Sandbox
                </span>
            </div>
        </footer>
    </div>
</div>

<script>
/**
 * Auto-fill demo account details for rapid testing
 */
function fillDemoData(providerId, consumerNum) {
    const provSelect = document.getElementById('provider_id');
    const consInput = document.getElementById('consumer_number');
    if (provSelect && consInput) {
        provSelect.value = providerId;
        consInput.value = consumerNum;
        consInput.focus();
        consInput.classList.add('is-valid');
        setTimeout(() => consInput.classList.remove('is-valid'), 1500);
    }
}
</script>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
