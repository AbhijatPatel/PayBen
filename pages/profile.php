<?php
// profile page

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';

// Enforce authenticated user session
requireLogin();

$currentUser = getCurrentUser();
$userId = (int)$currentUser['id'];
$pdo = getDBConnection();

// Fetch fresh user and wallet profile from database
$userStmt = $pdo->prepare(
    "SELECT u.*, w.balance AS wallet_balance 
     FROM users u 
     LEFT JOIN wallets w ON u.id = w.user_id 
     WHERE u.id = ? 
     LIMIT 1"
);
$userStmt->execute([$userId]);
$user = $userStmt->fetch();

if (!$user) {
    setFlashMessage('danger', 'User account not found. Please sign in again.');
    header('Location: ' . APP_URL . '/login.php');
    exit;
}

$profileErrors = [];
$passwordErrors = [];
$activeTab = 'profile';

// -----------------------------------------------------------------------------
// POST Request Handlers
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Verify CSRF Token for all state modifications
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!validateCSRFToken($csrfToken)) {
        setFlashMessage('danger', 'Security verification failed (invalid CSRF token). Please try again.');
        header('Location: ' . APP_URL . '/pages/profile.php');
        exit;
    }

    // -------------------------------------------------------------------------
    // ACTION A: Update Profile Details
    // -------------------------------------------------------------------------
    if ($action === 'update_profile') {
        $activeTab = 'profile';
        $fullName = trim($_POST['full_name'] ?? '');
        $mobile = trim($_POST['mobile'] ?? '');

        // 1. Full Name Validation
        if (empty($fullName)) {
            $profileErrors['full_name'] = 'Full name is required.';
        } elseif (mb_strlen($fullName) < 3 || mb_strlen($fullName) > 100) {
            $profileErrors['full_name'] = 'Full name must be between 3 and 100 characters.';
        }

        // 2. Mobile Number Validation
        if (empty($mobile)) {
            $profileErrors['mobile'] = 'Mobile number is required.';
        } elseif (!preg_match('/^[6-9]\d{9}$/', $mobile)) {
            $profileErrors['mobile'] = 'Mobile number must be a valid 10-digit Indian number starting with 6, 7, 8, or 9.';
        } else {
            // Check mobile uniqueness excluding current user
            $dupMobileStmt = $pdo->prepare("SELECT id FROM users WHERE mobile = ? AND id != ? LIMIT 1");
            $dupMobileStmt->execute([$mobile, $userId]);
            if ($dupMobileStmt->fetch()) {
                $profileErrors['mobile'] = 'This mobile number is already linked to another PayBen account.';
            }
        }

        // 3. Update User Record & Session
        if (empty($profileErrors)) {
            try {
                $updateStmt = $pdo->prepare(
                    "UPDATE users 
                     SET full_name = ?, mobile = ?, updated_at = NOW() 
                     WHERE id = ?"
                );
                $updateStmt->execute([$fullName, $mobile, $userId]);

                // Update session state
                $_SESSION['user_name'] = $fullName;
                $_SESSION['user_mobile'] = $mobile;

                setFlashMessage('success', 'Profile updated successfully!');
                header('Location: ' . APP_URL . '/pages/profile.php');
                exit;

            } catch (PDOException $e) {
                error_log("Profile update error for user {$userId}: " . $e->getMessage());
                $profileErrors['general'] = 'Failed to update profile due to an internal system error.';
            }
        }
    }

    // -------------------------------------------------------------------------
    // ACTION B: Change Password
    // -------------------------------------------------------------------------
    elseif ($action === 'change_password') {
        $activeTab = 'password';
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        // 1. Current Password Verification
        if (empty($currentPassword)) {
            $passwordErrors['current_password'] = 'Current password is required.';
        } elseif (!password_verify($currentPassword, $user['password_hash'])) {
            $passwordErrors['current_password'] = 'The current password you entered is incorrect.';
        }

        // 2. New Password Validation
        if (empty($newPassword)) {
            $passwordErrors['new_password'] = 'New password is required.';
        } elseif (strlen($newPassword) < 8) {
            $passwordErrors['new_password'] = 'New password must be at least 8 characters long.';
        } elseif ($currentPassword === $newPassword) {
            $passwordErrors['new_password'] = 'New password cannot be identical to your current password.';
        }

        // 3. Confirm Password Match
        if (empty($confirmPassword)) {
            $passwordErrors['confirm_password'] = 'Please confirm your new password.';
        } elseif ($newPassword !== $confirmPassword) {
            $passwordErrors['confirm_password'] = 'New passwords do not match.';
        }

        // 4. Update Password Hash in Database
        if (empty($passwordErrors)) {
            try {
                $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
                $updatePwStmt = $pdo->prepare(
                    "UPDATE users 
                     SET password_hash = ?, updated_at = NOW() 
                     WHERE id = ?"
                );
                $updatePwStmt->execute([$newHash, $userId]);

                setFlashMessage('success', 'Password changed successfully! Please use your new password next time you log in.');
                header('Location: ' . APP_URL . '/pages/profile.php');
                exit;

            } catch (PDOException $e) {
                error_log("Password change error for user {$userId}: " . $e->getMessage());
                $passwordErrors['general'] = 'Failed to update password due to an internal system error.';
            }
        }
    }
}

$pageTitle = 'Account Profile & Security';
$currentPage = 'profile';

require_once dirname(__DIR__) . '/includes/dashboard_layout_top.php';
?>

<div class="container-fluid px-0">
    <!-- Page Header Title & Subtitle -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-primary bg-opacity-10 text-primary px-2 py-1">
                    <i class="bi bi-person-badge me-1"></i> Account Center
                </span>
                <span class="badge bg-success bg-opacity-10 text-success px-2 py-1">
                    <i class="bi bi-shield-check me-1"></i> Verified
                </span>
            </div>
            <h1 class="h3 fw-bold text-dark mb-0">Profile &amp; Settings</h1>
            <p class="text-muted small mb-0">Manage your personal information, mobile contact details, and account password.</p>
        </div>
        <div>
            <a href="<?= APP_URL ?>/pages/wallet.php" class="btn btn-outline-primary shadow-sm px-3 py-2 rounded-pill">
                <i class="bi bi-wallet2 me-1"></i> Wallet: <strong><?= formatCurrency($user['wallet_balance'] ?? 0.00) ?></strong>
            </a>
        </div>
    </div>
    <div class="row g-4">
        <div class="col-lg-4">
            <!-- Profile Identity Card -->
            <div class="card border-0 shadow-sm rounded-4 text-center p-4 bg-white mb-4">
                <div class="position-relative d-inline-block mx-auto mb-3">
                    <?php if (!empty($user['avatar']) && file_exists(dirname(__DIR__) . '/' . $user['avatar'])): ?>
                        <img src="<?= APP_URL ?>/<?= sanitize($user['avatar']) ?>" 
                             alt="<?= sanitize($user['full_name']) ?>" 
                             class="rounded-circle img-thumbnail shadow-sm" 
                             style="width: 100px; height: 100px; object-fit: cover;">
                    <?php else: ?>
                        <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold shadow-sm mx-auto" 
                             style="width: 100px; height: 100px; font-size: 2.25rem; background: linear-gradient(135deg, #4361ee 0%, #7209b7 100%);">
                            <?= strtoupper(substr($user['full_name'] ?? 'U', 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                    <span class="position-absolute bottom-0 end-0 bg-success border border-white rounded-circle p-2" title="Active Account"></span>
                </div>

                <h4 class="fw-bold text-dark mb-1"><?= sanitize($user['full_name']) ?></h4>
                <p class="text-muted small mb-3"><?= sanitize($user['email']) ?></p>

                <div class="d-flex justify-content-center gap-2 mb-3">
                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-1 rounded-pill">
                        <i class="bi bi-check-circle-fill me-1"></i> Active User
                    </span>
                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3 py-1 rounded-pill">
                        <i class="bi bi-shield-lock-fill me-1"></i> Standard
                    </span>
                </div>

                <hr class="my-3 opacity-25">

                <!-- Meta Information List -->
                <ul class="list-unstyled text-start small mb-4">
                    <li class="d-flex align-items-center justify-content-between py-2 border-bottom border-light">
                        <span class="text-muted"><i class="bi bi-envelope text-primary me-2"></i> Email Address</span>
                        <span class="fw-semibold text-dark text-truncate" style="max-width: 160px;" title="<?= sanitize($user['email']) ?>">
                            <?= sanitize($user['email']) ?>
                        </span>
                    </li>
                    <li class="d-flex align-items-center justify-content-between py-2 border-bottom border-light">
                        <span class="text-muted"><i class="bi bi-phone text-success me-2"></i> Mobile Number</span>
                        <span class="fw-semibold text-dark">+91 <?= sanitize($user['mobile']) ?></span>
                    </li>
                    <li class="d-flex align-items-center justify-content-between py-2 border-bottom border-light">
                        <span class="text-muted"><i class="bi bi-calendar-check text-info me-2"></i> Member Since</span>
                        <span class="fw-semibold text-dark"><?= date('F j, Y', strtotime($user['created_at'])) ?></span>
                    </li>
                    <li class="d-flex align-items-center justify-content-between py-2">
                        <span class="text-muted"><i class="bi bi-wallet2 text-warning me-2"></i> Wallet Balance</span>
                        <span class="fw-bold text-primary"><?= formatCurrency($user['wallet_balance'] ?? 0.00) ?></span>
                    </li>
                </ul>

                <a href="#editProfileSection" class="btn btn-outline-primary w-100 rounded-pill fw-semibold py-2">
                    <i class="bi bi-pencil-square me-1"></i> Edit Profile
                </a>
            </div>

            <!-- Security Badge Info Card -->
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="rounded-circle bg-primary bg-opacity-10 text-primary p-2 d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                        <i class="bi bi-shield-shaded fs-5"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-0 text-dark">Security Health</h6>
                        <span class="text-success small fw-semibold">Good &bull; Password Active</span>
                    </div>
                </div>
                <p class="text-muted small mb-0">
                    Your password protects your account and digital wallet transactions. We recommend changing it periodically.
                </p>
            </div>
        </div>
        <div class="col-lg-8">
            <!-- Alert Display for Profile Errors -->
            <?php if (!empty($profileErrors['general'])): ?>
                <div class="alert alert-danger border-0 rounded-3 shadow-sm mb-4" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= sanitize($profileErrors['general']) ?>
                </div>
            <?php endif; ?>

            <!-- Alert Display for Password Errors -->
            <?php if (!empty($passwordErrors['general'])): ?>
                <div class="alert alert-danger border-0 rounded-3 shadow-sm mb-4" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= sanitize($passwordErrors['general']) ?>
                </div>
            <?php endif; ?>
            <div class="card border-0 shadow-sm rounded-4 bg-white mb-4" id="editProfileSection">
                <div class="card-header bg-white border-bottom py-3 px-4 d-flex align-items-center gap-2">
                    <div class="rounded-circle bg-primary bg-opacity-10 text-primary p-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                        <i class="bi bi-person-fill fs-6"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold text-dark mb-0">Edit Profile Information</h5>
                        <span class="text-muted small">Update your primary identity and contact details.</span>
                    </div>
                </div>

                <div class="card-body p-4">
                    <form action="<?= APP_URL ?>/pages/profile.php" method="POST" id="profileForm">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <input type="hidden" name="action" value="update_profile">

                        <div class="row g-3 mb-4">
                            <!-- Full Name -->
                            <div class="col-md-6">
                                <label for="full_name" class="form-label fw-semibold">
                                    Full Name <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-secondary">
                                        <i class="bi bi-person"></i>
                                    </span>
                                    <input type="text" 
                                           class="form-control <?= isset($profileErrors['full_name']) ? 'is-invalid' : '' ?>" 
                                           id="full_name" 
                                           name="full_name" 
                                           value="<?= sanitize($_POST['full_name'] ?? $user['full_name']) ?>" 
                                           required 
                                           minlength="3" 
                                           maxlength="100">
                                    <?php if (isset($profileErrors['full_name'])): ?>
                                        <div class="invalid-feedback"><?= sanitize($profileErrors['full_name']) ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="form-text">As it appears on your official government IDs.</div>
                            </div>

                            <!-- Mobile Number -->
                            <div class="col-md-6">
                                <label for="mobile" class="form-label fw-semibold">
                                    Mobile Number <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-secondary fw-semibold">+91</span>
                                    <input type="tel" 
                                           class="form-control <?= isset($profileErrors['mobile']) ? 'is-invalid' : '' ?>" 
                                           id="mobile" 
                                           name="mobile" 
                                           value="<?= sanitize($_POST['mobile'] ?? $user['mobile']) ?>" 
                                           data-type="phone" 
                                           maxlength="10" 
                                           required 
                                           pattern="[6-9][0-9]{9}">
                                    <?php if (isset($profileErrors['mobile'])): ?>
                                        <div class="invalid-feedback"><?= sanitize($profileErrors['mobile']) ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="form-text">Used for instant SMS recharge alerts and account recovery.</div>
                            </div>

                            <!-- Email Address (Read-only) -->
                            <div class="col-12">
                                <label for="email_readonly" class="form-label fw-semibold">
                                    Email Address (Account Identifier)
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-secondary">
                                        <i class="bi bi-envelope"></i>
                                    </span>
                                    <input type="email" 
                                           class="form-control bg-light" 
                                           id="email_readonly" 
                                           value="<?= sanitize($user['email']) ?>" 
                                           readonly 
                                           disabled>
                                    <span class="input-group-text bg-light text-success" title="Verified Primary Email">
                                        <i class="bi bi-shield-check-fill me-1"></i> Verified
                                    </span>
                                </div>
                                <div class="form-text text-muted">
                                    <i class="bi bi-lock-fill me-1"></i> For your security, email changes require contacting PayBen Customer Care.
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary px-4 py-2 rounded-pill fw-bold">
                                <i class="bi bi-check2-circle me-1"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="card border-0 shadow-sm rounded-4 bg-white" id="changePasswordSection">
                <div class="card-header bg-white border-bottom py-3 px-4 d-flex align-items-center gap-2">
                    <div class="rounded-circle bg-warning bg-opacity-10 text-warning p-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                        <i class="bi bi-key-fill fs-6 text-dark"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold text-dark mb-0">Change Password</h5>
                        <span class="text-muted small">Update your login credentials to protect your digital wallet.</span>
                    </div>
                </div>

                <div class="card-body p-4">
                    <form action="<?= APP_URL ?>/pages/profile.php" method="POST" id="passwordForm">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <input type="hidden" name="action" value="change_password">

                        <!-- Current Password -->
                        <div class="mb-3">
                            <label for="current_password" class="form-label fw-semibold">
                                Current Password <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-secondary">
                                    <i class="bi bi-lock"></i>
                                </span>
                                <input type="password" 
                                       class="form-control <?= isset($passwordErrors['current_password']) ? 'is-invalid' : '' ?>" 
                                       id="current_password" 
                                       name="current_password" 
                                       placeholder="Enter your current password" 
                                       required>
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('current_password', this)" aria-label="Toggle password visibility">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <?php if (isset($passwordErrors['current_password'])): ?>
                                    <div class="invalid-feedback"><?= sanitize($passwordErrors['current_password']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <!-- New Password -->
                            <div class="col-md-6">
                                <label for="new_password" class="form-label fw-semibold">
                                    New Password <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-secondary">
                                        <i class="bi bi-shield-lock"></i>
                                    </span>
                                    <input type="password" 
                                           class="form-control <?= isset($passwordErrors['new_password']) ? 'is-invalid' : '' ?>" 
                                           id="new_password" 
                                           name="new_password" 
                                           placeholder="Minimum 8 characters" 
                                           minlength="8" 
                                           required>
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('new_password', this)" aria-label="Toggle password visibility">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <?php if (isset($passwordErrors['new_password'])): ?>
                                        <div class="invalid-feedback"><?= sanitize($passwordErrors['new_password']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Confirm New Password -->
                            <div class="col-md-6">
                                <label for="confirm_password" class="form-label fw-semibold">
                                    Confirm New Password <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-secondary">
                                        <i class="bi bi-check-all"></i>
                                    </span>
                                    <input type="password" 
                                           class="form-control <?= isset($passwordErrors['confirm_password']) ? 'is-invalid' : '' ?>" 
                                           id="confirm_password" 
                                           name="confirm_password" 
                                           placeholder="Re-enter new password" 
                                           minlength="8" 
                                           required>
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('confirm_password', this)" aria-label="Toggle password visibility">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <?php if (isset($passwordErrors['confirm_password'])): ?>
                                        <div class="invalid-feedback"><?= sanitize($passwordErrors['confirm_password']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Password Guidelines -->
                        <div class="p-3 rounded-3 bg-light border mb-4">
                            <span class="fw-semibold text-dark small d-block mb-2">Password Requirements:</span>
                            <div class="row g-2 text-muted small">
                                <div class="col-sm-6">
                                    <i class="bi bi-check-circle-fill text-success me-1"></i> At least 8 characters in length
                                </div>
                                <div class="col-sm-6">
                                    <i class="bi bi-check-circle-fill text-success me-1"></i> Combination of letters &amp; numbers
                                </div>
                                <div class="col-sm-6">
                                    <i class="bi bi-check-circle-fill text-success me-1"></i> Cannot match current password
                                </div>
                                <div class="col-sm-6">
                                    <i class="bi bi-check-circle-fill text-success me-1"></i> Special symbols allowed (!@#$%)
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-outline-dark px-4 py-2 rounded-pill fw-bold">
                                <i class="bi bi-shield-check me-1"></i> Update Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
/**
 * Toggle password input visibility between password and text
 */
function togglePasswordVisibility(inputId, buttonEl) {
    const input = document.getElementById(inputId);
    if (!input) return;
    const icon = buttonEl.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        if (icon) {
            icon.classList.remove('bi-eye');
            icon.classList.add('bi-eye-slash');
        }
    } else {
        input.type = 'password';
        if (icon) {
            icon.classList.remove('bi-eye-slash');
            icon.classList.add('bi-eye');
        }
    }
}
</script>

<?php
require_once dirname(__DIR__) . '/includes/dashboard_layout_bottom.php';
?>
