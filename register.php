<?php
// Register

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

// Redirect authenticated users to dashboard
if (isLoggedIn()) {
    header('Location: ' . APP_URL . '/dashboard.php');
    exit;
}

$errors = [];
$formData = [
    'full_name' => '',
    'email' => '',
    'mobile' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Validate CSRF Token
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!validateCSRFToken($csrfToken)) {
        $errors['general'] = 'Security verification failed (invalid CSRF token). Please refresh and try again.';
    }

    // 2. Extract and sanitize inputs
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim(filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL));
    $mobile = trim($_POST['mobile'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    $formData['full_name'] = $fullName;
    $formData['email'] = $email;
    $formData['mobile'] = $mobile;

    // 3. Server-side Validation
    // Full Name
    if (empty($fullName)) {
        $errors['full_name'] = 'Full name is required.';
    } elseif (mb_strlen($fullName) < 3 || mb_strlen($fullName) > 100) {
        $errors['full_name'] = 'Full name must be between 3 and 100 characters.';
    }

    // Email
    if (empty($email)) {
        $errors['email'] = 'Email address is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please provide a valid email address.';
    }

    // Mobile Number (10 digits)
    if (empty($mobile)) {
        $errors['mobile'] = 'Mobile number is required.';
    } elseif (!preg_match('/^[6-9]\d{9}$/', $mobile)) {
        $errors['mobile'] = 'Mobile number must be a valid 10-digit Indian number starting with 6, 7, 8, or 9.';
    }

    // Password
    if (empty($password)) {
        $errors['password'] = 'Password is required.';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters long.';
    }

    // Confirm Password
    if (empty($confirmPassword)) {
        $errors['confirm_password'] = 'Please confirm your password.';
    } elseif ($password !== $confirmPassword) {
        $errors['confirm_password'] = 'Passwords do not match.';
    }

    // 4. Check for duplicate Email and Mobile in DB
    if (empty($errors)) {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("SELECT id, email, mobile FROM users WHERE email = ? OR mobile = ? LIMIT 1");
            $stmt->execute([$email, $mobile]);
            $existingUser = $stmt->fetch();

            if ($existingUser) {
                if (strcasecmp($existingUser['email'], $email) === 0) {
                    $errors['email'] = 'An account with this email address is already registered.';
                }
                if ($existingUser['mobile'] === $mobile) {
                    $errors['mobile'] = 'An account with this mobile number is already registered.';
                }
            }
        } catch (PDOException $e) {
            error_log("Registration check duplicate error: " . $e->getMessage());
            $errors['general'] = 'A system error occurred. Please try again shortly.';
        }
    }

    // 5. Insert new user and initialize wallet (inside atomic transaction)
    if (empty($errors)) {
        try {
            $pdo = getDBConnection();
            $pdo->beginTransaction();

            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            // Insert into users
            $insertUserStmt = $pdo->prepare(
                "INSERT INTO users (full_name, email, mobile, password_hash, status, created_at) VALUES (?, ?, ?, ?, 'active', NOW())"
            );
            $insertUserStmt->execute([$fullName, $email, $mobile, $passwordHash]);
            $newUserId = (int)$pdo->lastInsertId();

            // Initialize user wallet with 0.00 balance
            $insertWalletStmt = $pdo->prepare(
                "INSERT INTO wallets (user_id, balance, created_at) VALUES (?, 0.00, NOW())"
            );
            $insertWalletStmt->execute([$newUserId]);

            $pdo->commit();

            // Set flash message and redirect to login
            setFlashMessage('success', 'Account registered successfully! Please sign in with your credentials.');
            header('Location: ' . APP_URL . '/login.php');
            exit;

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("User registration failed: " . $e->getMessage());
            $errors['general'] = 'Registration failed due to a database error. Please try again.';
        }
    }
}

$pageTitle = 'Create Your Free Account';
require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-page-wrapper py-5" style="background: linear-gradient(135deg, #f8fafd 0%, #e8f0fe 100%); min-height: calc(100vh - 180px); display: flex; align-items: center;">
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-9 col-lg-6 col-xl-5">

                <!-- PayBen Smart UPI Register Card -->
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden" style="background: #ffffff; border: 1px solid #dadce0 !important;">
                    <div class="card-header bg-white border-0 pt-4 pb-2 px-4 text-center">
                        <div class="rounded-3 mx-auto mb-3 d-flex align-items-center justify-content-center shadow-sm text-white" 
                             style="width: 58px; height: 58px; background: linear-gradient(135deg, #2563eb 0%, #10b981 100%);">
                            <i class="bi bi-lightning-charge-fill fs-2"></i>
                        </div>
                        <h4 class="fw-bold text-dark mb-1">
                            Pay<span style="color: #2563eb;">Ben</span>
                        </h4>
                        <p class="text-muted small mb-0">Create your free PayBen UPI digital wallet</p>
                    </div>

                    <div class="card-body p-4 pt-2">
                        <!-- General Error Alert -->
                        <?php if (!empty($errors['general'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 small rounded-3 py-2" role="alert">
                                <i class="bi bi-exclamation-triangle-fill fs-6 text-danger"></i>
                                <div><?php echo htmlspecialchars($errors['general'], ENT_QUOTES, 'UTF-8'); ?></div>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>" method="POST" id="registerForm" novalidate>
                            <!-- CSRF Token -->
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCSRFToken(), ENT_QUOTES, 'UTF-8'); ?>">

                            <!-- Full Name Field -->
                            <div class="mb-3">
                                <label for="full_name" class="form-label small fw-semibold text-secondary">Full Name</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted">
                                        <i class="bi bi-person"></i>
                                    </span>
                                    <input 
                                        type="text" 
                                        name="full_name" 
                                        id="full_name" 
                                        class="form-control border-start-0 <?php echo isset($errors['full_name']) ? 'is-invalid' : ''; ?>" 
                                        placeholder="Enter your full name" 
                                        value="<?php echo htmlspecialchars($formData['full_name'], ENT_QUOTES, 'UTF-8'); ?>" 
                                        required 
                                        minlength="3" 
                                        maxlength="100"
                                    >
                                    <?php if (isset($errors['full_name'])): ?>
                                        <div class="invalid-feedback d-block"><?php echo htmlspecialchars($errors['full_name'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="invalid-feedback" id="client_full_name_error"></div>
                            </div>

                            <!-- Email Field -->
                            <div class="mb-3">
                                <label for="email" class="form-label small fw-semibold text-secondary">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted">
                                        <i class="bi bi-envelope"></i>
                                    </span>
                                    <input 
                                        type="email" 
                                        name="email" 
                                        id="email" 
                                        class="form-control border-start-0 <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" 
                                        placeholder="name@example.com" 
                                        value="<?php echo htmlspecialchars($formData['email'], ENT_QUOTES, 'UTF-8'); ?>" 
                                        required
                                    >
                                    <?php if (isset($errors['email'])): ?>
                                        <div class="invalid-feedback d-block"><?php echo htmlspecialchars($errors['email'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="invalid-feedback" id="client_email_error"></div>
                            </div>

                            <!-- Mobile Number Field (10 Digits) -->
                            <div class="mb-3">
                                <label for="mobile" class="form-label small fw-semibold text-secondary">Mobile Number (10 Digits)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted">
                                        <span class="small fw-semibold">+91</span>
                                    </span>
                                    <input 
                                        type="tel" 
                                        name="mobile" 
                                        id="mobile" 
                                        class="form-control border-start-0 <?php echo isset($errors['mobile']) ? 'is-invalid' : ''; ?>" 
                                        placeholder="9876543210" 
                                        value="<?php echo htmlspecialchars($formData['mobile'], ENT_QUOTES, 'UTF-8'); ?>" 
                                        required 
                                        pattern="[6-9][0-9]{9}" 
                                        maxlength="10"
                                    >
                                    <?php if (isset($errors['mobile'])): ?>
                                        <div class="invalid-feedback d-block"><?php echo htmlspecialchars($errors['mobile'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="invalid-feedback" id="client_mobile_error"></div>
                            </div>

                            <!-- Password Field (Min 8 Chars) -->
                            <div class="mb-3">
                                <label for="password" class="form-label small fw-semibold text-secondary">Password (Min. 8 characters)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted">
                                        <i class="bi bi-lock"></i>
                                    </span>
                                    <input 
                                        type="password" 
                                        name="password" 
                                        id="password" 
                                        class="form-control border-start-0 border-end-0 <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>" 
                                        placeholder="Create a strong password" 
                                        required 
                                        minlength="8"
                                    >
                                    <button class="btn btn-light border border-start-0 text-muted toggle-password" type="button" data-target="password" tabindex="-1">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <?php if (isset($errors['password'])): ?>
                                        <div class="invalid-feedback d-block"><?php echo htmlspecialchars($errors['password'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="invalid-feedback" id="client_password_error"></div>
                            </div>

                            <!-- Confirm Password Field -->
                            <div class="mb-4">
                                <label for="confirm_password" class="form-label small fw-semibold text-secondary">Confirm Password</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted">
                                        <i class="bi bi-shield-lock"></i>
                                    </span>
                                    <input 
                                        type="password" 
                                        name="confirm_password" 
                                        id="confirm_password" 
                                        class="form-control border-start-0 border-end-0 <?php echo isset($errors['confirm_password']) ? 'is-invalid' : ''; ?>" 
                                        placeholder="Re-enter your password" 
                                        required
                                    >
                                    <button class="btn btn-light border border-start-0 text-muted toggle-password" type="button" data-target="confirm_password" tabindex="-1">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <?php if (isset($errors['confirm_password'])): ?>
                                        <div class="invalid-feedback d-block"><?php echo htmlspecialchars($errors['confirm_password'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="invalid-feedback" id="client_confirm_password_error"></div>
                            </div>

                            <!-- Terms Notice -->
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="termsCheck" required checked>
                                <label class="form-check-label small text-muted" for="termsCheck">
                                    I agree to PayBen's <a href="#terms" class="text-primary text-decoration-none">Terms of Service</a> &amp; <a href="#privacy" class="text-primary text-decoration-none">Privacy Policy</a>
                                </label>
                            </div>

                            <!-- Submit Button -->
                            <button type="submit" class="btn btn-gpay w-100 py-2 fs-6 rounded-pill fw-bold shadow-sm" id="registerSubmitBtn">
                                <i class="bi bi-person-check-fill me-1"></i> Create PayBen UPI Account
                            </button>
                        </form>
                    </div>

                    <div class="card-footer bg-light border-0 py-3 text-center">
                        <span class="small text-muted">Already have a PayBen account?</span>
                        <a href="<?php echo APP_URL; ?>/login.php" class="small fw-bold text-primary text-decoration-none ms-1">
                            Sign In Here <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Client-Side Form Validation & Toggle Visibility Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle password visibility
    const toggleButtons = document.querySelectorAll('.toggle-password');
    toggleButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const targetInput = document.getElementById(targetId);
            const icon = this.querySelector('i');
            if (targetInput.type === 'password') {
                targetInput.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                targetInput.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        });
    });

    // Form Client-Side Validation
    const form = document.getElementById('registerForm');
    const fullNameInput = document.getElementById('full_name');
    const emailInput = document.getElementById('email');
    const mobileInput = document.getElementById('mobile');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');

    function showError(input, errorElementId, message) {
        input.classList.add('is-invalid');
        const errEl = document.getElementById(errorElementId);
        if (errEl) {
            errEl.textContent = message;
            errEl.style.display = 'block';
        }
    }

    function clearError(input, errorElementId) {
        input.classList.remove('is-invalid');
        const errEl = document.getElementById(errorElementId);
        if (errEl) {
            errEl.textContent = '';
            errEl.style.display = 'none';
        }
    }

    // Only allow digits in mobile field
    mobileInput.addEventListener('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
    });

    form.addEventListener('submit', function(e) {
        let isValid = true;

        // Full Name
        if (!fullNameInput.value.trim()) {
            showError(fullNameInput, 'client_full_name_error', 'Full name is required.');
            isValid = false;
        } else if (fullNameInput.value.trim().length < 3) {
            showError(fullNameInput, 'client_full_name_error', 'Full name must be at least 3 characters.');
            isValid = false;
        } else {
            clearError(fullNameInput, 'client_full_name_error');
        }

        // Email
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailInput.value.trim()) {
            showError(emailInput, 'client_email_error', 'Email address is required.');
            isValid = false;
        } else if (!emailRegex.test(emailInput.value.trim())) {
            showError(emailInput, 'client_email_error', 'Please enter a valid email format.');
            isValid = false;
        } else {
            clearError(emailInput, 'client_email_error');
        }

        // Mobile (10 digits)
        const mobileRegex = /^[6-9]\d{9}$/;
        if (!mobileInput.value.trim()) {
            showError(mobileInput, 'client_mobile_error', 'Mobile number is required.');
            isValid = false;
        } else if (!mobileRegex.test(mobileInput.value.trim())) {
            showError(mobileInput, 'client_mobile_error', 'Mobile number must be exactly 10 digits starting with 6-9.');
            isValid = false;
        } else {
            clearError(mobileInput, 'client_mobile_error');
        }

        // Password (min 8 chars)
        if (!passwordInput.value) {
            showError(passwordInput, 'client_password_error', 'Password is required.');
            isValid = false;
        } else if (passwordInput.value.length < 8) {
            showError(passwordInput, 'client_password_error', 'Password must be at least 8 characters long.');
            isValid = false;
        } else {
            clearError(passwordInput, 'client_password_error');
        }

        // Confirm Password
        if (!confirmPasswordInput.value) {
            showError(confirmPasswordInput, 'client_confirm_password_error', 'Please confirm your password.');
            isValid = false;
        } else if (confirmPasswordInput.value !== passwordInput.value) {
            showError(confirmPasswordInput, 'client_confirm_password_error', 'Passwords do not match.');
            isValid = false;
        } else {
            clearError(confirmPasswordInput, 'client_confirm_password_error');
        }

        if (!isValid) {
            e.preventDefault();
        } else {
            // Update button UI and prevent double clicks
            const submitBtn = document.getElementById('registerSubmitBtn');
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Creating Account...';
            setTimeout(() => { submitBtn.disabled = true; }, 10);
        }
    });
});
</script>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
