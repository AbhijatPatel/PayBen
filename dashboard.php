<?php
// Dashboard

$pageTitle = 'Dashboard';
$currentPage = 'dashboard';

require_once 'includes/auth.php';
require_once 'config/config.php';
require_once 'config/database.php';

requireLogin();

$currentUser = getCurrentUser();
$pdo = getDBConnection();

$userId = (int)($currentUser['id'] ?? 0);
$userName = $currentUser['full_name'] ?? $currentUser['name'] ?? 'User';
$userMobile = $currentUser['mobile'] ?? '9876543210';
$userUpi = $userMobile . '@payben';

$walletBalance = 0.00;
$totalTransactions = 0;
$successfulTransactions = 0;
$recentTransactions = [];

// Fetch user data & recent transactions
if ($pdo && $userId > 0) {
    try {
        $stmtWallet = $pdo->prepare("SELECT balance FROM wallets WHERE user_id = ? LIMIT 1");
        $stmtWallet->execute([$userId]);
        $walletRow = $stmtWallet->fetch();
        $walletBalance = $walletRow ? (float)$walletRow['balance'] : (float)($currentUser['wallet_balance'] ?? 0);

        $stmtStats = $pdo->prepare("
            SELECT 
                COUNT(*) AS total_count,
                SUM(CASE WHEN status = 'SUCCESS' THEN 1 ELSE 0 END) AS success_count
            FROM transactions 
            WHERE user_id = ?
        ");
        $stmtStats->execute([$userId]);
        $stats = $stmtStats->fetch();
        if ($stats) {
            $totalTransactions = (int)($stats['total_count'] ?? 0);
            $successfulTransactions = (int)($stats['success_count'] ?? 0);
        }

        $stmtRecent = $pdo->prepare("
            SELECT id, transaction_id, type, mobile_or_consumer_number, operator_or_provider, 
                   amount, status, payment_method, description, created_at 
            FROM transactions 
            WHERE user_id = ? 
            ORDER BY created_at DESC, id DESC 
            LIMIT 6
        ");
        $stmtRecent->execute([$userId]);
        $recentTransactions = $stmtRecent->fetchAll();

        // Fetch other users on network for "People" carousel
        $stmtPeople = $pdo->prepare("SELECT id, full_name, mobile, email FROM users WHERE id != ? LIMIT 5");
        $stmtPeople->execute([$userId]);
        $people = $stmtPeople->fetchAll();
    } catch (PDOException $e) {
        error_log("GPay Dashboard error: " . $e->getMessage());
    }
}

include 'includes/header.php';
include 'includes/navbar.php';
?>

<div class="d-flex">
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content flex-grow-1" style="background-color: #f8fafd;">
        <main class="content-body py-4 px-3 px-md-4">
            <div class="container-fluid" style="max-width: 1040px;">
                <div class="gpay-card p-4 p-md-5 mb-4 position-relative overflow-hidden" 
                     style="background: linear-gradient(135deg, #ffffff 0%, #f1f5fd 100%); border-top: 4px solid #1a73e8;">
                    
                    <div class="row align-items-center g-4">
                        <div class="col-md-7">
                            <div class="d-flex align-items-center gap-3 mb-2">
                                <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold shadow-sm" 
                                     style="width: 58px; height: 58px; background: linear-gradient(135deg, #1a73e8 0%, #4285f4 100%); font-size: 1.5rem;">
                                    <?= strtoupper(substr($userName, 0, 1)) ?>
                                </div>
                                <div>
                                    <h2 class="fs-4 fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                                        <?= sanitize($userName) ?>
                                        <i class="bi bi-patch-check-fill gpay-blue fs-5" title="Verified PayBen User"></i>
                                    </h2>
                                    <div class="d-flex align-items-center gap-2 mt-1">
                                        <span class="gpay-upi-badge">
                                            <i class="bi bi-upc-scan"></i> <?= $userUpi ?>
                                        </span>
                                        <button class="btn btn-sm btn-link text-decoration-none p-0 text-muted" onclick="copyUpiId('<?= $userUpi ?>')" title="Copy UPI ID">
                                            <i class="bi bi-copy"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        
                        <div class="col-md-5 text-md-end">
                            <div class="p-3 rounded-4 bg-white border d-inline-block text-start shadow-sm" style="min-width: 240px; border-color: #dadce0 !important;">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="text-muted small fw-semibold">PayBen UPI Balance</span>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill" style="font-size: 0.7rem;">Active</span>
                                </div>
                                <div class="fs-3 fw-bold text-dark d-flex align-items-center gap-2" id="walletDisplay">
                                    <?= formatCurrency($walletBalance) ?>
                                </div>
                                <div class="d-flex gap-2 mt-2">
                                    <button class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1 fw-semibold d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#addMoneyModal">
                                        <i class="bi bi-plus-lg"></i> Add Money
                                    </button>
                                    <button class="btn btn-sm btn-light rounded-pill px-3 py-1 fw-semibold text-secondary" data-bs-toggle="modal" data-bs-target="#qrModal">
                                        <i class="bi bi-qr-code"></i> My QR
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="gpay-card p-4 mb-4">
                    <h6 class="text-muted fw-bold text-uppercase mb-3 small" style="letter-spacing: 0.5px;">Quick Actions</h6>
                    <div class="row text-center g-3">
                        <div class="col-4 col-sm-2">
                            <button class="gpay-circle-btn gpay-btn-blue mb-2" data-bs-toggle="modal" data-bs-target="#qrModal">
                                <i class="bi bi-qr-code-scan"></i>
                            </button>
                            <div class="small fw-semibold text-dark">Scan any QR</div>
                        </div>
                        <div class="col-4 col-sm-2">
                            <button class="gpay-circle-btn gpay-btn-green mb-2" id="upiPayBtn" data-bs-toggle="modal" data-bs-target="#sendMoneyModal">
                                <i class="bi bi-person-check-fill"></i>
                            </button>
                            <div class="small fw-semibold text-dark">Pay contacts</div>
                        </div>
                        <div class="col-4 col-sm-2">
                            <button class="gpay-circle-btn gpay-btn-yellow mb-2" onclick="openTransferModal('', '')">
                                <i class="bi bi-phone"></i>
                            </button>
                            <div class="small fw-semibold text-dark">Pay number</div>
                        </div>
                        <div class="col-4 col-sm-2">
                            <button class="gpay-circle-btn gpay-btn-purple mb-2" data-bs-toggle="modal" data-bs-target="#addMoneyModal">
                                <i class="bi bi-bank"></i>
                            </button>
                            <div class="small fw-semibold text-dark">Bank transfer</div>
                        </div>
                        <div class="col-4 col-sm-2">
                            <button class="gpay-circle-btn gpay-btn-red mb-2" onclick="openTransferModal('', '@payben')">
                                <i class="bi bi-at"></i>
                            </button>
                            <div class="small fw-semibold text-dark">Pay UPI ID</div>
                        </div>
                        <div class="col-4 col-sm-2">
                            <a href="<?= APP_URL ?>/pages/mobile-recharge.php" class="gpay-circle-btn gpay-btn-teal mb-2">
                                <i class="bi bi-broadcast"></i>
                            </a>
                            <div class="small fw-semibold text-dark">Recharge</div>
                        </div>
                    </div>
                </div>
                <?php if (!empty($people)): ?>
                <div class="gpay-card p-4 mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="text-muted fw-bold text-uppercase mb-0 small" style="letter-spacing: 0.5px;">People</h6>
                        <span class="text-muted small">Pay instantly via UPI</span>
                    </div>
                    <div class="d-flex align-items-center gap-4 overflow-auto pb-2">
                        <?php 
                        $avatarColors = ['#1a73e8', '#34a853', '#ea4335', '#f9ab00', '#8430ce'];
                        foreach ($people as $idx => $p): 
                            $color = $avatarColors[$idx % count($avatarColors)];
                        ?>
                        <div class="text-center flex-shrink-0" style="cursor: pointer;" onclick="openTransferModal('<?= sanitize($p['mobile']) ?>', '<?= sanitize($p['full_name']) ?>')">
                            <div class="gpay-avatar mx-auto mb-1 shadow-sm" style="background-color: <?= $color ?>;">
                                <?= strtoupper(substr($p['full_name'], 0, 1)) ?>
                            </div>
                            <div class="small fw-semibold text-dark text-truncate" style="max-width: 80px;"><?= sanitize(explode(' ', $p['full_name'])[0]) ?></div>
                        </div>
                        <?php endforeach; ?>
                        
                        <!-- Add contact button -->
                        <div class="text-center flex-shrink-0" style="cursor: pointer;" data-bs-toggle="modal" data-bs-target="#sendMoneyModal">
                            <div class="gpay-avatar mx-auto mb-1 bg-light border text-primary">
                                <i class="bi bi-plus-lg fs-5"></i>
                            </div>
                            <div class="small fw-semibold text-muted">New pay</div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                <div class="gpay-card p-4 mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="text-muted fw-bold text-uppercase mb-0 small" style="letter-spacing: 0.5px;">Bills &amp; Recharges</h6>
                        <span class="badge bg-primary-subtle text-primary rounded-pill">BBPS Ready</span>
                    </div>

                    <div class="row g-3 text-center">
                        <!-- Mobile Recharge -->
                        <div class="col-6 col-sm-3 col-md-2">
                            <a href="<?= APP_URL ?>/pages/mobile-recharge.php" class="text-decoration-none">
                                <div class="gpay-circle-btn gpay-btn-blue mb-2">
                                    <i class="bi bi-phone-fill"></i>
                                </div>
                                <div class="small fw-semibold text-dark">Mobile</div>
                            </a>
                        </div>

                        <!-- DTH -->
                        <div class="col-6 col-sm-3 col-md-2">
                            <a href="<?= APP_URL ?>/pages/dth-recharge.php" class="text-decoration-none">
                                <div class="gpay-circle-btn gpay-btn-purple mb-2">
                                    <i class="bi bi-tv-fill"></i>
                                </div>
                                <div class="small fw-semibold text-dark">DTH</div>
                            </a>
                        </div>

                        <!-- Electricity -->
                        <div class="col-6 col-sm-3 col-md-2">
                            <a href="<?= APP_URL ?>/pages/electricity.php" class="text-decoration-none">
                                <div class="gpay-circle-btn gpay-btn-yellow mb-2">
                                    <i class="bi bi-lightning-charge-fill"></i>
                                </div>
                                <div class="small fw-semibold text-dark">Electricity</div>
                            </a>
                        </div>

                        <!-- Water -->
                        <div class="col-6 col-sm-3 col-md-2">
                            <a href="<?= APP_URL ?>/pages/water.php" class="text-decoration-none">
                                <div class="gpay-circle-btn gpay-btn-blue mb-2" style="background:#e0f2fe; color:#0284c7;">
                                    <i class="bi bi-droplet-fill"></i>
                                </div>
                                <div class="small fw-semibold text-dark">Water</div>
                            </a>
                        </div>

                        <!-- Gas -->
                        <div class="col-6 col-sm-3 col-md-2">
                            <a href="<?= APP_URL ?>/pages/gas.php" class="text-decoration-none">
                                <div class="gpay-circle-btn gpay-btn-red mb-2" style="background:#fef2f2; color:#dc2626;">
                                    <i class="bi bi-fire"></i>
                                </div>
                                <div class="small fw-semibold text-dark">Piped Gas</div>
                            </a>
                        </div>

                        <!-- Fastag / More -->
                        <div class="col-6 col-sm-3 col-md-2">
                            <a href="<?= APP_URL ?>/pages/transactions.php" class="text-decoration-none">
                                <div class="gpay-circle-btn gpay-btn-green mb-2">
                                    <i class="bi bi-grid-fill"></i>
                                </div>
                                <div class="small fw-semibold text-dark">View all</div>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="gpay-card gpay-scratch-box p-4 h-100 d-flex align-items-center justify-content-between" 
                             onclick="openScratchCardModal()">
                            <div>
                                <span class="badge bg-white text-dark rounded-pill fw-bold mb-2 shadow-sm">
                                    <i class="bi bi-stars text-warning"></i> PayBen Rewards
                                </span>
                                <h4 class="fw-bold mb-1">You have a Scratch Card! 🎁</h4>
                                <p class="mb-0 small text-white-50">Tap to scratch and win real wallet cashback instantly!</p>
                            </div>
                            <div class="fs-1 text-white p-2">
                                <i class="bi bi-gift-fill"></i>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="gpay-card p-4 h-100 d-flex flex-column justify-content-between" style="border-left: 4px solid #34a853;">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <span class="text-muted small fw-semibold text-uppercase">Payment Activity</span>
                                    <h4 class="fw-bold mb-0 mt-1"><?= number_format($totalTransactions) ?> Total Payments</h4>
                                </div>
                                <span class="gpay-circle-btn gpay-btn-green" style="width: 44px; height: 44px; font-size: 1.1rem;">
                                    <i class="bi bi-check-lg"></i>
                                </span>
                            </div>
                            <div class="d-flex gap-2 mt-3">
                                <a href="<?= APP_URL ?>/pages/transactions.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                                    <i class="bi bi-clock-history"></i> See history
                                </a>
                                <a href="<?= APP_URL ?>/pages/wallet.php" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                    <i class="bi bi-wallet2"></i> Check balance
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="gpay-card p-4 mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="text-muted fw-bold text-uppercase mb-0 small" style="letter-spacing: 0.5px;">Recent Activity</h6>
                        <a href="<?= APP_URL ?>/pages/transactions.php" class="text-decoration-none gpay-blue fw-semibold small">
                            See all <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>

                    <?php if (empty($recentTransactions)): ?>
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>
                            <p class="mb-2">No transactions yet.</p>
                            <a href="<?= APP_URL ?>/pages/mobile-recharge.php" class="btn btn-sm btn-gpay">Make First Payment</a>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($recentTransactions as $txn): 
                                $isCredit = str_contains(strtolower($txn['type']), 'topup') || str_contains(strtolower($txn['description']), 'received');
                                $iconBg = $isCredit ? '#e6f4ea' : '#e8f0fe';
                                $iconColor = $isCredit ? '#137333' : '#1a73e8';
                                $icon = match($txn['type']) {
                                    'mobile_recharge' => 'bi-phone',
                                    'dth_recharge' => 'bi-tv',
                                    'electricity' => 'bi-lightning-charge',
                                    'water' => 'bi-droplet',
                                    'gas' => 'bi-fire',
                                    default => 'bi-arrow-left-right'
                                };
                            ?>
                            <div class="list-group-item px-0 py-3 d-flex align-items-center justify-content-between border-0 border-bottom">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center" 
                                         style="width: 44px; height: 44px; background: <?= $iconBg ?>; color: <?= $iconColor ?>; font-size: 1.2rem;">
                                        <i class="bi <?= $icon ?>"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark mb-0"><?= sanitize($txn['operator_or_provider'] ?: $txn['description']) ?></div>
                                        <div class="text-muted small">
                                            <?= date('d M Y, h:i A', strtotime($txn['created_at'])) ?> • <span class="badge bg-light text-secondary border"><?= $txn['status'] ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="fw-bold <?= $isCredit ? 'text-success' : 'text-dark' ?>" style="font-size: 1.05rem;">
                                        <?= $isCredit ? '+' : '-' ?><?= formatCurrency($txn['amount']) ?>
                                    </div>
                                    <span class="text-muted" style="font-size: 0.72rem; font-family: monospace;">
                                        <?= $txn['transaction_id'] ?>
                                    </span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </main>
    </div>
</div>

<!-- ========================================================================= -->

<!-- ========================================================================= -->

<!-- Modal 1: Send Money (UPI Transfer) -->
<div class="modal fade" id="sendMoneyModal" tabindex="-1" aria-labelledby="sendMoneyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header border-0 pb-0" style="background: #f8fafd;">
                <h5 class="modal-title fw-bold d-flex align-items-center gap-2" id="sendMoneyModalLabel">
                    <i class="bi bi-send-fill text-primary"></i> Pay with PayBen UPI
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 pt-2">
                <form id="upiTransferForm">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase">Pay To (Phone or UPI ID)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-person"></i></span>
                            <input type="text" class="form-control border-start-0" id="transferRecipient" placeholder="e.g. 9812345678 or priya@payben" required>
                        </div>
                        <div class="form-text small" id="recipientHelper">Enter registered 10-digit mobile number or UPI ID.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase">Amount</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light fw-bold">₹</span>
                            <input type="number" class="form-control form-control-lg fw-bold" id="transferAmount" placeholder="0.00" min="1" max="100000" step="any" required>
                        </div>
                        <div class="d-flex gap-2 mt-2">
                            <button type="button" class="btn btn-sm btn-light border rounded-pill" onclick="document.getElementById('transferAmount').value=100">₹100</button>
                            <button type="button" class="btn btn-sm btn-light border rounded-pill" onclick="document.getElementById('transferAmount').value=500">₹500</button>
                            <button type="button" class="btn btn-sm btn-light border rounded-pill" onclick="document.getElementById('transferAmount').value=1000">₹1,000</button>
                            <button type="button" class="btn btn-sm btn-light border rounded-pill" onclick="document.getElementById('transferAmount').value=2000">₹2,000</button>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted text-uppercase">Add a note (optional)</label>
                        <input type="text" class="form-control" id="transferNote" placeholder="What's this for? (e.g. Dinner, Rent)">
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3 p-3 bg-light rounded-3 border">
                        <span class="small text-muted">Paying from:</span>
                        <span class="small fw-bold text-dark">PayBen Wallet (<?= formatCurrency($walletBalance) ?>)</span>
                    </div>

                    <button type="submit" class="btn btn-gpay w-100 py-2 fs-6 fw-bold" id="submitTransferBtn">
                        <i class="bi bi-send-fill me-1"></i> Pay Securely
                    </button>
                </form>

                
                <div id="transferResultScreen" class="text-center py-3" style="display: none;">
                    <div class="gpay-success-check mb-3">
                        <i class="bi bi-check-lg"></i>
                    </div>
                    <h3 class="fw-bold mb-1" id="resAmount">₹0.00</h3>
                    <div class="text-muted mb-3" id="resRecipient">Paid to Contact</div>
                    <div class="card p-3 bg-light border-0 rounded-3 text-start small mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">UPI Ref ID:</span>
                            <span class="fw-bold" id="resUpiRef">UPI123456</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Transaction ID:</span>
                            <span class="fw-bold font-monospace" id="resTxnId">PB123</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Date &amp; Time:</span>
                            <span id="resTimestamp"><?= date('d M Y, h:i A') ?></span>
                        </div>
                    </div>
                    <button type="button" class="btn btn-gpay w-100 rounded-pill" onclick="location.reload()">Done</button>
                </div>

            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="qrModal" tabindex="-1" aria-labelledby="qrModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header border-0 pb-0">
                <ul class="nav nav-pills w-100" id="qrTab" role="tablist">
                    <li class="nav-item flex-fill text-center" role="presentation">
                        <button class="nav-link active rounded-pill w-100 fw-semibold" id="myqr-tab" data-bs-toggle="pill" data-bs-target="#myqr" type="button" role="tab">My QR Code</button>
                    </li>
                    <li class="nav-item flex-fill text-center" role="presentation">
                        <button class="nav-link rounded-pill w-100 fw-semibold" id="scanqr-tab" data-bs-toggle="pill" data-bs-target="#scanqr" type="button" role="tab">Scan &amp; Pay</button>
                    </li>
                </ul>
                <button type="button" class="btn-close ms-2" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <div class="tab-content" id="qrTabContent">
                    <!-- Tab 1: My QR Code -->
                    <div class="tab-pane fade show active" id="myqr" role="tabpanel">
                        <h5 class="fw-bold text-dark mt-2 mb-1"><?= sanitize($userName) ?></h5>
                        <p class="text-muted small mb-3">Scan with any UPI app</p>
                        
                        <!-- Real Dynamic SVG QR Code Representation -->
                        <div class="p-3 bg-white border rounded-4 shadow-sm d-inline-block mb-3" style="border-color: #dadce0 !important;">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=upi://pay?pa=<?= urlencode($userUpi) ?>&pn=<?= urlencode($userName) ?>&cu=INR" 
                                 alt="UPI QR Code" class="img-fluid rounded" style="width: 190px; height: 190px;">
                        </div>

                        <div class="gpay-upi-badge mb-2">
                            <i class="bi bi-upc"></i> <?= $userUpi ?>
                        </div>
                        <div class="text-muted small">Linked to PayBen Digital Wallet</div>
                    </div>

                    <!-- Tab 2: Scan QR / Pay UPI -->
                    <div class="tab-pane fade" id="scanqr" role="tabpanel">
                        <div class="p-4 bg-light rounded-4 border border-dashed mb-3">
                            <i class="bi bi-qr-code-scan fs-1 gpay-blue d-block mb-2"></i>
                            <h6 class="fw-bold mb-1">Simulated QR Code Scanner</h6>
                            <p class="text-muted small mb-0">Point your camera or paste a UPI QR string below</p>
                        </div>
                        <div class="mb-3">
                            <input type="text" class="form-control rounded-pill text-center" id="scanQrInput" placeholder="upi://pay?pa=recipient@payben&pn=Store">
                        </div>
                        <button type="button" class="btn btn-gpay w-100 rounded-pill" onclick="processScannedQr()">
                            Simulate Scan &amp; Pay
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="scratchCardModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden text-center" style="background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%); color: white;">
            <div class="modal-header border-0 pb-0 justify-content-end">
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 p-md-5">
                <div id="scratchBefore">
                    <div class="fs-1 text-warning mb-2 animate__animated animate__pulse animate__infinite">
                        <i class="bi bi-gift-fill"></i>
                    </div>
                    <h3 class="fw-bold mb-2">You Have a Reward! 🎉</h3>
                    <p class="text-white-50 mb-4">Click below to scratch and reveal your PayBen cashback!</p>

                    <div class="gpay-scratch-box p-5 rounded-4 shadow-lg mx-auto mb-4" style="max-width: 280px; height: 180px; display: flex; align-items: center; justify-content: center;" onclick="revealScratchCard()">
                        <div class="text-center">
                            <i class="bi bi-sparkles fs-2 mb-1 d-block"></i>
                            <span class="fw-bold fs-5">SCRATCH HERE</span>
                        </div>
                    </div>
                </div>

                <div id="scratchAfter" style="display: none;">
                    <div class="gpay-success-check mb-3 mx-auto" style="background: #ffffff; color: #34a853;">
                        <i class="bi bi-trophy-fill"></i>
                    </div>
                    <h2 class="display-5 fw-bold text-warning mb-1" id="cashbackAmount">₹15.00</h2>
                    <h5 class="fw-bold mb-2">Cashback Won! 🎊</h5>
                    <p class="text-white-50 small mb-4" id="cashbackNote">Credited directly to your PayBen Wallet!</p>
                    <button type="button" class="btn btn-light rounded-pill px-4 py-2 fw-bold text-primary" onclick="location.reload()">
                        Awesome!
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal 4: Add Money to Wallet Modal -->
<div class="modal fade" id="addMoneyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold"><i class="bi bi-wallet2 text-primary"></i> Add Money to Wallet</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="addMoneyForm">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase">Enter Amount</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light fw-bold">₹</span>
                            <input type="number" class="form-control form-control-lg fw-bold" id="addAmountInput" placeholder="500" min="100" max="50000" required>
                        </div>
                        <div class="d-flex gap-2 mt-2">
                            <button type="button" class="btn btn-sm btn-light border rounded-pill" onclick="document.getElementById('addAmountInput').value=500">+₹500</button>
                            <button type="button" class="btn btn-sm btn-light border rounded-pill" onclick="document.getElementById('addAmountInput').value=1000">+₹1,000</button>
                            <button type="button" class="btn btn-sm btn-light border rounded-pill" onclick="document.getElementById('addAmountInput').value=2000">+₹2,000</button>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-gpay w-100 py-2 fw-bold">Add Money Instantly</button>
                </form>
            </div>
        </div>
    </div>
</div>


<script>
function playGpayChime() {
    try {
        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        
        // Tone 1: High crisp ding (880Hz)
        const osc1 = audioCtx.createOscillator();
        const gain1 = audioCtx.createGain();
        osc1.type = 'sine';
        osc1.frequency.setValueAtTime(880, audioCtx.currentTime);
        gain1.gain.setValueAtTime(0.3, audioCtx.currentTime);
        gain1.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.4);
        osc1.connect(gain1);
        gain1.connect(audioCtx.destination);
        osc1.start();
        osc1.stop(audioCtx.currentTime + 0.4);

        // Tone 2: Harmonic pleasant chime (1760Hz)
        setTimeout(() => {
            const osc2 = audioCtx.createOscillator();
            const gain2 = audioCtx.createGain();
            osc2.type = 'sine';
            osc2.frequency.setValueAtTime(1320, audioCtx.currentTime);
            gain2.gain.setValueAtTime(0.35, audioCtx.currentTime);
            gain2.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.6);
            osc2.connect(gain2);
            gain2.connect(audioCtx.destination);
            osc2.start();
            osc2.stop(audioCtx.currentTime + 0.6);
        }, 120);
    } catch (e) {
        console.log('Audio not supported');
    }
}

function copyUpiId(text) {
    navigator.clipboard.writeText(text).then(() => {
        if (typeof showToast === 'function') {
            showToast('UPI ID copied to clipboard: ' + text, 'success');
        } else {
            alert('Copied UPI ID: ' + text);
        }
    });
}

function openTransferModal(recipient, name) {
    document.getElementById('transferRecipient').value = recipient;
    if (name) {
        document.getElementById('recipientHelper').innerHTML = 'Paying to <strong class="text-primary">' + name + '</strong> (' + recipient + ')';
    } else {
        document.getElementById('recipientHelper').innerHTML = 'Enter registered 10-digit mobile number or UPI ID.';
    }
    const modal = new bootstrap.Modal(document.getElementById('sendMoneyModal'));
    modal.show();
}

function openScratchCardModal() {
    document.getElementById('scratchBefore').style.display = 'block';
    document.getElementById('scratchAfter').style.display = 'none';
    const modal = new bootstrap.Modal(document.getElementById('scratchCardModal'));
    modal.show();
}

// Scratch Card Reveal Logic
function revealScratchCard() {
    fetch('<?= APP_URL ?>/api/wallet/scratch-card.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            playGpayChime();
            document.getElementById('cashbackAmount').innerText = data.data.reward_formatted;
            document.getElementById('cashbackNote').innerText = data.message;
            document.getElementById('scratchBefore').style.display = 'none';
            document.getElementById('scratchAfter').style.display = 'block';
            document.getElementById('walletDisplay').innerText = data.data.balance_formatted;
        } else {
            alert(data.message);
        }
    })
    .catch(() => alert('Failed to redeem reward.'));
}

// UPI Send Money Submission
document.getElementById('upiTransferForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const recipient = document.getElementById('transferRecipient').value.trim();
    const amount = parseFloat(document.getElementById('transferAmount').value);
    const note = document.getElementById('transferNote').value.trim();
    const btn = document.getElementById('submitTransferBtn');

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Processing with PayBen UPI...';

    fetch('<?= APP_URL ?>/api/wallet/transfer.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ recipient, amount, note })
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-send-fill me-1"></i> Pay Securely';

        if (data.success) {
            playGpayChime();
            document.getElementById('upiTransferForm').style.display = 'none';
            document.getElementById('transferResultScreen').style.display = 'block';

            document.getElementById('resAmount').innerText = data.data.amount_formatted;
            document.getElementById('resRecipient').innerText = 'Paid to ' + data.data.recipient_name + ' (' + data.data.recipient_upi + ')';
            document.getElementById('resUpiRef').innerText = data.data.upi_ref;
            document.getElementById('resTxnId').innerText = data.data.transaction_id;
            document.getElementById('resTimestamp').innerText = data.data.timestamp;
            document.getElementById('walletDisplay').innerText = data.data.balance_formatted;
        } else {
            alert(data.message || 'Payment failed.');
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-send-fill me-1"></i> Pay Securely';
        alert('Network error. Please try again.');
    });
});

// Add Money Form Submission
document.getElementById('addMoneyForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const amount = parseFloat(document.getElementById('addAmountInput').value);

    fetch('<?= APP_URL ?>/api/wallet/add-money.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ amount })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            playGpayChime();
            alert('₹' + amount + ' added to wallet successfully!');
            location.reload();
        } else {
            alert(data.message || 'Failed to add money.');
        }
    })
    .catch(() => alert('Network error.'));
});

function processScannedQr() {
    const val = document.getElementById('scanQrInput').value.trim();
    if (!val) {
        alert('Please enter or paste a UPI QR code string.');
        return;
    }
    // Extract pa (payee address) and pn (payee name)
    const matchPa = val.match(/pa=([^&]+)/);
    const matchPn = val.match(/pn=([^&]+)/);
    const pa = matchPa ? decodeURIComponent(matchPa[1]) : val;
    const pn = matchPn ? decodeURIComponent(matchPn[1]) : 'Merchant';

    const qrModalEl = document.getElementById('qrModal');
    const qrModal = bootstrap.Modal.getInstance(qrModalEl);
    if (qrModal) qrModal.hide();

    openTransferModal(pa, pn);
}
</script>

<?php include 'includes/footer.php'; ?>
