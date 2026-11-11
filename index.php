<?php
// Index

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: ' . APP_URL . '/dashboard.php');
    exit;
}

$pageTitle = 'Fast, Secure Recharges & Bill Payments';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Public Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom sticky-top py-2 shadow-xs">
    <div class="container">
        <!-- Brand Identity -->
        <a class="navbar-brand d-flex align-items-center gap-2 fw-bold text-decoration-none" href="<?php echo APP_URL; ?>/index.php">
            <div class="rounded-3 d-flex align-items-center justify-content-center text-white" style="width: 38px; height: 38px; background: linear-gradient(135deg, #4361ee 0%, #7209b7 100%); box-shadow: 0 4px 10px rgba(67, 97, 238, 0.3);">
                <i class="bi bi-wallet2 fs-5"></i>
            </div>
            <span class="fs-4 fw-bold">
                <span class="text-dark">Pay</span><span style="background: linear-gradient(135deg, #4361ee 0%, #7209b7 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Ben</span>
            </span>
        </a>

        <!-- Mobile Toggler -->
        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#landingNavbar" aria-controls="landingNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navbar Links & CTA Buttons -->
        <div class="collapse navbar-collapse" id="landingNavbar">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-4">
                <li class="nav-item">
                    <a class="nav-link fw-semibold text-secondary px-3" href="#hero">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fw-semibold text-secondary px-3" href="#features">Features</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fw-semibold text-secondary px-3" href="#how-it-works">How It Works</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fw-semibold text-secondary px-3" href="#security">Security</a>
                </li>
            </ul>

            <div class="d-flex align-items-center gap-2 mt-3 mt-lg-0">
                <a href="<?php echo APP_URL; ?>/login.php" class="btn btn-outline-primary px-3 py-2 rounded-pill fw-semibold">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Login
                </a>
                <a href="<?php echo APP_URL; ?>/register.php" class="btn btn-fintech-gradient px-4 py-2 rounded-pill fw-semibold shadow-sm text-white" style="background: linear-gradient(135deg, #4361ee 0%, #7209b7 100%); border: none;">
                    <i class="bi bi-person-plus me-1"></i> Get Started
                </a>
            </div>
        </div>
    </div>
</nav>
<section id="hero" class="hero-section text-white position-relative overflow-hidden py-5" style="background: linear-gradient(135deg, #1e1b4b 0%, #312e81 35%, #4338ca 70%, #6d28d9 100%);">
    <div class="position-absolute top-0 end-0 p-5 opacity-25 d-none d-lg-block pointer-events-none" style="transform: translate(20%, -20%);">
        <div style="width: 450px; height: 450px; border-radius: 50%; background: radial-gradient(circle, rgba(168,85,247,0.4) 0%, rgba(67,97,238,0) 70%);"></div>
    </div>
    <div class="position-absolute bottom-0 start-0 p-5 opacity-20 d-none d-lg-block pointer-events-none" style="transform: translate(-30%, 30%);">
        <div style="width: 380px; height: 380px; border-radius: 50%; background: radial-gradient(circle, rgba(59,130,246,0.5) 0%, rgba(67,97,238,0) 70%);"></div>
    </div>

    <div class="container py-lg-5 position-relative" style="z-index: 2;">
        <div class="row align-items-center g-5">
            <!-- Left Hero Content -->
            <div class="col-lg-7 text-center text-lg-start">
                <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-white bg-opacity-10 border border-white border-opacity-20 mb-3 text-white-50">
                    <span class="badge bg-success rounded-pill px-2 py-1 text-white small">NEW</span>
                    <span class="small text-white">Instant BBPS Utility & High-Speed 5G Recharges</span>
                </div>
                <h1 class="display-4 fw-extrabold text-white mb-3 lh-sm" style="font-weight: 800; letter-spacing: -0.03em;">
                    Fast, Secure Recharges &amp; <span style="background: linear-gradient(135deg, #a5b4fc 0%, #f472b6 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Bill Payments</span>
                </h1>
                <p class="lead text-white-50 mb-4 fs-5" style="max-width: 600px;">
                    Experience lightning-speed prepaid mobile recharge, DTH activations, electricity, water, and gas payments with India's smart digital wallet platform. Zero convenience fee, 100% secure.
                </p>

                <!-- Call-to-action buttons: Get Started -> register.php, Login -> login.php -->
                <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center justify-content-lg-start mb-4">
                    <a href="<?php echo APP_URL; ?>/register.php" class="btn btn-lg btn-light text-primary px-4 py-3 rounded-pill fw-bold shadow-lg d-inline-flex align-items-center justify-content-center gap-2" style="color: #4338ca !important;">
                        <i class="bi bi-rocket-takeoff-fill"></i> Get Started
                    </a>
                    <a href="<?php echo APP_URL; ?>/login.php" class="btn btn-lg btn-outline-light px-4 py-3 rounded-pill fw-bold d-inline-flex align-items-center justify-content-center gap-2">
                        <i class="bi bi-box-arrow-in-right"></i> Login
                    </a>
                </div>

                <!-- Trust Badges Under Hero -->
                <div class="d-flex flex-wrap align-items-center gap-4 justify-content-center justify-content-lg-start pt-2 text-white-50 small">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-shield-check text-success fs-5"></i>
                        <span>Bank-Grade 256-bit SSL</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-clock-history text-warning fs-5"></i>
                        <span>Instant 1-Second Credit</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-check-circle text-info fs-5"></i>
                        <span>Zero Hidden Charges</span>
                    </div>
                </div>
            </div>

            <!-- Right Hero Card Mockup -->
            <div class="col-lg-5">
                <div class="card border-0 shadow-2xl rounded-4 text-dark overflow-hidden position-relative" style="background: rgba(255, 255, 255, 0.98); backdrop-filter: blur(10px); box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.45);">
                    <div class="card-header bg-transparent border-0 pt-4 px-4 pb-2 d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <span class="p-2 rounded-3 bg-primary bg-opacity-10 text-primary">
                                <i class="bi bi-lightning-charge-fill fs-5"></i>
                            </span>
                            <div>
                                <h6 class="mb-0 fw-bold">Instant Quick Pay</h6>
                                <span class="text-muted small">Select a service to begin</span>
                            </div>
                        </div>
                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1">
                            <i class="bi bi-circle-fill me-1" style="font-size: 7px;"></i> Active
                        </span>
                    </div>

                    <div class="card-body px-4 pt-2 pb-4">
                        <!-- Quick Service Icon Grid -->
                        <div class="row g-2 mb-4">
                            <div class="col-4">
                                <a href="<?php echo APP_URL; ?>/pages/mobile_recharge.php" class="text-decoration-none">
                                    <div class="p-3 text-center rounded-3 border bg-light h-100 hover-elevate transition-all">
                                        <i class="bi bi-phone fs-2 text-primary d-block mb-1"></i>
                                        <span class="small fw-bold text-dark d-block">Mobile</span>
                                    </div>
                                </a>
                            </div>
                            <div class="col-4">
                                <a href="<?php echo APP_URL; ?>/pages/dth_recharge.php" class="text-decoration-none">
                                    <div class="p-3 text-center rounded-3 border bg-light h-100 hover-elevate transition-all">
                                        <i class="bi bi-tv fs-2 text-primary d-block mb-1"></i>
                                        <span class="small fw-bold text-dark d-block">DTH</span>
                                    </div>
                                </a>
                            </div>
                            <div class="col-4">
                                <a href="<?php echo APP_URL; ?>/pages/electricity.php" class="text-decoration-none">
                                    <div class="p-3 text-center rounded-3 border bg-light h-100 hover-elevate transition-all">
                                        <i class="bi bi-lightning-charge fs-2 text-warning d-block mb-1"></i>
                                        <span class="small fw-bold text-dark d-block">Electricity</span>
                                    </div>
                                </a>
                            </div>
                            <div class="col-4">
                                <a href="<?php echo APP_URL; ?>/pages/water.php" class="text-decoration-none">
                                    <div class="p-3 text-center rounded-3 border bg-light h-100 hover-elevate transition-all">
                                        <i class="bi bi-droplet-half fs-2 text-info d-block mb-1"></i>
                                        <span class="small fw-bold text-dark d-block">Water</span>
                                    </div>
                                </a>
                            </div>
                            <div class="col-4">
                                <a href="<?php echo APP_URL; ?>/pages/gas.php" class="text-decoration-none">
                                    <div class="p-3 text-center rounded-3 border bg-light h-100 hover-elevate transition-all">
                                        <i class="bi bi-fire fs-2 text-danger d-block mb-1"></i>
                                        <span class="small fw-bold text-dark d-block">Gas</span>
                                    </div>
                                </a>
                            </div>
                            <div class="col-4">
                                <a href="<?php echo APP_URL; ?>/wallet.php" class="text-decoration-none">
                                    <div class="p-3 text-center rounded-3 border bg-light h-100 hover-elevate transition-all">
                                        <i class="bi bi-wallet2 fs-2 text-success d-block mb-1"></i>
                                        <span class="small fw-bold text-dark d-block">Wallet</span>
                                    </div>
                                </a>
                            </div>
                        </div>

                        <!-- Sample Wallet Card Preview -->
                        <div class="p-3 rounded-3 text-white position-relative overflow-hidden" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="small text-white-50 text-uppercase tracking-wider">PayBen Balance</span>
                                <span class="badge bg-primary text-white rounded-pill px-2">Instant UPI</span>
                            </div>
                            <h3 class="fw-bold mb-1">₹5,000.00</h3>
                            <div class="d-flex justify-content-between align-items-center text-white-50 small mt-2 pt-2 border-top border-secondary border-opacity-25">
                                <span>Rahul Sharma &bull; 9876543210</span>
                                <span class="text-success"><i class="bi bi-shield-fill-check"></i> Verified</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<section id="features" class="py-5 bg-white">
    <div class="container py-lg-4">
        <div class="text-center max-w-xl mx-auto mb-5">
            <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill fw-semibold mb-2">
                COMPLETE PAYMENT SUITE
            </span>
            <h2 class="display-6 fw-bold text-dark mb-3">All Your Essential Payments in One App</h2>
            <p class="text-muted">Pay bills and recharge anytime, anywhere with seamless zero-delay processing and instant downloadable invoices.</p>
        </div>

        <div class="row g-4">
            <!-- Feature Card 1: Mobile Recharge -->
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm rounded-4 p-4 hover-lift transition-all" style="background: #f8fafc; border: 1px solid #e2e8f0 !important;">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-4 mb-4 text-primary" style="width: 58px; height: 58px; background: rgba(67, 97, 238, 0.12);">
                        <i class="bi bi-phone-fill fs-3"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Mobile Recharge</h5>
                    <p class="text-muted small mb-4">
                        Instant prepaid recharge for Jio, Airtel, Vi, and BSNL. Explore 4G/5G plans, unlimited calling packs, data boosters, and validity extensions.
                    </p>
                    <div class="mt-auto pt-2">
                        <a href="<?php echo APP_URL; ?>/pages/mobile_recharge.php" class="fw-bold text-primary text-decoration-none small d-inline-flex align-items-center gap-1">
                            Recharge Now <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Feature Card 2: DTH Recharge -->
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm rounded-4 p-4 hover-lift transition-all" style="background: #f8fafc; border: 1px solid #e2e8f0 !important;">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-4 mb-4 text-primary" style="width: 58px; height: 58px; background: rgba(114, 9, 183, 0.12); color: #7209b7 !important;">
                        <i class="bi bi-tv-fill fs-3"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">DTH Recharge</h5>
                    <p class="text-muted small mb-4">
                        Quick digital TV top-ups for Tata Play, Airtel DTH, Dish TV, and Sun Direct. Never miss your favorite live sports or channels.
                    </p>
                    <div class="mt-auto pt-2">
                        <a href="<?php echo APP_URL; ?>/pages/dth_recharge.php" class="fw-bold text-primary text-decoration-none small d-inline-flex align-items-center gap-1">
                            Recharge DTH <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Feature Card 3: Electricity -->
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm rounded-4 p-4 hover-lift transition-all" style="background: #f8fafc; border: 1px solid #e2e8f0 !important;">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-4 mb-4 text-warning" style="width: 58px; height: 58px; background: rgba(245, 158, 11, 0.15);">
                        <i class="bi bi-lightning-charge-fill fs-3"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Electricity</h5>
                    <p class="text-muted small mb-4">
                        Fetch and pay state electricity bills seamlessly (BESCOM, Tata Power, BRPL, Torrent Power). Avoid late payment penalties.
                    </p>
                    <div class="mt-auto pt-2">
                        <a href="<?php echo APP_URL; ?>/pages/electricity.php" class="fw-bold text-warning text-decoration-none small d-inline-flex align-items-center gap-1">
                            Pay Electricity <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Feature Card 4: Water -->
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm rounded-4 p-4 hover-lift transition-all" style="background: #f8fafc; border: 1px solid #e2e8f0 !important;">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-4 mb-4 text-info" style="width: 58px; height: 58px; background: rgba(6, 182, 212, 0.15);">
                        <i class="bi bi-droplet-fill fs-3"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Water</h5>
                    <p class="text-muted small mb-4">
                        Municipal water board payments made easy for Delhi Jal Board (DJB), BWSSB Bangalore, MCGM Mumbai, and all major cities.
                    </p>
                    <div class="mt-auto pt-2">
                        <a href="<?php echo APP_URL; ?>/pages/water.php" class="fw-bold text-info text-decoration-none small d-inline-flex align-items-center gap-1">
                            Pay Water Bill <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Feature Card 5: Gas -->
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm rounded-4 p-4 hover-lift transition-all" style="background: #f8fafc; border: 1px solid #e2e8f0 !important;">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-4 mb-4 text-danger" style="width: 58px; height: 58px; background: rgba(239, 71, 111, 0.15);">
                        <i class="bi bi-fire fs-3"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Gas</h5>
                    <p class="text-muted small mb-4">
                        Pay piped cooking gas dues for IGL Delhi, Mahanagar Gas (MGL), and Adani Gas with automated instant consumer verification.
                    </p>
                    <div class="mt-auto pt-2">
                        <a href="<?php echo APP_URL; ?>/pages/gas.php" class="fw-bold text-danger text-decoration-none small d-inline-flex align-items-center gap-1">
                            Pay Gas Bill <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Feature Card 6: Wallet -->
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm rounded-4 p-4 hover-lift transition-all" style="background: #f8fafc; border: 1px solid #e2e8f0 !important;">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-4 mb-4 text-success" style="width: 58px; height: 58px; background: rgba(16, 185, 129, 0.15);">
                        <i class="bi bi-wallet2 fs-3"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Digital Wallet</h5>
                    <p class="text-muted small mb-4">
                        Preload your secure PayBen wallet via UPI, Net Banking, or Cards for 1-click checkout with zero OTP delays and automated refunds.
                    </p>
                    <div class="mt-auto pt-2">
                        <a href="<?php echo APP_URL; ?>/wallet.php" class="fw-bold text-success text-decoration-none small d-inline-flex align-items-center gap-1">
                            Manage Wallet <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 3. "How It Works" Section (3 Steps: Register -> Add Money -> Pay Bills) -->
<section id="how-it-works" class="py-5" style="background-color: #f4f7fe;">
    <div class="container py-lg-4">
        <div class="text-center max-w-xl mx-auto mb-5">
            <span class="badge bg-secondary bg-opacity-10 text-secondary px-3 py-2 rounded-pill fw-semibold mb-2">
                EFFORTLESS ONBOARDING
            </span>
            <h2 class="display-6 fw-bold text-dark mb-3">How It Works in 3 Simple Steps</h2>
            <p class="text-muted">Start settling recharges and household utility bills in less than two minutes.</p>
        </div>

        <div class="row g-4 position-relative">
            <!-- Step 1: Register -->
            <div class="col-lg-4 text-center">
                <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-white">
                    <div class="position-relative d-inline-block mx-auto mb-4">
                        <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold fs-4 mx-auto" style="width: 72px; height: 72px; background: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%); box-shadow: 0 8px 20px rgba(67, 97, 238, 0.35);">
                            1
                        </div>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Step 1: Register</h5>
                    <p class="text-muted small mb-0">
                        Create your free PayBen account in under 60 seconds with simple name, mobile, and email verification.
                    </p>
                </div>
            </div>

            <!-- Step 2: Add Money -->
            <div class="col-lg-4 text-center">
                <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-white">
                    <div class="position-relative d-inline-block mx-auto mb-4">
                        <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold fs-4 mx-auto" style="width: 72px; height: 72px; background: linear-gradient(135deg, #7209b7 0%, #b5179e 100%); box-shadow: 0 8px 20px rgba(114, 9, 183, 0.35);">
                            2
                        </div>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Step 2: Add Money</h5>
                    <p class="text-muted small mb-0">
                        Top up your wallet easily via UPI apps (GPay, PhonePe, Paytm), debit cards, or net banking with end-to-end encryption.
                    </p>
                </div>
            </div>

            <!-- Step 3: Pay Bills -->
            <div class="col-lg-4 text-center">
                <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-white">
                    <div class="position-relative d-inline-block mx-auto mb-4">
                        <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold fs-4 mx-auto" style="width: 72px; height: 72px; background: linear-gradient(135deg, #06d6a0 0%, #118ab2 100%); box-shadow: 0 8px 20px rgba(6, 214, 160, 0.35);">
                            3
                        </div>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Step 3: Pay Bills</h5>
                    <p class="text-muted small mb-0">
                        Select your operator or biller, fetch current dues, and settle in 1 click with instant automated receipts.
                    </p>
                </div>
            </div>
        </div>

        <!-- Banner Call-to-action in Steps -->
        <div class="text-center mt-5">
            <a href="<?php echo APP_URL; ?>/register.php" class="btn btn-fintech-gradient btn-lg px-5 py-3 rounded-pill fw-bold shadow text-white" style="background: linear-gradient(135deg, #4361ee 0%, #7209b7 100%); border: none;">
                Create Your Account Now <i class="bi bi-arrow-right ms-2"></i>
            </a>
        </div>
    </div>
</section>
<section id="security" class="py-5 bg-white border-top">
    <div class="container py-lg-4">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill fw-semibold mb-2">
                    FINANCIAL GRADE SECURITY
                </span>
                <h2 class="display-6 fw-bold text-dark mb-3">Your Money &amp; Data Are Always Protected</h2>
                <p class="text-muted mb-4">
                    PayBen adheres to stringent banking security protocols. Every rupee processed is shielded by multi-layer authentication and tokenized databases.
                </p>

                <div class="d-flex flex-column gap-3">
                    <div class="d-flex align-items-start gap-3">
                        <div class="p-2 rounded-3 bg-primary bg-opacity-10 text-primary">
                            <i class="bi bi-shield-lock-fill fs-5"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1 text-dark">End-to-End 256-Bit SSL Encryption</h6>
                            <p class="text-muted small mb-0">All data transfers between your browser and our servers are encrypted via SHA-256 SSL certificates.</p>
                        </div>
                    </div>

                    <div class="d-flex align-items-start gap-3">
                        <div class="p-2 rounded-3 bg-success bg-opacity-10 text-success">
                            <i class="bi bi-patch-check-fill fs-5"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1 text-dark">Double-Entry Audited Wallet</h6>
                            <p class="text-muted small mb-0">Every transaction has an immutable audit log ensuring accurate balance verification with zero discrepancy.</p>
                        </div>
                    </div>

                    <div class="d-flex align-items-start gap-3">
                        <div class="p-2 rounded-3 bg-info bg-opacity-10 text-info">
                            <i class="bi bi-shield-check fs-5"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1 text-dark">Instant Automated Refunds</h6>
                            <p class="text-muted small mb-0">If a network provider fails to fulfill a recharge, your funds are reversed into your wallet instantaneously.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 text-center">
                <div class="p-5 rounded-4 shadow-lg text-white text-center position-relative overflow-hidden" style="background: linear-gradient(135deg, #1e1b4b 0%, #4338ca 100%);">
                    <i class="bi bi-shield-shaded display-1 text-warning mb-3 d-inline-block"></i>
                    <h3 class="fw-bold mb-2">100% Protected Payments</h3>
                    <p class="text-white-50 small mb-4">Trusted by over 50,000+ satisfied customers nationwide for daily household bills and fast mobile top-ups.</p>
                    <div class="row g-3 text-center pt-3 border-top border-white border-opacity-20">
                        <div class="col-4">
                            <h4 class="fw-bold mb-0">99.9%</h4>
                            <span class="text-white-50 small">Success Rate</span>
                        </div>
                        <div class="col-4">
                            <h4 class="fw-bold mb-0">&lt; 2s</h4>
                            <span class="text-white-50 small">Processing</span>
                        </div>
                        <div class="col-4">
                            <h4 class="fw-bold mb-0">24/7</h4>
                            <span class="text-white-50 small">Live Support</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<footer class="bg-dark text-white pt-5 pb-4 border-top border-secondary border-opacity-25" style="background: linear-gradient(180deg, #161a29 0%, #0d111b 100%) !important;">
    <div class="container">
        <div class="row g-4 mb-4">
            <!-- Brand Column -->
            <div class="col-lg-4 col-md-6">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center text-white" style="width: 38px; height: 38px; background: linear-gradient(135deg, #4361ee 0%, #7209b7 100%);">
                        <i class="bi bi-wallet2 fs-5"></i>
                    </div>
                    <span class="fs-4 fw-bold text-white tracking-tight">Pay<span class="text-primary">Ben</span></span>
                </div>
                <p class="text-white-50 small mb-3">
                    Fast, Secure Recharges &amp; Bill Payments. India's trusted digital platform for prepaid recharge, DTH top-ups, and utility bills settlement with instant automated receipt generation.
                </p>
                <div class="d-flex gap-2">
                    <span class="badge bg-secondary bg-opacity-25 text-light px-2 py-1 small"><i class="bi bi-shield-lock-fill text-success me-1"></i> 256-Bit SSL</span>
                    <span class="badge bg-secondary bg-opacity-25 text-light px-2 py-1 small"><i class="bi bi-patch-check-fill text-info me-1"></i> BBPS Certified</span>
                    <span class="badge bg-secondary bg-opacity-25 text-light px-2 py-1 small"><i class="bi bi-lightning-fill text-warning me-1"></i> Instant</span>
                </div>
            </div>

            <!-- Services Quick Links -->
            <div class="col-lg-2 col-md-6 col-6">
                <h6 class="text-uppercase fw-bold text-white mb-3 small tracking-wider">Services</h6>
                <ul class="list-unstyled mb-0 small">
                    <li class="mb-2">
                        <a href="<?php echo APP_URL; ?>/pages/mobile_recharge.php" class="text-white-50 text-decoration-none hover-white">
                            <i class="bi bi-phone me-1 text-primary"></i> Mobile Recharge
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="<?php echo APP_URL; ?>/pages/dth_recharge.php" class="text-white-50 text-decoration-none hover-white">
                            <i class="bi bi-tv me-1 text-primary"></i> DTH Recharge
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="<?php echo APP_URL; ?>/pages/electricity.php" class="text-white-50 text-decoration-none hover-white">
                            <i class="bi bi-lightning-charge me-1 text-primary"></i> Electricity Bill
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="<?php echo APP_URL; ?>/pages/water.php" class="text-white-50 text-decoration-none hover-white">
                            <i class="bi bi-droplet me-1 text-primary"></i> Water Bill
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="<?php echo APP_URL; ?>/pages/gas.php" class="text-white-50 text-decoration-none hover-white">
                            <i class="bi bi-fire me-1 text-primary"></i> Piped Gas
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="<?php echo APP_URL; ?>/wallet.php" class="text-white-50 text-decoration-none hover-white">
                            <i class="bi bi-wallet2 me-1 text-primary"></i> Digital Wallet
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Quick Navigation Links -->
            <div class="col-lg-2 col-md-6 col-6">
                <h6 class="text-uppercase fw-bold text-white mb-3 small tracking-wider">Navigation</h6>
                <ul class="list-unstyled mb-0 small">
                    <li class="mb-2">
                        <a href="<?php echo APP_URL; ?>/index.php" class="text-white-50 text-decoration-none hover-white">Home</a>
                    </li>
                    <li class="mb-2">
                        <a href="<?php echo APP_URL; ?>/index.php#features" class="text-white-50 text-decoration-none hover-white">Features</a>
                    </li>
                    <li class="mb-2">
                        <a href="<?php echo APP_URL; ?>/index.php#how-it-works" class="text-white-50 text-decoration-none hover-white">How It Works</a>
                    </li>
                    <li class="mb-2">
                        <a href="<?php echo APP_URL; ?>/login.php" class="text-white-50 text-decoration-none hover-white">User Login</a>
                    </li>
                    <li class="mb-2">
                        <a href="<?php echo APP_URL; ?>/register.php" class="text-white-50 text-decoration-none hover-white">Register Account</a>
                    </li>
                    <li class="mb-2">
                        <a href="<?php echo APP_URL; ?>/admin/login.php" class="text-white-50 text-decoration-none hover-white">Admin Portal</a>
                    </li>
                </ul>
            </div>

            <!-- Contact & Support Info -->
            <div class="col-lg-4 col-md-6">
                <h6 class="text-uppercase fw-bold text-white mb-3 small tracking-wider">Contact &amp; Support</h6>
                <ul class="list-unstyled small text-white-50 mb-3">
                    <li class="mb-2 d-flex align-items-center gap-2">
                        <i class="bi bi-envelope text-primary"></i>
                        <span>support@payben.com</span>
                    </li>
                    <li class="mb-2 d-flex align-items-center gap-2">
                        <i class="bi bi-telephone text-success"></i>
                        <span>+91 1800-123-BENPAY (Toll-Free 24/7)</span>
                    </li>
                    <li class="mb-2 d-flex align-items-center gap-2">
                        <i class="bi bi-geo-alt text-danger"></i>
                        <span>Fintech Tower, Bandra Kurla Complex, Mumbai, India</span>
                    </li>
                </ul>
                <div class="pt-2">
                    <span class="text-white-50 small d-block mb-2">Supported Payment Methods:</span>
                    <div class="d-flex align-items-center gap-3 fs-5 text-secondary">
                        <span title="UPI Enabled"><i class="bi bi-qr-code-scan"></i> UPI</span>
                        <span title="Credit &amp; Debit Cards"><i class="bi bi-credit-card-2-front"></i> Cards</span>
                        <span title="Net Banking"><i class="bi bi-bank"></i> NetBanking</span>
                        <span title="PayBen Wallet"><i class="bi bi-wallet2"></i> Wallet</span>
                    </div>
                </div>
            </div>
        </div>

        <hr class="border-secondary border-opacity-25 my-4">

        <div class="d-flex flex-column flex-md-row align-items-center justify-content-between small text-white-50">
            <p class="mb-2 mb-md-0">
                &copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?> Technologies Pvt. Ltd. All rights reserved.
            </p>
            <div class="d-flex gap-3">
                <span class="text-white-50">v<?php echo APP_VERSION; ?></span>
                <span class="text-secondary">&bull;</span>
                <a href="#privacy" class="text-white-50 text-decoration-none">Privacy Policy</a>
                <span class="text-secondary">&bull;</span>
                <a href="#terms" class="text-white-50 text-decoration-none">Terms of Service</a>
                <span class="text-secondary">&bull;</span>
                <a href="#security" class="text-white-50 text-decoration-none">Security</a>
            </div>
        </div>
    </div>
</footer>

<style>
    .hover-lift {
        transition: transform 0.25s ease, box-shadow 0.25s ease;
    }
    .hover-lift:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08) !important;
    }
    .hover-elevate:hover {
        transform: translateY(-3px);
        border-color: #4361ee !important;
        box-shadow: 0 4px 12px rgba(67, 97, 238, 0.15);
    }
    .hover-white:hover {
        color: #ffffff !important;
        transition: color 0.2s ease;
    }
</style>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
