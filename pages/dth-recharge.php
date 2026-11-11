<?php
// dth-recharge page

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';

// Enforce user authentication
requireLogin();

$pdo = getDBConnection();
$currentUser = getCurrentUser();

// Compatibility for name property across layouts
if ($currentUser && !isset($currentUser['name']) && isset($currentUser['full_name'])) {
    $currentUser['name'] = $currentUser['full_name'];
}

$errors = [];
$receiptData = null;

// Fetch active DTH operators for dropdown
try {
    $opStmt = $pdo->prepare("
        SELECT id, name, code, logo, status 
        FROM operators 
        WHERE type = 'dth' AND status = 'active' 
        ORDER BY name ASC
    ");
    $opStmt->execute();
    $dthOperators = $opStmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed to load DTH operators: " . $e->getMessage());
    $dthOperators = [];
    $errors['general'] = "Could not load DTH operator list. Please refresh the page.";
}

// Retain form inputs on validation error
$inputCustomerId = sanitize($_POST['customer_id'] ?? ($_GET['customer_id'] ?? ''));
$inputOperatorId = filter_var($_POST['operator_id'] ?? ($_GET['operator_id'] ?? ''), FILTER_VALIDATE_INT) ?: 0;
$inputAmount     = sanitize($_POST['amount'] ?? ($_GET['amount'] ?? ''));
$inputPlanId     = filter_var($_POST['plan_id'] ?? '', FILTER_VALIDATE_INT) ?: 0;
$inputPlanName   = sanitize($_POST['plan_name'] ?? '');
$inputPlanDesc   = sanitize($_POST['plan_description'] ?? '');

// -------------------------------------------------------------------------
// POST HANDLER: Process Simulated DTH Recharge
// -------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Verify CSRF Token
    $token = $_POST['csrf_token'] ?? '';
    if (!validateCSRFToken($token)) {
        $errors['csrf'] = 'Session security token expired or invalid. Please reload the page.';
    }

    // 2. Server-Side Validation: Customer / Subscriber ID (8 to 15 alphanumeric characters)
    $cleanCustomerId = preg_replace('/[^a-zA-Z0-9]/', '', $inputCustomerId);
    if (empty($cleanCustomerId)) {
        $errors['customer_id'] = 'Subscriber / Customer ID is required.';
    } elseif (strlen($cleanCustomerId) < 8 || strlen($cleanCustomerId) > 15) {
        $errors['customer_id'] = 'Please enter a valid DTH Subscriber ID (8 to 15 digits/characters).';
    }

    // 3. Server-Side Validation: Operator
    $selectedOperator = null;
    if (empty($inputOperatorId)) {
        $errors['operator_id'] = 'Please choose your DTH service provider.';
    } else {
        foreach ($dthOperators as $op) {
            if ((int)$op['id'] === (int)$inputOperatorId) {
                $selectedOperator = $op;
                break;
            }
        }
        if (!$selectedOperator) {
            $errors['operator_id'] = 'The selected DTH operator is invalid or currently inactive.';
        }
    }

    // 4. Server-Side Validation: Recharge Amount
    $amountFloat = filter_var($inputAmount, FILTER_VALIDATE_FLOAT);
    if ($amountFloat === false || $amountFloat <= 0) {
        $errors['amount'] = 'Please enter a valid recharge amount greater than ₹0.';
    } elseif ($amountFloat < 10) {
        $errors['amount'] = 'Minimum DTH recharge amount is ₹10.00.';
    } elseif ($amountFloat > 25000) {
        $errors['amount'] = 'Maximum DTH recharge limit is ₹25,000.00.';
    }

    // 5. Database Transaction Execution
    if (empty($errors)) {
        try {
            $userId = (int)$currentUser['id'];

            // Begin Atomic Transaction
            $pdo->beginTransaction();

            // Row-lock wallet record to guarantee atomic balance consistency
            $walletStmt = $pdo->prepare("SELECT id, balance FROM wallets WHERE user_id = ? FOR UPDATE");
            $walletStmt->execute([$userId]);
            $wallet = $walletStmt->fetch();

            if (!$wallet) {
                // Initialize wallet if missing
                $initWalletStmt = $pdo->prepare("INSERT INTO wallets (user_id, balance) VALUES (?, 0.00)");
                $initWalletStmt->execute([$userId]);
                $walletId = (int)$pdo->lastInsertId();
                $walletBalance = 0.00;
            } else {
                $walletId = (int)$wallet['id'];
                $walletBalance = (float)$wallet['balance'];
            }

            // Check if user has sufficient funds
            if ($walletBalance < $amountFloat) {
                $pdo->rollBack();
                $diff = $amountFloat - $walletBalance;
                $errors['wallet'] = "Insufficient wallet balance. You need " . formatCurrency($diff) . " more to complete this DTH recharge.";
            } else {
                // Compute new balance
                $newBalance = round($walletBalance - $amountFloat, 2);

                // A. Deduct balance from user wallet
                $updateWalletStmt = $pdo->prepare("UPDATE wallets SET balance = ? WHERE id = ?");
                $updateWalletStmt->execute([$newBalance, $walletId]);

                // B. Generate unique reference ID with DT prefix
                $transactionId = generateTransactionID('DT');

                // C. Log double-entry debit in wallet_transactions
                $descriptionText = "DTH Recharge - {$selectedOperator['name']} (ID: {$cleanCustomerId})";
                if (!empty($inputPlanName)) {
                    $descriptionText .= " [{$inputPlanName}]";
                }

                $wtStmt = $pdo->prepare("
                    INSERT INTO wallet_transactions 
                        (user_id, wallet_id, type, amount, description, reference_id, balance_after) 
                    VALUES 
                        (?, ?, 'debit', ?, ?, ?, ?)
                ");
                $wtStmt->execute([
                    $userId,
                    $walletId,
                    $amountFloat,
                    $descriptionText,
                    $transactionId,
                    $newBalance
                ]);

                // D. Insert record into transactions master log
                $planDetailsLog = !empty($inputPlanName) ? $inputPlanName : 'DTH Monthly Pack / Top-up';
                if (!empty($inputPlanDesc)) {
                    $planDetailsLog .= ' - ' . $inputPlanDesc;
                }

                $txStmt = $pdo->prepare("
                    INSERT INTO transactions 
                        (user_id, transaction_id, type, mobile_or_consumer_number, operator_or_provider, amount, status, payment_method, description) 
                    VALUES 
                        (?, ?, 'dth_recharge', ?, ?, ?, 'SUCCESS', 'Wallet', ?)
                ");
                $txStmt->execute([
                    $userId,
                    $transactionId,
                    $cleanCustomerId,
                    $selectedOperator['name'],
                    $amountFloat,
                    $planDetailsLog
                ]);

                // Commit Transaction
                $pdo->commit();

                // Refresh current user wallet balance in session/memory
                $currentUser['wallet_balance'] = $newBalance;

                // Prepare Receipt Details
                $receiptData = [
                    'transaction_id'  => $transactionId,
                    'status'          => 'SUCCESS',
                    'is_simulated'    => true,
                    'customer_id'     => $cleanCustomerId,
                    'operator_name'   => $selectedOperator['name'],
                    'operator_code'   => $selectedOperator['code'],
                    'amount'          => $amountFloat,
                    'plan_name'       => $inputPlanName ?: 'Monthly DTH Subscription',
                    'plan_desc'       => $inputPlanDesc,
                    'payment_method'  => 'PayBen Digital Wallet',
                    'balance_before'  => $walletBalance,
                    'balance_after'   => $newBalance,
                    'created_at'      => date('d M Y, h:i A')
                ];

                setFlashMessage('success', "DTH recharge of " . formatCurrency($amountFloat) . " completed successfully! [SIMULATED]");
            }

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("DTH recharge execution error: " . $e->getMessage());
            $errors['general'] = "A database error occurred during payment processing. No funds were debited.";
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Unexpected DTH recharge error: " . $e->getMessage());
            $errors['general'] = "An unexpected error occurred. Please try again.";
        }
    }
}

// -------------------------------------------------------------------------
// View Rendering Setup
// -------------------------------------------------------------------------
$pageTitle   = 'DTH Recharge';
$currentPage = 'dth-recharge';

require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="app-wrapper">
    <!-- Left Sidebar Navigation -->
    <?php require_once dirname(__DIR__) . '/includes/sidebar.php'; ?>
    <div class="main-content">
        <!-- Top App Navbar -->
        <?php require_once dirname(__DIR__) . '/includes/navbar.php'; ?>

        <main class="content-body">
            <div class="container-fluid px-0 px-md-3">

                <!-- Breadcrumb & Page Header -->
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                    <div>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-1 small">
                                <li class="breadcrumb-item"><a href="<?= APP_URL ?>/pages/dashboard.php">Home</a></li>
                                <li class="breadcrumb-item"><a href="<?= APP_URL ?>/pages/dashboard.php">Recharge & Utilities</a></li>
                                <li class="breadcrumb-item active" aria-current="page">DTH Recharge</li>
                            </ol>
                        </nav>
                        <h2 class="h3 fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                            <i class="bi bi-tv-fill text-primary"></i> DTH &amp; Digital TV Recharge
                        </h2>
                    </div>

                    <!-- Live Wallet Quick Pill -->
                    <div class="d-flex align-items-center gap-2">
                        <div class="card border-0 bg-white shadow-sm px-3 py-2 rounded-3 d-flex flex-row align-items-center gap-3">
                            <div class="stat-icon bg-primary bg-opacity-10 text-primary m-0" style="width: 40px; height: 40px;">
                                <i class="bi bi-wallet2 fs-5"></i>
                            </div>
                            <div>
                                <div class="text-muted small text-uppercase fw-bold" style="font-size: 0.65rem; letter-spacing: 0.5px;">Wallet Balance</div>
                                <div class="fw-bold text-dark fs-6" id="headerWalletDisplay">
                                    <?= formatCurrency($currentUser['wallet_balance'] ?? 0.00) ?>
                                </div>
                            </div>
                            <a href="<?= APP_URL ?>/pages/wallet.php" class="btn btn-outline-primary btn-sm ms-2" title="Top-up Wallet">
                                <i class="bi bi-plus-lg"></i> Add
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Simulation Mode Notice Banner -->
                <div class="alert alert-info border-0 shadow-sm rounded-3 d-flex align-items-center gap-3 mb-4" role="alert">
                    <div class="fs-4 text-info"><i class="bi bi-info-circle-fill"></i></div>
                    <div class="flex-grow-1 small">
                        <strong>Simulated Sandbox Environment:</strong> DTH top-up tests real wallet balance deductions and double-entry ledger logs without calling live satellite broadcast APIs. All successful orders display a clear <span class="badge bg-success-subtle text-success border border-success">SIMULATED</span> status badge.
                    </div>
                </div>

                <!-- Global Form Errors Alert -->
                <?php if (!empty($errors['general']) || !empty($errors['csrf']) || !empty($errors['wallet'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-3 d-flex align-items-start gap-2 mb-4" role="alert">
                        <i class="bi bi-exclamation-triangle-fill fs-5 mt-1 text-danger"></i>
                        <div>
                            <strong>Payment Error:</strong>
                            <?= htmlspecialchars($errors['wallet'] ?? $errors['general'] ?? $errors['csrf'] ?? 'An error occurred.', ENT_QUOTES, 'UTF-8') ?>
                            <?php if (!empty($errors['wallet'])): ?>
                                <div class="mt-2">
                                    <a href="<?= APP_URL ?>/pages/wallet.php" class="btn btn-sm btn-danger rounded-pill">
                                        <i class="bi bi-plus-circle me-1"></i> Add Money to Wallet
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if ($receiptData): ?>
                    <!-- ========================================================= -->
                    <!-- DTH TRANSACTION RECEIPT CARD (AFTER SUCCESSFUL RECHARGE)   -->
                    <!-- ========================================================= -->
                    <div class="row justify-content-center mb-5" id="receiptPrintArea">
                        <div class="col-lg-8 col-xl-7">
                            <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                                <!-- Top Success Banner -->
                                <div class="card-header bg-success text-white py-4 text-center border-0" style="background: linear-gradient(135deg, #059669 0%, #10b981 100%);">
                                    <div class="rounded-circle bg-white text-success d-inline-flex align-items-center justify-content-center mb-2 shadow" style="width: 64px; height: 64px;">
                                        <i class="bi bi-check-lg fs-1 fw-bold"></i>
                                    </div>
                                    <h4 class="fw-bold mb-1">DTH Recharge Successful!</h4>
                                    <span class="badge bg-white text-success fw-bold px-3 py-1 rounded-pill text-uppercase">
                                        SIMULATED TRANSACTION
                                    </span>
                                </div>

                                <div class="card-body p-4 p-md-5">
                                    <div class="text-center mb-4">
                                        <div class="text-muted small text-uppercase tracking-wider">Amount Paid</div>
                                        <div class="display-5 fw-extrabold text-dark my-1">
                                            <?= formatCurrency($receiptData['amount']) ?>
                                        </div>
                                        <div class="badge bg-success-subtle text-success px-3 py-1 rounded-pill">
                                            <i class="bi bi-patch-check-fill me-1"></i> TV Signal Activated Instantly
                                        </div>
                                    </div>

                                    <!-- Receipt Details Grid -->
                                    <div class="bg-light rounded-3 p-3 p-md-4 mb-4">
                                        <div class="row g-3">
                                            <div class="col-sm-6">
                                                <div class="text-muted small">Transaction ID</div>
                                                <div class="fw-bold text-dark font-monospace d-flex align-items-center gap-2">
                                                    <span><?= htmlspecialchars($receiptData['transaction_id'], ENT_QUOTES, 'UTF-8') ?></span>
                                                    <button type="button" class="btn btn-link btn-sm p-0 text-muted" onclick="copyToClipboard('<?= $receiptData['transaction_id'] ?>')" title="Copy ID">
                                                        <i class="bi bi-copy"></i>
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="col-sm-6">
                                                <div class="text-muted small">Date &amp; Time</div>
                                                <div class="fw-semibold text-dark"><?= $receiptData['created_at'] ?></div>
                                            </div>

                                            <div class="col-sm-6">
                                                <div class="text-muted small">Subscriber / Customer ID</div>
                                                <div class="fw-bold text-dark fs-6 font-monospace"><?= htmlspecialchars($receiptData['customer_id'], ENT_QUOTES, 'UTF-8') ?></div>
                                            </div>

                                            <div class="col-sm-6">
                                                <div class="text-muted small">DTH Provider</div>
                                                <div class="fw-semibold text-dark"><?= htmlspecialchars($receiptData['operator_name'], ENT_QUOTES, 'UTF-8') ?></div>
                                            </div>

                                            <div class="col-sm-6">
                                                <div class="text-muted small">Pack / Plan</div>
                                                <div class="fw-semibold text-dark"><?= htmlspecialchars($receiptData['plan_name'], ENT_QUOTES, 'UTF-8') ?></div>
                                            </div>

                                            <div class="col-sm-6">
                                                <div class="text-muted small">Payment Method</div>
                                                <div class="fw-semibold text-dark d-flex align-items-center gap-1">
                                                    <i class="bi bi-wallet2 text-primary"></i> <?= $receiptData['payment_method'] ?>
                                                </div>
                                            </div>

                                            <div class="col-12 pt-2 border-top">
                                                <div class="d-flex justify-content-between align-items-center text-muted small">
                                                    <span>Previous Wallet Balance: <?= formatCurrency($receiptData['balance_before']) ?></span>
                                                    <span class="fw-bold text-dark">Updated Wallet Balance: <?= formatCurrency($receiptData['balance_after']) ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Receipt Action Buttons -->
                                    <div class="d-flex flex-wrap gap-2 justify-content-center d-print-none">
                                        <button type="button" class="btn btn-outline-secondary px-4 rounded-pill" onclick="window.print();">
                                            <i class="bi bi-printer me-1"></i> Print Receipt
                                        </button>
                                        <a href="<?= APP_URL ?>/pages/dth-recharge.php" class="btn btn-primary px-4 rounded-pill">
                                            <i class="bi bi-arrow-repeat me-1"></i> Make Another DTH Recharge
                                        </a>
                                        <a href="<?= APP_URL ?>/pages/transactions.php" class="btn btn-light px-4 rounded-pill border">
                                            <i class="bi bi-receipt me-1"></i> View Transactions
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- ============================================================= -->
                <!-- DTH FORM & PLANS SECTION                                      -->
                <!-- ============================================================= -->
                <div class="row g-4 <?= $receiptData ? 'd-none' : '' ?>">
                    <!-- Left Column: Form & Live Summary -->
                    <div class="col-lg-5 col-xl-5">
                        <div class="card border-0 shadow-sm rounded-4 mb-4">
                            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                                <h5 class="card-header-title m-0 fs-6">
                                    <i class="bi bi-tv text-primary fs-5"></i> Enter DTH Subscription Details
                                </h5>
                                <span class="badge bg-primary-subtle text-primary small">Instant</span>
                            </div>

                            <div class="card-body p-4">
                                <form action="<?= htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') ?>" method="POST" id="dthForm" novalidate>
                                    <!-- CSRF Protection -->
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCSRFToken(), ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="plan_id" id="plan_id" value="<?= htmlspecialchars((string)$inputPlanId, ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="plan_name" id="plan_name" value="<?= htmlspecialchars($inputPlanName, ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="plan_description" id="plan_description" value="<?= htmlspecialchars($inputPlanDesc, ENT_QUOTES, 'UTF-8') ?>">
                                    <div class="mb-3">
                                        <label for="operator_id" class="form-label">
                                            Select DTH Operator <span class="text-danger">*</span>
                                        </label>
                                        <select 
                                            class="form-select form-select-lg fs-6 <?= isset($errors['operator_id']) ? 'is-invalid' : '' ?>" 
                                            id="operator_id" 
                                            name="operator_id" 
                                            required
                                        >
                                            <option value="">-- Choose DTH Service Provider --</option>
                                            <?php foreach ($dthOperators as $op): ?>
                                                <option 
                                                    value="<?= (int)$op['id'] ?>" 
                                                    data-name="<?= htmlspecialchars($op['name'], ENT_QUOTES, 'UTF-8') ?>" 
                                                    data-code="<?= htmlspecialchars($op['code'], ENT_QUOTES, 'UTF-8') ?>"
                                                    <?= ((int)$inputOperatorId === (int)$op['id']) ? 'selected' : '' ?>
                                                >
                                                    <?= htmlspecialchars($op['name'], ENT_QUOTES, 'UTF-8') ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php if (isset($errors['operator_id'])): ?>
                                            <div class="text-danger small mt-1"><?= htmlspecialchars($errors['operator_id'], ENT_QUOTES, 'UTF-8') ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="mb-3">
                                        <label for="customer_id" class="form-label d-flex justify-content-between align-items-center">
                                            <span>Subscriber ID / Customer ID <span class="text-danger">*</span></span>
                                            <span class="text-muted small fw-normal" id="idFormatHint" style="font-size: 0.72rem;">e.g. 10 or 11 digits</span>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light text-muted">
                                                <i class="bi bi-person-badge"></i>
                                            </span>
                                            <input 
                                                type="text" 
                                                class="form-control form-control-lg fs-6 font-monospace <?= isset($errors['customer_id']) ? 'is-invalid' : '' ?>" 
                                                id="customer_id" 
                                                name="customer_id" 
                                                placeholder="Enter Subscriber / Smart Card ID" 
                                                maxlength="15" 
                                                value="<?= htmlspecialchars($inputCustomerId, ENT_QUOTES, 'UTF-8') ?>" 
                                                required 
                                                autocomplete="off"
                                            >
                                        </div>
                                        <div class="form-text small text-muted">
                                            Located on your DTH setup box or viewed via the remote 'Home/Info' button.
                                        </div>
                                        <?php if (isset($errors['customer_id'])): ?>
                                            <div class="text-danger small mt-1"><?= htmlspecialchars($errors['customer_id'], ENT_QUOTES, 'UTF-8') ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <label for="amount" class="form-label mb-0">
                                                Recharge Amount <span class="text-danger">*</span>
                                            </label>
                                            <a href="#plansContainer" class="small text-primary text-decoration-none fw-semibold" id="browsePlansLink">
                                                <i class="bi bi-grid-3x3-gap-fill me-1"></i> View Popular Packs
                                            </a>
                                        </div>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light fw-bold text-dark">₹</span>
                                            <input 
                                                type="number" 
                                                step="1" 
                                                min="10" 
                                                max="25000" 
                                                class="form-control form-control-lg fs-6 fw-bold <?= isset($errors['amount']) ? 'is-invalid' : '' ?>" 
                                                id="amount" 
                                                name="amount" 
                                                placeholder="Enter amount (e.g. 299)" 
                                                value="<?= htmlspecialchars($inputAmount, ENT_QUOTES, 'UTF-8') ?>" 
                                                required
                                            >
                                        </div>
                                        <?php if (isset($errors['amount'])): ?>
                                            <div class="text-danger small mt-1"><?= htmlspecialchars($errors['amount'], ENT_QUOTES, 'UTF-8') ?></div>
                                        <?php endif; ?>

                                        <!-- Quick Amount Chips -->
                                        <div class="mt-2 pt-1">
                                            <div class="text-muted small mb-1" style="font-size: 0.75rem;">Quick Amounts:</div>
                                            <div class="d-flex flex-wrap gap-1">
                                                <?php foreach ([199, 249, 299, 399, 499, 599, 999] as $qAmt): ?>
                                                    <button 
                                                        type="button" 
                                                        class="btn btn-sm btn-outline-secondary py-1 px-2 rounded-2 quick-chip" 
                                                        data-quick-amount="<?= $qAmt ?>" 
                                                        data-target-input="amount"
                                                        style="font-size: 0.75rem;"
                                                    >
                                                        ₹<?= $qAmt ?>
                                                    </button>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <div class="card border-0 shadow-sm rounded-4" id="summaryCard">
                            <div class="card-header bg-white py-3 border-bottom">
                                <h5 class="card-header-title m-0 fs-6">
                                    <i class="bi bi-receipt text-primary fs-5"></i> DTH Recharge Summary
                                </h5>
                            </div>
                            <div class="card-body p-4">
                                <ul class="list-group list-group-flush small mb-3">
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                        <span class="text-muted">Operator</span>
                                        <span class="fw-bold text-dark" id="summaryOperator">--</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                        <span class="text-muted">Customer ID</span>
                                        <span class="fw-bold text-dark font-monospace" id="summaryCustomer">--</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                        <span class="text-muted">Pack / Description</span>
                                        <span class="fw-semibold text-primary text-end" id="summaryPlanName">Monthly Recharge / Manual</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                        <span class="text-muted">Wallet Balance</span>
                                        <span class="fw-bold text-dark" id="summaryWalletBalance"><?= formatCurrency($currentUser['wallet_balance'] ?? 0.00) ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 bg-light rounded-2 px-2 mt-2">
                                        <span class="fw-bold text-dark">Total Amount</span>
                                        <span class="fw-extrabold text-primary fs-5" id="summaryPayable">₹0.00</span>
                                    </li>
                                </ul>

                                <!-- Balance Status Alert -->
                                <div id="balanceAlertBox" class="alert alert-warning py-2 px-3 small rounded-3 d-none mb-3">
                                    <i class="bi bi-exclamation-circle-fill me-1"></i>
                                    <span id="balanceAlertMsg">Insufficient wallet balance.</span>
                                </div>

                                <!-- Action Button: Triggers Form Submit -->
                                <button 
                                    type="button" 
                                    class="btn btn-fintech-gradient w-100 py-3 rounded-pill fw-bold shadow-sm d-flex align-items-center justify-content-center gap-2" 
                                    id="btnProceedRecharge"
                                >
                                    <i class="bi bi-tv-fill"></i>
                                    <span>Proceed to Recharge</span>
                                </button>
                                <div class="text-center mt-2">
                                    <small class="text-muted" style="font-size: 0.72rem;">
                                        <i class="bi bi-shield-check text-success"></i> Instant simulated broadcast activation with audit trail
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Interactive DTH Plans Browser -->
                    <div class="col-lg-7 col-xl-7">
                        <div class="card border-0 shadow-sm rounded-4" id="plansContainer">
                            <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-2">
                                <div>
                                    <h5 class="card-header-title m-0 fs-6">
                                        <i class="bi bi-collection-play text-primary fs-5"></i> Popular DTH Channel Packs
                                    </h5>
                                    <small class="text-muted" id="plansSubtitle">Select a provider to explore channel packs</small>
                                </div>
                            </div>

                            <div class="card-body p-4">
                                <!-- State 1: Placeholder before selecting operator -->
                                <div id="plansPlaceholder" class="text-center py-5">
                                    <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center text-muted mb-3" style="width: 80px; height: 80px;">
                                        <i class="bi bi-tv fs-1 text-primary opacity-50"></i>
                                    </div>
                                    <h6 class="fw-bold text-dark mb-1">Select a DTH Provider to View Packs</h6>
                                    <p class="text-muted small mx-auto" style="max-width: 380px;">
                                        Choose Tata Play, Airtel Digital TV, Dish TV, or Sun Direct to explore recommended monthly channel bouquets, HD sports packs, and regional packages.
                                    </p>
                                </div>

                                <!-- State 2: Spinner Loader during AJAX Fetch -->
                                <div id="plansLoading" class="text-center py-5 d-none">
                                    <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                                        <span class="visually-hidden">Loading packs...</span>
                                    </div>
                                    <h6 class="fw-bold text-dark">Fetching Channel Packs...</h6>
                                    <p class="text-muted small">Retrieving active DTH bouquets via PayBen API</p>
                                </div>

                                <!-- State 3: Empty Plans Alert -->
                                <div id="plansEmpty" class="text-center py-5 d-none">
                                    <div class="rounded-circle bg-warning-subtle text-warning d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                                        <i class="bi bi-exclamation-circle fs-2"></i>
                                    </div>
                                    <h6 class="fw-bold text-dark">No Pre-defined Packs Found</h6>
                                    <p class="text-muted small">You can enter your monthly subscription renewal amount manually in the form on the left.</p>
                                </div>

                                <!-- State 4: Rendered Plans Grid -->
                                <div id="plansGrid" class="row g-3 d-none">
                                    <!-- Dynamic DTH Plan Cards Injected via JavaScript -->
                                </div>
                            </div>
                        </div>

                        <!-- DTH Quick Help Box -->
                        <div class="card border-0 bg-white shadow-sm rounded-4 mt-4 p-4">
                            <h6 class="fw-bold text-dark mb-2 d-flex align-items-center gap-2">
                                <i class="bi bi-question-circle-fill text-primary"></i> Where to find your DTH Subscriber ID?
                            </h6>
                            <div class="row g-3 mt-1 small text-muted">
                                <div class="col-sm-6">
                                    <strong class="text-dark d-block">Tata Play (Tata Sky):</strong>
                                    Press the 'Home' button on your remote. Your 10-digit Subscriber ID is shown on the top-right corner.
                                </div>
                                <div class="col-sm-6">
                                    <strong class="text-dark d-block">Airtel Digital TV:</strong>
                                    Press the 'Menu' button on your remote or check the sticker at the bottom of your set-top box.
                                </div>
                                <div class="col-sm-6">
                                    <strong class="text-dark d-block">Dish TV:</strong>
                                    Press the 'Help' or 'Home' button on your remote. Your Viewing Card (VC) number starts with 0.
                                </div>
                                <div class="col-sm-6">
                                    <strong class="text-dark d-block">Sun Direct:</strong>
                                    Press the 'Info' button on your remote. Your Smart Card Number is displayed on screen.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </main>

        <!-- Standard Dashboard Footer -->
        <footer class="app-footer">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span>&copy; <?= date('Y') ?> PayBen Technologies. All rights reserved.</span>
                <span class="text-muted small d-flex align-items-center gap-2">
                    <i class="bi bi-shield-lock-fill text-success"></i> 256-Bit SSL Encrypted &bull; Instant Simulated Settlements
                </span>
            </div>
        </footer>
    </div>
</div>

<!-- ========================================================================= -->
<!-- CLIENT-SIDE SCRIPT: Dynamic AJAX DTH Plans & Live Summary                  -->
<!-- ========================================================================= -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const customerInput    = document.getElementById('customer_id');
    const operatorSelect   = document.getElementById('operator_id');
    const amountInput      = document.getElementById('amount');
    const planIdInput      = document.getElementById('plan_id');
    const planNameInput    = document.getElementById('plan_name');
    const planDescInput    = document.getElementById('plan_description');
    const dthForm          = document.getElementById('dthForm');
    const btnProceed       = document.getElementById('btnProceedRecharge');

    const summaryCustomer  = document.getElementById('summaryCustomer');
    const summaryOperator  = document.getElementById('summaryOperator');
    const summaryPlanName  = document.getElementById('summaryPlanName');
    const summaryPayable   = document.getElementById('summaryPayable');
    const balanceAlertBox  = document.getElementById('balanceAlertBox');
    const balanceAlertMsg  = document.getElementById('balanceAlertMsg');
    const idFormatHint     = document.getElementById('idFormatHint');

    const plansPlaceholder = document.getElementById('plansPlaceholder');
    const plansLoading     = document.getElementById('plansLoading');
    const plansEmpty       = document.getElementById('plansEmpty');
    const plansGrid        = document.getElementById('plansGrid');
    const plansSubtitle    = document.getElementById('plansSubtitle');

    const walletBalance    = parseFloat('<?= (float)($currentUser['wallet_balance'] ?? 0.00) ?>');
    let loadedPlans        = [];

    // Format Currency Helper
    function formatINR(amount) {
        const num = parseFloat(amount) || 0;
        return '₹' + num.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    // Update Live Summary Box
    function updateSummary() {
        const custId = customerInput ? customerInput.value.trim() : '';
        summaryCustomer.textContent = custId || '--';

        const selectedOp = operatorSelect ? operatorSelect.options[operatorSelect.selectedIndex] : null;
        if (selectedOp && selectedOp.value) {
            summaryOperator.textContent = selectedOp.getAttribute('data-name') || selectedOp.text;
        } else {
            summaryOperator.textContent = '--';
        }

        const planName = planNameInput ? planNameInput.value.trim() : '';
        summaryPlanName.textContent = planName || 'Monthly Recharge / Manual';

        const amount = parseFloat(amountInput ? amountInput.value : 0) || 0;
        summaryPayable.textContent = formatINR(amount);

        // Balance Verification
        if (amount > 0) {
            if (amount > walletBalance) {
                const diff = amount - walletBalance;
                balanceAlertBox.classList.remove('d-none');
                balanceAlertMsg.textContent = 'Insufficient balance! You need ' + formatINR(diff) + ' more in your wallet.';
                btnProceed.disabled = true;
                btnProceed.classList.add('opacity-75');
            } else {
                balanceAlertBox.classList.add('d-none');
                btnProceed.disabled = false;
                btnProceed.classList.remove('opacity-75');
            }
        } else {
            balanceAlertBox.classList.add('d-none');
            btnProceed.disabled = false;
            btnProceed.classList.remove('opacity-75');
        }
    }

    if (customerInput) customerInput.addEventListener('input', updateSummary);
    if (amountInput) amountInput.addEventListener('input', updateSummary);

    // Fetch DTH Channel Packs via AJAX
    async function fetchPlansForDTH(operatorId) {
        if (!operatorId) {
            plansPlaceholder.classList.remove('d-none');
            plansLoading.classList.add('d-none');
            plansEmpty.classList.add('d-none');
            plansGrid.classList.add('d-none');
            plansSubtitle.textContent = 'Select a provider to explore channel packs';
            return;
        }

        // Show spinner
        plansPlaceholder.classList.add('d-none');
        plansLoading.classList.remove('d-none');
        plansEmpty.classList.add('d-none');
        plansGrid.classList.add('d-none');
        plansSubtitle.textContent = 'Fetching available bouquets...';

        try {
            const endpoint = '<?= APP_URL ?>/api/recharge/plans.php?operator_id=' + encodeURIComponent(operatorId);
            const response = await fetch(endpoint, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error('API response code: ' + response.status);
            }

            const data = await response.json();
            plansLoading.classList.add('d-none');

            if (data.success && Array.isArray(data.plans) && data.plans.length > 0) {
                loadedPlans = data.plans;
                plansSubtitle.textContent = 'Showing ' + data.plans.length + ' popular packs for ' + (data.operator?.name || 'Provider');
                renderPlansGrid();
            } else {
                loadedPlans = [];
                plansEmpty.classList.remove('d-none');
                plansSubtitle.textContent = 'No preset packs listed. Please enter amount manually.';
            }

        } catch (err) {
            console.error('Failed to load DTH packs:', err);
            plansLoading.classList.add('d-none');
            plansEmpty.classList.remove('d-none');
            plansSubtitle.textContent = 'Could not load packs automatically. Please enter amount manually.';
        }
    }

    // Render DTH Packs Grid
    function renderPlansGrid() {
        plansGrid.innerHTML = '';

        const currentAmt = parseFloat(amountInput ? amountInput.value : 0);

        loadedPlans.forEach(function (plan) {
            const amt = parseFloat(plan.amount_numeric || plan.amount);
            const isSelected = (amt === currentAmt);

            const col = document.createElement('div');
            col.className = 'col-sm-6 col-xl-6';

            col.innerHTML = `
                <div class="card h-100 plan-card ${isSelected ? 'selected' : ''} p-3 position-relative" data-plan-id="${plan.id}" data-amount="${amt}" data-name="${escapeHtml(plan.plan_name)}" data-desc="${escapeHtml(plan.description)}">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <div class="plan-price fw-extrabold text-dark fs-4">₹${amt.toFixed(2)}</div>
                            <span class="badge bg-primary-subtle text-primary small py-1 px-2 rounded-pill">${escapeHtml(plan.validity)}</span>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-light text-dark border small fw-semibold">
                                <i class="bi bi-tv text-primary me-1"></i> SD & HD
                            </span>
                        </div>
                    </div>
                    <h6 class="fw-bold text-dark fs-6 mb-1">${escapeHtml(plan.plan_name)}</h6>
                    <p class="text-muted small mb-3 flex-grow-1" style="font-size: 0.8rem; line-height: 1.35;">
                        ${escapeHtml(plan.description || '')}
                    </p>
                    <div class="pt-2 border-top d-flex justify-content-between align-items-center">
                        <span class="text-muted small" style="font-size: 0.75rem;">TV Bouquet</span>
                        <button type="button" class="btn btn-sm ${isSelected ? 'btn-primary' : 'btn-outline-primary'} rounded-pill px-3 py-1 select-plan-btn">
                            ${isSelected ? '<i class="bi bi-check2"></i> Selected' : 'Choose Pack'}
                        </button>
                    </div>
                </div>
            `;

            // Card click selects pack
            col.querySelector('.plan-card').addEventListener('click', function () {
                selectPlan(plan);
            });

            plansGrid.appendChild(col);
        });

        plansGrid.classList.remove('d-none');
    }

    // Select Plan Action
    function selectPlan(plan) {
        const amt = parseFloat(plan.amount_numeric || plan.amount);
        amountInput.value = amt;
        planIdInput.value = plan.id;
        planNameInput.value = plan.plan_name;
        planDescInput.value = plan.description || '';

        // Visual selection updates
        document.querySelectorAll('.plan-card').forEach(el => {
            el.classList.remove('selected');
            const btn = el.querySelector('.select-plan-btn');
            if (btn) {
                btn.className = 'btn btn-sm btn-outline-primary rounded-pill px-3 py-1 select-plan-btn';
                btn.innerHTML = 'Choose Pack';
            }
        });

        const activeCard = document.querySelector(`.plan-card[data-plan-id="${plan.id}"]`);
        if (activeCard) {
            activeCard.classList.add('selected');
            const btn = activeCard.querySelector('.select-plan-btn');
            if (btn) {
                btn.className = 'btn btn-sm btn-primary rounded-pill px-3 py-1 select-plan-btn';
                btn.innerHTML = '<i class="bi bi-check2"></i> Selected';
            }
        }

        updateSummary();

        if (window.PayBen && typeof window.PayBen.showToast === 'function') {
            window.PayBen.showToast('info', 'Pack ₹' + amt + ' selected (' + plan.plan_name + ')', 'DTH Pack Selected', 2500);
        }
    }

    // Operator Select Change Listener
    if (operatorSelect) {
        operatorSelect.addEventListener('change', function () {
            const opId = this.value;
            fetchPlansForDTH(opId);
            updateSummary();

            // Dynamic format hint
            const selectedOpt = operatorSelect.options[operatorSelect.selectedIndex];
            const opCode = selectedOpt ? selectedOpt.getAttribute('data-code') : '';
            if (opCode === 'TATAPLAY') {
                idFormatHint.textContent = '10-digit Subscriber ID';
            } else if (opCode === 'AIRTEL_DTH') {
                idFormatHint.textContent = '10-digit Customer ID';
            } else if (opCode === 'DISHTV') {
                idFormatHint.textContent = '11-digit Viewing Card (VC) No.';
            } else if (opCode === 'SUNDIRECT') {
                idFormatHint.textContent = '11-digit Smart Card No.';
            } else {
                idFormatHint.textContent = '8 to 15 digits / characters';
            }
        });

        if (operatorSelect.value) {
            fetchPlansForDTH(operatorSelect.value);
        }
    }

    // Proceed to Recharge Button Submission
    if (btnProceed && dthForm) {
        btnProceed.addEventListener('click', function (e) {
            e.preventDefault();

            if (!operatorSelect || !operatorSelect.value) {
                if (window.PayBen && typeof window.PayBen.showToast === 'function') {
                    window.PayBen.showToast('warning', 'Please select your DTH service provider.', 'Provider Required');
                } else {
                    alert('Please select your DTH service provider.');
                }
                operatorSelect.focus();
                return;
            }

            const custId = customerInput ? customerInput.value.trim() : '';
            if (!custId || custId.length < 8) {
                if (window.PayBen && typeof window.PayBen.showToast === 'function') {
                    window.PayBen.showToast('error', 'Please enter a valid Subscriber / Customer ID (at least 8 characters).', 'Invalid ID');
                } else {
                    alert('Please enter a valid Subscriber ID.');
                }
                customerInput.focus();
                return;
            }

            const amount = parseFloat(amountInput ? amountInput.value : 0);
            if (!amount || amount < 10) {
                if (window.PayBen && typeof window.PayBen.showToast === 'function') {
                    window.PayBen.showToast('error', 'Please enter a valid recharge amount of at least ₹10.00.', 'Invalid Amount');
                } else {
                    alert('Please enter a valid amount.');
                }
                amountInput.focus();
                return;
            }

            if (amount > walletBalance) {
                if (window.PayBen && typeof window.PayBen.showToast === 'function') {
                    window.PayBen.showToast('danger', 'Insufficient wallet balance. Please add funds first.', 'Insufficient Funds');
                } else {
                    alert('Insufficient wallet balance.');
                }
                return;
            }

            // Confirm payment with user
            const opName = operatorSelect.options[operatorSelect.selectedIndex].text;
            const confirmMsg = `Confirm DTH Recharge of ${formatINR(amount)} for Subscriber #${custId} (${opName})?\n\nThis is a SIMULATED transaction. Funds will be debited from your PayBen wallet.`;

            if (confirm(confirmMsg)) {
                btnProceed.disabled = true;
                btnProceed.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Processing DTH Recharge...';
                dthForm.submit();
            }
        });
    }

    // Escape helper
    function escapeHtml(text) {
        if (!text) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // Initial summary computation
    updateSummary();
});
</script>

<?php
require_once dirname(__DIR__) . '/includes/footer.php';
?>
