<?php
// mobile-recharge page

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

// Fetch active mobile operators for dropdown
try {
    $opStmt = $pdo->prepare("
        SELECT id, name, code, logo, status 
        FROM operators 
        WHERE type = 'mobile' AND status = 'active' 
        ORDER BY name ASC
    ");
    $opStmt->execute();
    $mobileOperators = $opStmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed to load mobile operators: " . $e->getMessage());
    $mobileOperators = [];
    $errors['general'] = "Could not load operator list. Please refresh the page.";
}

// Retain form inputs on validation error
$inputMobile     = sanitize($_POST['mobile_number'] ?? ($_GET['mobile'] ?? ''));
$inputOperatorId = filter_var($_POST['operator_id'] ?? ($_GET['operator_id'] ?? ''), FILTER_VALIDATE_INT) ?: 0;
$inputAmount     = sanitize($_POST['amount'] ?? ($_GET['amount'] ?? ''));
$inputPlanId     = filter_var($_POST['plan_id'] ?? '', FILTER_VALIDATE_INT) ?: 0;
$inputPlanName   = sanitize($_POST['plan_name'] ?? '');
$inputPlanDesc   = sanitize($_POST['plan_description'] ?? '');

// -------------------------------------------------------------------------
// POST HANDLER: Process Simulated Mobile Recharge
// -------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Verify CSRF Token
    $token = $_POST['csrf_token'] ?? '';
    if (!validateCSRFToken($token)) {
        $errors['csrf'] = 'Session security token expired or invalid. Please reload the page.';
    }

    // 2. Server-Side Validation: Mobile Number (10 digits starting 6,7,8,9)
    $cleanMobile = preg_replace('/\D/', '', $inputMobile);
    if (empty($cleanMobile)) {
        $errors['mobile_number'] = 'Mobile number is required.';
    } elseif (!preg_match('/^[6-9]\d{9}$/', $cleanMobile)) {
        $errors['mobile_number'] = 'Please enter a valid 10-digit Indian mobile number starting with 6, 7, 8, or 9.';
    }

    // 3. Server-Side Validation: Operator
    $selectedOperator = null;
    if (empty($inputOperatorId)) {
        $errors['operator_id'] = 'Please choose your mobile network operator.';
    } else {
        foreach ($mobileOperators as $op) {
            if ((int)$op['id'] === (int)$inputOperatorId) {
                $selectedOperator = $op;
                break;
            }
        }
        if (!$selectedOperator) {
            $errors['operator_id'] = 'The selected operator is invalid or currently inactive.';
        }
    }

    // 4. Server-Side Validation: Recharge Amount
    $amountFloat = filter_var($inputAmount, FILTER_VALIDATE_FLOAT);
    if ($amountFloat === false || $amountFloat <= 0) {
        $errors['amount'] = 'Please enter a valid recharge amount greater than ₹0.';
    } elseif ($amountFloat < 10) {
        $errors['amount'] = 'Minimum recharge amount is ₹10.00.';
    } elseif ($amountFloat > 10000) {
        $errors['amount'] = 'Maximum recharge limit is ₹10,000.00.';
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
                $errors['wallet'] = "Insufficient wallet balance. You need " . formatCurrency($diff) . " more to complete this recharge.";
            } else {
                // Compute new balance
                $newBalance = round($walletBalance - $amountFloat, 2);

                // A. Deduct balance from user wallet
                $updateWalletStmt = $pdo->prepare("UPDATE wallets SET balance = ? WHERE id = ?");
                $updateWalletStmt->execute([$newBalance, $walletId]);

                // B. Generate unique reference ID
                $transactionId = generateTransactionID('MR');

                // C. Log double-entry debit in wallet_transactions
                $descriptionText = "Prepaid Mobile Recharge - {$selectedOperator['name']} ({$cleanMobile})";
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
                $planDetailsLog = !empty($inputPlanName) ? $inputPlanName : 'Custom Recharge Amount';
                if (!empty($inputPlanDesc)) {
                    $planDetailsLog .= ' - ' . $inputPlanDesc;
                }

                $txStmt = $pdo->prepare("
                    INSERT INTO transactions 
                        (user_id, transaction_id, type, mobile_or_consumer_number, operator_or_provider, amount, status, payment_method, description) 
                    VALUES 
                        (?, ?, 'mobile_recharge', ?, ?, ?, 'SUCCESS', 'Wallet', ?)
                ");
                $txStmt->execute([
                    $userId,
                    $transactionId,
                    $cleanMobile,
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
                    'mobile_number'   => $cleanMobile,
                    'operator_name'   => $selectedOperator['name'],
                    'operator_code'   => $selectedOperator['code'],
                    'amount'          => $amountFloat,
                    'plan_name'       => $inputPlanName ?: 'Prepaid Talktime / Top-up',
                    'plan_desc'       => $inputPlanDesc,
                    'payment_method'  => 'PayBen Digital Wallet',
                    'balance_before'  => $walletBalance,
                    'balance_after'   => $newBalance,
                    'created_at'      => date('d M Y, h:i A')
                ];

                setFlashMessage('success', "Mobile recharge of " . formatCurrency($amountFloat) . " completed successfully! [SIMULATED]");
            }

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Recharge execution error: " . $e->getMessage());
            $errors['general'] = "A database error occurred during payment processing. No funds were debited.";
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Unexpected recharge error: " . $e->getMessage());
            $errors['general'] = "An unexpected error occurred. Please try again.";
        }
    }
}

// -------------------------------------------------------------------------
// View Rendering Setup
// -------------------------------------------------------------------------
$pageTitle   = 'Mobile Recharge';
$currentPage = 'mobile-recharge';

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
                                <li class="breadcrumb-item active" aria-current="page">Mobile Recharge</li>
                            </ol>
                        </nav>
                        <h2 class="h3 fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                            <i class="bi bi-phone-fill text-primary"></i> Prepaid Mobile Recharge
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
                        <strong>Simulated Sandbox Environment:</strong> Transactions test real database wallet debits and audit entries without calling live telecom telco gateways. All successful orders display a clear <span class="badge bg-success-subtle text-success border border-success">SIMULATED</span> status badge.
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
                    <!-- TRANSACTION RECEIPT CARD (AFTER SUCCESSFUL RECHARGE)       -->
                    <!-- ========================================================= -->
                    <div class="row justify-content-center mb-5" id="receiptPrintArea">
                        <div class="col-lg-8 col-xl-7">
                            <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                                <!-- Top Success Banner -->
                                <div class="card-header bg-success text-white py-4 text-center border-0" style="background: linear-gradient(135deg, #059669 0%, #10b981 100%);">
                                    <div class="rounded-circle bg-white text-success d-inline-flex align-items-center justify-content-center mb-2 shadow" style="width: 64px; height: 64px;">
                                        <i class="bi bi-check-lg fs-1 fw-bold"></i>
                                    </div>
                                    <h4 class="fw-bold mb-1">Recharge Successful!</h4>
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
                                            <i class="bi bi-patch-check-fill me-1"></i> Completed Instantly
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
                                                <div class="text-muted small">Mobile Number</div>
                                                <div class="fw-bold text-dark fs-6">+91 <?= htmlspecialchars($receiptData['mobile_number'], ENT_QUOTES, 'UTF-8') ?></div>
                                            </div>

                                            <div class="col-sm-6">
                                                <div class="text-muted small">Operator</div>
                                                <div class="fw-semibold text-dark"><?= htmlspecialchars($receiptData['operator_name'], ENT_QUOTES, 'UTF-8') ?></div>
                                            </div>

                                            <div class="col-sm-6">
                                                <div class="text-muted small">Plan Description</div>
                                                <div class="fw-semibold text-dark"><?= htmlspecialchars($receiptData['plan_name'], ENT_QUOTES, 'UTF-8') ?></div>
                                            </div>

                                            <div class="col-sm-6">
                                                <div class="text-muted small">Payment Source</div>
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
                                        <a href="<?= APP_URL ?>/pages/mobile-recharge.php" class="btn btn-primary px-4 rounded-pill">
                                            <i class="bi bi-arrow-repeat me-1"></i> Make Another Recharge
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
                <!-- RECHARGE FORM & PLANS SECTION                                  -->
                <!-- ============================================================= -->
                <div class="row g-4 <?= $receiptData ? 'd-none' : '' ?>">
                    <!-- Left Column: Form & Live Summary -->
                    <div class="col-lg-5 col-xl-4">
                        <div class="card border-0 shadow-sm rounded-4 mb-4">
                            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                                <h5 class="card-header-title m-0 fs-6">
                                    <i class="bi bi-phone text-primary fs-5"></i> Enter Recharge Details
                                </h5>
                                <span class="badge bg-primary-subtle text-primary small">Instant</span>
                            </div>

                            <div class="card-body p-4">
                                <form action="<?= htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') ?>" method="POST" id="rechargeForm" novalidate>
                                    <!-- CSRF Protection -->
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCSRFToken(), ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="plan_id" id="plan_id" value="<?= htmlspecialchars((string)$inputPlanId, ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="plan_name" id="plan_name" value="<?= htmlspecialchars($inputPlanName, ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="plan_description" id="plan_description" value="<?= htmlspecialchars($inputPlanDesc, ENT_QUOTES, 'UTF-8') ?>">
                                    <div class="mb-3">
                                        <label for="mobile_number" class="form-label">
                                            Mobile Number <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light text-muted fw-bold">
                                                <i class="bi bi-telephone-fill me-1 small"></i> +91
                                            </span>
                                            <input 
                                                type="tel" 
                                                class="form-control form-control-lg fs-6 <?= isset($errors['mobile_number']) ? 'is-invalid' : '' ?>" 
                                                id="mobile_number" 
                                                name="mobile_number" 
                                                placeholder="Enter 10-digit number" 
                                                maxlength="10" 
                                                value="<?= htmlspecialchars($inputMobile, ENT_QUOTES, 'UTF-8') ?>" 
                                                required 
                                                autocomplete="tel-national"
                                                pattern="[6-9][0-9]{9}"
                                            >
                                        </div>
                                        <div class="form-text small text-muted">
                                            Valid 10-digit Indian mobile number starting with 6, 7, 8, or 9.
                                        </div>
                                        <?php if (isset($errors['mobile_number'])): ?>
                                            <div class="text-danger small mt-1"><?= htmlspecialchars($errors['mobile_number'], ENT_QUOTES, 'UTF-8') ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="mb-3">
                                        <label for="operator_id" class="form-label">
                                            Select Operator <span class="text-danger">*</span>
                                        </label>
                                        <select 
                                            class="form-select form-select-lg fs-6 <?= isset($errors['operator_id']) ? 'is-invalid' : '' ?>" 
                                            id="operator_id" 
                                            name="operator_id" 
                                            required
                                        >
                                            <option value="">-- Choose Telecom Operator --</option>
                                            <?php foreach ($mobileOperators as $op): ?>
                                                <option 
                                                    value="<?= (int)$op['id'] ?>" 
                                                    data-name="<?= htmlspecialchars($op['name'], ENT_QUOTES, 'UTF-8') ?>" 
                                                    data-code="<?= htmlspecialchars($op['code'], ENT_QUOTES, 'UTF-8') ?>"
                                                    <?= ((int)$inputOperatorId === (int)$op['id']) ? 'selected' : '' ?>
                                                >
                                                    <?= htmlspecialchars($op['name'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($op['code'], ENT_QUOTES, 'UTF-8') ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php if (isset($errors['operator_id'])): ?>
                                            <div class="text-danger small mt-1"><?= htmlspecialchars($errors['operator_id'], ENT_QUOTES, 'UTF-8') ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <label for="amount" class="form-label mb-0">
                                                Recharge Amount <span class="text-danger">*</span>
                                            </label>
                                            <a href="#plansContainer" class="small text-primary text-decoration-none fw-semibold" id="browsePlansLink">
                                                <i class="bi bi-grid-3x3-gap-fill me-1"></i> Browse Plans
                                            </a>
                                        </div>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light fw-bold text-dark">₹</span>
                                            <input 
                                                type="number" 
                                                step="1" 
                                                min="10" 
                                                max="10000" 
                                                class="form-control form-control-lg fs-6 fw-bold <?= isset($errors['amount']) ? 'is-invalid' : '' ?>" 
                                                id="amount" 
                                                name="amount" 
                                                placeholder="e.g. 249" 
                                                value="<?= htmlspecialchars($inputAmount, ENT_QUOTES, 'UTF-8') ?>" 
                                                required
                                            >
                                        </div>
                                        <?php if (isset($errors['amount'])): ?>
                                            <div class="text-danger small mt-1"><?= htmlspecialchars($errors['amount'], ENT_QUOTES, 'UTF-8') ?></div>
                                        <?php endif; ?>

                                        <!-- Quick Amount Chips -->
                                        <div class="mt-2 pt-1">
                                            <div class="text-muted small mb-1" style="font-size: 0.75rem;">Popular Amounts:</div>
                                            <div class="d-flex flex-wrap gap-1">
                                                <?php foreach ([149, 199, 249, 299, 399, 499, 699, 999] as $qAmt): ?>
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
                                    <i class="bi bi-receipt text-primary fs-5"></i> Recharge Summary
                                </h5>
                            </div>
                            <div class="card-body p-4">
                                <ul class="list-group list-group-flush small mb-3">
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                        <span class="text-muted">Mobile Number</span>
                                        <span class="fw-bold text-dark" id="summaryMobile">--</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                        <span class="text-muted">Operator</span>
                                        <span class="fw-bold text-dark" id="summaryOperator">--</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                        <span class="text-muted">Selected Plan</span>
                                        <span class="fw-semibold text-primary text-end" id="summaryPlanName">Custom / None</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                        <span class="text-muted">Wallet Balance</span>
                                        <span class="fw-bold text-dark" id="summaryWalletBalance"><?= formatCurrency($currentUser['wallet_balance'] ?? 0.00) ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 bg-light rounded-2 px-2 mt-2">
                                        <span class="fw-bold text-dark">Total Payable</span>
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
                                    <i class="bi bi-lightning-charge-fill"></i>
                                    <span>Proceed to Recharge</span>
                                </button>
                                <div class="text-center mt-2">
                                    <small class="text-muted" style="font-size: 0.72rem;">
                                        <i class="bi bi-shield-check text-success"></i> Instant simulated fulfillment with full audit ledger
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Interactive Plans Browser -->
                    <div class="col-lg-7 col-xl-8">
                        <div class="card border-0 shadow-sm rounded-4" id="plansContainer">
                            <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-2">
                                <div>
                                    <h5 class="card-header-title m-0 fs-6">
                                        <i class="bi bi-grid text-primary fs-5"></i> Browse Recharge Plans
                                    </h5>
                                    <small class="text-muted" id="plansSubtitle">Select an operator on the left to see live plans</small>
                                </div>

                                <!-- Price Slab Filter Chips -->
                                <div class="d-flex align-items-center gap-1 flex-wrap" id="planFilterGroup" style="display: none !important;">
                                    <button type="button" class="btn btn-sm btn-outline-primary active rounded-pill filter-btn py-1 px-2" data-filter="all">All Plans</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill filter-btn py-1 px-2" data-filter="budget">&lt; ₹250</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill filter-btn py-1 px-2" data-filter="popular">₹250 - ₹500</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill filter-btn py-1 px-2" data-filter="unlimited">&gt; ₹500</button>
                                </div>
                            </div>

                            <div class="card-body p-4">
                                <!-- State 1: Placeholder before selecting operator -->
                                <div id="plansPlaceholder" class="text-center py-5">
                                    <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center text-muted mb-3" style="width: 80px; height: 80px;">
                                        <i class="bi bi-phone fs-1 text-primary opacity-50"></i>
                                    </div>
                                    <h6 class="fw-bold text-dark mb-1">Choose an Operator to View Plans</h6>
                                    <p class="text-muted small mx-auto" style="max-width: 380px;">
                                        Select your telecom network from the dropdown list to explore full 4G/5G daily data allowances, long validity packs, and combo plans.
                                    </p>
                                </div>

                                <!-- State 2: Spinner Loader during AJAX Fetch -->
                                <div id="plansLoading" class="text-center py-5 d-none">
                                    <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                                        <span class="visually-hidden">Loading plans...</span>
                                    </div>
                                    <h6 class="fw-bold text-dark">Fetching Operator Plans...</h6>
                                    <p class="text-muted small">Retrieving current active tariff packs via PayBen API</p>
                                </div>

                                <!-- State 3: Empty Plans Alert -->
                                <div id="plansEmpty" class="text-center py-5 d-none">
                                    <div class="rounded-circle bg-warning-subtle text-warning d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                                        <i class="bi bi-exclamation-circle fs-2"></i>
                                    </div>
                                    <h6 class="fw-bold text-dark">No Plans Found</h6>
                                    <p class="text-muted small">No active prepaid recharge packs are listed for this operator yet. You can manually type your desired amount in the form.</p>
                                </div>

                                <!-- State 4: Rendered Plans Grid -->
                                <div id="plansGrid" class="row g-3 d-none">
                                    <!-- Dynamic Plan Cards Injected via JavaScript -->
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
<!-- CLIENT-SIDE SCRIPT: Dynamic AJAX Plans Fetching & Interactive Summary      -->
<!-- ========================================================================= -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const mobileInput      = document.getElementById('mobile_number');
    const operatorSelect   = document.getElementById('operator_id');
    const amountInput      = document.getElementById('amount');
    const planIdInput      = document.getElementById('plan_id');
    const planNameInput    = document.getElementById('plan_name');
    const planDescInput    = document.getElementById('plan_description');
    const rechargeForm     = document.getElementById('rechargeForm');
    const btnProceed       = document.getElementById('btnProceedRecharge');

    const summaryMobile    = document.getElementById('summaryMobile');
    const summaryOperator  = document.getElementById('summaryOperator');
    const summaryPlanName  = document.getElementById('summaryPlanName');
    const summaryPayable   = document.getElementById('summaryPayable');
    const balanceAlertBox  = document.getElementById('balanceAlertBox');
    const balanceAlertMsg  = document.getElementById('balanceAlertMsg');

    const plansPlaceholder = document.getElementById('plansPlaceholder');
    const plansLoading     = document.getElementById('plansLoading');
    const plansEmpty       = document.getElementById('plansEmpty');
    const plansGrid        = document.getElementById('plansGrid');
    const planFilterGroup  = document.getElementById('planFilterGroup');
    const plansSubtitle    = document.getElementById('plansSubtitle');

    const walletBalance    = parseFloat('<?= (float)($currentUser['wallet_balance'] ?? 0.00) ?>');
    let loadedPlans        = [];
    let activeFilter       = 'all';

    // ---------------------------------------------------------------------
    // Helper: Format Currency (₹ XXX.XX)
    // ---------------------------------------------------------------------
    function formatINR(amount) {
        const num = parseFloat(amount) || 0;
        return '₹' + num.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    // ---------------------------------------------------------------------
    // Update Live Summary Box
    // ---------------------------------------------------------------------
    function updateSummary() {
        const mobile = mobileInput ? mobileInput.value.trim() : '';
        summaryMobile.textContent = mobile ? ('+91 ' + mobile) : '--';

        const selectedOp = operatorSelect ? operatorSelect.options[operatorSelect.selectedIndex] : null;
        if (selectedOp && selectedOp.value) {
            summaryOperator.textContent = selectedOp.getAttribute('data-name') || selectedOp.text;
        } else {
            summaryOperator.textContent = '--';
        }

        const planName = planNameInput ? planNameInput.value.trim() : '';
        summaryPlanName.textContent = planName || 'Custom / None';

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

    // Attach real-time input listeners
    if (mobileInput) mobileInput.addEventListener('input', updateSummary);
    if (amountInput) amountInput.addEventListener('input', function () {
        // If user manually modifies amount, clear plan ID unless matching
        updateSummary();
    });

    // ---------------------------------------------------------------------
    // Fetch Plans via AJAX when Operator changes
    // ---------------------------------------------------------------------
    async function fetchPlansForOperator(operatorId) {
        if (!operatorId) {
            plansPlaceholder.classList.remove('d-none');
            plansLoading.classList.add('d-none');
            plansEmpty.classList.add('d-none');
            plansGrid.classList.add('d-none');
            planFilterGroup.style.setProperty('display', 'none', 'important');
            plansSubtitle.textContent = 'Select an operator on the left to see live plans';
            return;
        }

        // Show spinner
        plansPlaceholder.classList.add('d-none');
        plansLoading.classList.remove('d-none');
        plansEmpty.classList.add('d-none');
        plansGrid.classList.add('d-none');
        planFilterGroup.style.setProperty('display', 'none', 'important');
        plansSubtitle.textContent = 'Fetching current offers...';

        try {
            const endpoint = '<?= APP_URL ?>/api/recharge/plans.php?operator_id=' + encodeURIComponent(operatorId);
            const response = await fetch(endpoint, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error('API request failed with status ' + response.status);
            }

            const data = await response.json();
            plansLoading.classList.add('d-none');

            if (data.success && Array.isArray(data.plans) && data.plans.length > 0) {
                loadedPlans = data.plans;
                plansSubtitle.textContent = 'Showing ' + data.plans.length + ' plans for ' + (data.operator?.name || 'Operator');
                planFilterGroup.style.removeProperty('display');
                renderPlansGrid();
            } else {
                loadedPlans = [];
                plansEmpty.classList.remove('d-none');
                plansSubtitle.textContent = 'No preset plans found for this provider';
            }

        } catch (err) {
            console.error('Failed to load plans:', err);
            plansLoading.classList.add('d-none');
            plansEmpty.classList.remove('d-none');
            plansSubtitle.textContent = 'Could not load plans automatically. Please enter amount manually.';
        }
    }

    // ---------------------------------------------------------------------
    // Render Plans Grid with Price Slab Filters
    // ---------------------------------------------------------------------
    function renderPlansGrid() {
        plansGrid.innerHTML = '';

        const filtered = loadedPlans.filter(function (p) {
            const amt = parseFloat(p.amount_numeric || p.amount);
            if (activeFilter === 'budget') return amt < 250;
            if (activeFilter === 'popular') return amt >= 250 && amt <= 500;
            if (activeFilter === 'unlimited') return amt > 500;
            return true;
        });

        if (filtered.length === 0) {
            plansGrid.innerHTML = `
                <div class="col-12 text-center py-4 text-muted small">
                    <i class="bi bi-funnel fs-4 d-block mb-1"></i> No plans found in this price category.
                </div>
            `;
            plansGrid.classList.remove('d-none');
            return;
        }

        const currentAmt = parseFloat(amountInput ? amountInput.value : 0);

        filtered.forEach(function (plan) {
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
                                <i class="bi bi-wifi text-primary me-1"></i> ${escapeHtml(plan.data_limit || 'N/A')}
                            </span>
                        </div>
                    </div>
                    <h6 class="fw-bold text-dark fs-6 mb-1">${escapeHtml(plan.plan_name)}</h6>
                    <p class="text-muted small mb-3 flex-grow-1" style="font-size: 0.8rem; line-height: 1.35;">
                        ${escapeHtml(plan.description || '')}
                    </p>
                    <div class="pt-2 border-top d-flex justify-content-between align-items-center">
                        <span class="text-muted small" style="font-size: 0.75rem;">Prepaid Pack</span>
                        <button type="button" class="btn btn-sm ${isSelected ? 'btn-primary' : 'btn-outline-primary'} rounded-pill px-3 py-1 select-plan-btn">
                            ${isSelected ? '<i class="bi bi-check2"></i> Selected' : 'Select Plan'}
                        </button>
                    </div>
                </div>
            `;

            // Clicking card selects plan
            col.querySelector('.plan-card').addEventListener('click', function () {
                selectPlan(plan);
            });

            plansGrid.appendChild(col);
        });

        plansGrid.classList.remove('d-none');
    }

    // ---------------------------------------------------------------------
    // Select Plan Action
    // ---------------------------------------------------------------------
    function selectPlan(plan) {
        const amt = parseFloat(plan.amount_numeric || plan.amount);
        amountInput.value = amt;
        planIdInput.value = plan.id;
        planNameInput.value = plan.plan_name;
        planDescInput.value = plan.description || '';

        // Update card visual selections
        document.querySelectorAll('.plan-card').forEach(el => {
            el.classList.remove('selected');
            const btn = el.querySelector('.select-plan-btn');
            if (btn) {
                btn.className = 'btn btn-sm btn-outline-primary rounded-pill px-3 py-1 select-plan-btn';
                btn.innerHTML = 'Select Plan';
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

        // Mobile UX: brief toast feedback
        if (window.PayBen && typeof window.PayBen.showToast === 'function') {
            window.PayBen.showToast('info', 'Plan ₹' + amt + ' selected (' + plan.plan_name + ')', 'Plan Chosen', 2500);
        }
    }

    // ---------------------------------------------------------------------
    // Filter Chips Event Listener
    // ---------------------------------------------------------------------
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.filter-btn').forEach(b => {
                b.classList.remove('btn-primary', 'active');
                b.classList.add('btn-outline-secondary');
            });
            this.classList.remove('btn-outline-secondary');
            this.classList.add('btn-primary', 'active');
            activeFilter = this.getAttribute('data-filter') || 'all';
            renderPlansGrid();
        });
    });

    // ---------------------------------------------------------------------
    // Operator Select Change Listener
    // ---------------------------------------------------------------------
    if (operatorSelect) {
        operatorSelect.addEventListener('change', function () {
            const opId = this.value;
            fetchPlansForOperator(opId);
            updateSummary();
        });

        // Trigger on load if pre-selected
        if (operatorSelect.value) {
            fetchPlansForOperator(operatorSelect.value);
        }
    }

    // ---------------------------------------------------------------------
    // Proceed to Recharge Button Submission
    // ---------------------------------------------------------------------
    if (btnProceed && rechargeForm) {
        btnProceed.addEventListener('click', function (e) {
            e.preventDefault();

            // Client-side quick checks
            const mobile = mobileInput ? mobileInput.value.trim() : '';
            if (!mobile || !/^[6-9]\d{9}$/.test(mobile)) {
                if (window.PayBen && typeof window.PayBen.showToast === 'function') {
                    window.PayBen.showToast('error', 'Please enter a valid 10-digit mobile number starting with 6-9.', 'Invalid Number');
                } else {
                    alert('Please enter a valid 10-digit mobile number.');
                }
                mobileInput.focus();
                return;
            }

            if (!operatorSelect || !operatorSelect.value) {
                if (window.PayBen && typeof window.PayBen.showToast === 'function') {
                    window.PayBen.showToast('warning', 'Please select your network operator.', 'Operator Required');
                } else {
                    alert('Please select your network operator.');
                }
                operatorSelect.focus();
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
            const confirmMsg = `Confirm Mobile Recharge of ${formatINR(amount)} for +91 ${mobile} (${opName})?\n\nThis is a SIMULATED transaction. Funds will be debited from your PayBen wallet.`;

            if (confirm(confirmMsg)) {
                btnProceed.disabled = true;
                btnProceed.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Processing Recharge...';
                rechargeForm.submit();
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
