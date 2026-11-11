<?php
// Login page

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: ' . APP_URL . '/dashboard.php');
    exit;
}

$errors = [];
$email = $_COOKIE['payben_remember_email'] ?? '';
$rememberMe = !empty($email);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!validateCSRFToken($csrfToken)) {
        $errors['general'] = 'Security verification failed (invalid CSRF token). Please refresh the page.';
    }

    $email = trim(filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL));
    $password = $_POST['password'] ?? '';
    $rememberMe = !empty($_POST['remember_me']);

    if (empty($email)) {
        $errors['email'] = 'Email address is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email format.';
    }

    if (empty($password)) {
        $errors['password'] = 'Password is required.';
    }

    if (empty($errors)) {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("SELECT id, full_name, email, mobile, password_hash, status FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                if (strtolower($user['status']) !== 'active') {
                    $errors['general'] = 'Your account has been deactivated or suspended. Please contact customer support.';
                } else {
                    if (session_status() === PHP_SESSION_NONE) {
                        session_start();
                    }
                    session_regenerate_id(true);

                    $_SESSION['user_id'] = (int)$user['id'];
                    $_SESSION['user_name'] = $user['full_name'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_mobile'] = $user['mobile'];

                    if ($rememberMe) {
                        setcookie('payben_remember_email', $email, time() + (86400 * 30), '/', '', false, true);
                    } else {
                        if (isset($_COOKIE['payben_remember_email'])) {
                            setcookie('payben_remember_email', '', time() - 3600, '/');
                        }
                    }

                    setFlashMessage('success', 'Welcome back, ' . $user['full_name'] . '!');

                    $redirectTarget = $_SESSION['redirect_url'] ?? (APP_URL . '/dashboard.php');
                    unset($_SESSION['redirect_url']);

                    header('Location: ' . $redirectTarget);
                    exit;
                }
            } else {
                $errors['general'] = 'Invalid email address or password. Please verify and try again.';
            }
        } catch (PDOException $e) {
            error_log("Login authentication error: " . $e->getMessage());
            $errors['general'] = 'A temporary database error occurred. Please try again in a few moments.';
        }
    }
}

$pageTitle = 'Sign In to Your Account';
require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-page-wrapper py-5" style="background: linear-gradient(135deg, #f8fafd 0%, #e8f0fe 100%); min-height: calc(100vh - 180px); display: flex; align-items: center;">
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-5 col-xl-4">

                <div class="card border-0 shadow-sm rounded-4 overflow-hidden" style="background: #ffffff; border: 1px solid #dadce0 !important;">
                    <div class="card-header bg-white border-0 pt-4 pb-2 px-4 text-center">
                        <div class="rounded-3 mx-auto mb-3 d-flex align-items-center justify-content-center shadow-sm text-white" 
                             style="width: 58px; height: 58px; background: linear-gradient(135deg, #2563eb 0%, #10b981 100%);">
                            <i class="bi bi-lightning-charge-fill fs-2"></i>
                        </div>
                        <h4 class="fw-bold text-dark mb-1">
                            Pay<span style="color: #2563eb;">Ben</span>
                        </h4>
                        <p class="text-muted small mb-0">Smart UPI &amp; Instant Bill Payment Platform</p>
                    </div>

                    <div class="card-body p-4 pt-2">
                        <?php if (!empty($errors['general'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 small rounded-3 py-2" role="alert">
                                <i class="bi bi-exclamation-octagon-fill fs-6 text-danger"></i>
                                <div><?php echo htmlspecialchars($errors['general'], ENT_QUOTES, 'UTF-8'); ?></div>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <div class="p-3 bg-light rounded-3 border mb-3 small">
                            <div class="d-flex align-items-center gap-1 text-primary fw-bold mb-1">
                                <i class="bi bi-info-circle-fill"></i> Demo User Credentials:
                            </div>
                            <div class="text-muted font-monospace" style="font-size: 0.8rem;">
                                Email: <strong>user1@test.com</strong><br>
                                Password: <strong>password123</strong>
                            </div>
                        </div>

                        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>" method="POST" id="loginForm" novalidate>
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCSRFToken(), ENT_QUOTES, 'UTF-8'); ?>">

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
                                        placeholder="user@example.com" 
                                        value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>" 
                                        required 
                                        autocomplete="email"
                                    >
                                    <?php if (isset($errors['email'])): ?>
                                        <div class="invalid-feedback d-block"><?php echo htmlspecialchars($errors['email'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="invalid-feedback" id="client_email_error"></div>
                            </div>

                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label for="password" class="form-label small fw-semibold text-secondary mb-0">Password</label>
                                </div>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted">
                                        <i class="bi bi-lock"></i>
                                    </span>
                                    <input 
                                        type="password" 
                                        name="password" 
                                        id="password" 
                                        class="form-control border-start-0 border-end-0 <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>" 
                                        placeholder="Enter your password" 
                                        required 
                                        autocomplete="current-password"
                                    >
                                    <button class="btn btn-light border border-start-0 text-muted toggle-password" type="button" tabindex="-1">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <?php if (isset($errors['password'])): ?>
                                        <div class="invalid-feedback d-block"><?php echo htmlspecialchars($errors['password'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="invalid-feedback" id="client_password_error"></div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <div class="form-check">
                                    <input 
                                        type="checkbox" 
                                        class="form-check-input" 
                                        id="remember_me" 
                                        name="remember_me" 
                                        value="1" 
                                        <?php echo $rememberMe ? 'checked' : ''; ?>
                                    >
                                    <label class="form-check-label small text-secondary" for="remember_me">
                                        Remember Me
                                    </label>
                                </div>
                                <span class="small text-muted">
                                    <i class="bi bi-shield-check text-success"></i> Secure SSL
                                </span>
                            </div>

                            <button type="submit" class="btn btn-gpay w-100 py-2 fs-6 rounded-pill fw-bold shadow-sm" id="loginSubmitBtn">
                                <i class="bi bi-box-arrow-in-right me-1"></i> Sign In with PayBen
                            </button>
                        </form>
                    </div>

                    <div class="card-footer bg-light border-0 py-3 text-center">
                        <span class="small text-muted">Don't have an account yet?</span>
                        <a href="<?php echo APP_URL; ?>/register.php" class="small fw-bold text-primary text-decoration-none ms-1">
                            Register Now <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>

                <div class="text-center mt-3">
                    <a href="<?php echo APP_URL; ?>/admin/login.php" class="text-white-50 text-decoration-none small">
                        <i class="bi bi-shield-lock me-1"></i> Access Admin Portal
                    </a>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggleBtn = document.querySelector('.toggle-password');
    const passwordInput = document.getElementById('password');
    if (toggleBtn && passwordInput) {
        toggleBtn.addEventListener('click', function() {
            const icon = this.querySelector('i');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        });
    }

    const form = document.getElementById('loginForm');
    const emailInput = document.getElementById('email');

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

    form.addEventListener('submit', function(e) {
        let isValid = true;

        // Email
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailInput.value.trim()) {
            showError(emailInput, 'client_email_error', 'Email address is required.');
            isValid = false;
        } else if (!emailRegex.test(emailInput.value.trim())) {
            showError(emailInput, 'client_email_error', 'Please enter a valid email address.');
            isValid = false;
        } else {
            clearError(emailInput, 'client_email_error');
        }

        // Password
        if (!passwordInput.value) {
            showError(passwordInput, 'client_password_error', 'Password is required.');
            isValid = false;
        } else {
            clearError(passwordInput, 'client_password_error');
        }

        if (!isValid) {
            e.preventDefault();
        } else {
            const submitBtn = document.getElementById('loginSubmitBtn');
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Signing In...';
            setTimeout(() => { submitBtn.disabled = true; }, 10);
        }
    });
});
</script>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
