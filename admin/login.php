<?php
// Admin login

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';

// If already authenticated as admin, jump straight to the admin dashboard
if (isAdmin()) {
    header('Location: ' . APP_URL . '/admin/dashboard.php');
    exit;
}

$errorMessage = '';
$usernameOrEmail = '';

// Handle POST authentication request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!validateCSRFToken($submittedToken)) {
        $errorMessage = 'Security validation failed (Invalid CSRF Token). Please refresh and try again.';
    } else {
        $usernameOrEmail = sanitize($_POST['username_or_email'] ?? '');
        $password = (string)($_POST['password'] ?? '');

        if (empty($usernameOrEmail) || empty($password)) {
            $errorMessage = 'Please provide both your Username/Email and Password.';
        } else {
            try {
                $pdo = getDBConnection();
                $stmt = $pdo->prepare("SELECT * FROM admins WHERE (username = ? OR email = ?) LIMIT 1");
                $stmt->execute([$usernameOrEmail, $usernameOrEmail]);
                $admin = $stmt->fetch();

                if (!$admin) {
                    $errorMessage = 'Invalid administrative credentials provided.';
                } elseif (!password_verify($password, $admin['password_hash'])) {
                    $errorMessage = 'Invalid administrative credentials provided.';
                } elseif ($admin['status'] !== 'active') {
                    $errorMessage = 'Your administrator account has been deactivated. Please contact the Super Admin.';
                } else {
                    // Password verified and account active - Initialize secure admin session
                    session_regenerate_id(true);

                    $_SESSION['admin_id'] = (int)$admin['id'];
                    $_SESSION['admin_name'] = $admin['full_name'];
                    $_SESSION['admin_username'] = $admin['username'];
                    $_SESSION['admin_email'] = $admin['email'];
                    $_SESSION['admin_role'] = $admin['role'];

                    if (function_exists('setFlashMessage')) {
                        setFlashMessage('success', 'Welcome back, ' . htmlspecialchars($admin['full_name'], ENT_QUOTES, 'UTF-8') . '! Signed in as ' . ucfirst($admin['role']) . '.');
                    }

                    header('Location: ' . APP_URL . '/admin/dashboard.php');
                    exit;
                }
            } catch (PDOException $e) {
                error_log("Admin login error: " . $e->getMessage());
                $errorMessage = 'A database error occurred during authentication. Please verify MySQL service status.';
            }
        }
    }
}

$flash = function_exists('getFlashMessage') ? getFlashMessage() : null;
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Admin Sign In | PayBen Administration Console</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Fintech CSS -->
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/style.css">

    <style>
        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: radial-gradient(circle at 50% 20%, #1e1b4b 0%, #0f172a 45%, #030712 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #f3f4f6;
            margin: 0;
            padding: 1.5rem;
        }

        .admin-login-wrapper {
            width: 100%;
            max-width: 440px;
        }

        .admin-login-card {
            background: rgba(17, 24, 39, 0.85);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7), 0 0 35px rgba(79, 70, 229, 0.15);
            padding: 2.25rem;
        }

        .brand-badge {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 1.75rem;
            box-shadow: 0 8px 24px rgba(79, 70, 229, 0.45);
        }

        .form-control {
            background-color: #0b0f19 !important;
            border: 1px solid #374151 !important;
            color: #ffffff !important;
            padding: 0.75rem 1rem;
            border-radius: 10px;
            transition: all 0.2s ease;
        }

        .form-control:focus {
            border-color: #6366f1 !important;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.25) !important;
        }

        .input-group-text {
            background-color: #0b0f19;
            border: 1px solid #374151;
            color: #9ca3af;
            border-radius: 10px;
        }

        .btn-admin-submit {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            border: none;
            color: #ffffff;
            font-weight: 600;
            padding: 0.85rem 1.5rem;
            border-radius: 10px;
            transition: all 0.25s ease;
            box-shadow: 0 4px 16px rgba(79, 70, 229, 0.4);
        }

        .btn-admin-submit:hover {
            background: linear-gradient(135deg, #4338ca 0%, #6d28d9 100%);
            transform: translateY(-1px);
            box-shadow: 0 6px 22px rgba(79, 70, 229, 0.55);
            color: #ffffff;
        }

        .demo-credential-chip {
            background: rgba(255, 255, 255, 0.04);
            border: 1px dashed rgba(255, 255, 255, 0.15);
            border-radius: 10px;
            padding: 0.75rem 1rem;
            font-size: 0.8rem;
            color: #9ca3af;
        }
    </style>
</head>
<body>

<div class="admin-login-wrapper">
    <!-- Top Header Branding -->
    <div class="text-center mb-4">
        <div class="brand-badge mb-3">
            <i class="bi bi-shield-lock-fill"></i>
        </div>
        <h3 class="fw-bold text-white mb-1 tracking-tight">Pay<span class="text-primary">Ben</span> Admin</h3>
        <p class="text-secondary small mb-0">Authorized Administrator Access Gateway</p>
    </div>

    <!-- Login Card -->
    <div class="admin-login-card">
        <!-- Flash Alert -->
        <?php if (!empty($flash)): ?>
            <div class="alert alert-<?php echo htmlspecialchars($flash['type'] ?? 'info', ENT_QUOTES, 'UTF-8'); ?> alert-dismissible fade show rounded-3 small py-2 px-3 mb-3 border-0" role="alert">
                <i class="bi bi-info-circle me-1"></i>
                <?php echo htmlspecialchars($flash['message'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Error Message Alert -->
        <?php if (!empty($errorMessage)): ?>
            <div class="alert alert-danger rounded-3 small py-2 px-3 mb-3 border-0 d-flex align-items-center gap-2" role="alert">
                <i class="bi bi-exclamation-triangle-fill fs-6 flex-shrink-0"></i>
                <div><?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>" novalidate autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCSRFToken(), ENT_QUOTES, 'UTF-8'); ?>">

            <!-- Username or Email Field -->
            <div class="mb-3">
                <label for="username_or_email" class="form-label text-light small fw-semibold">Username or Email</label>
                <div class="input-group">
                    <span class="input-group-text border-end-0">
                        <i class="bi bi-person-fill"></i>
                    </span>
                    <input type="text" 
                           class="form-control border-start-0 ps-0" 
                           id="username_or_email" 
                           name="username_or_email" 
                           placeholder="admin or admin@payben.com" 
                           value="<?php echo htmlspecialchars($usernameOrEmail, ENT_QUOTES, 'UTF-8'); ?>" 
                           required 
                           autofocus>
                </div>
            </div>

            <!-- Password Field -->
            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label for="password" class="form-label text-light small fw-semibold mb-0">Password</label>
                </div>
                <div class="input-group">
                    <span class="input-group-text border-end-0">
                        <i class="bi bi-key-fill"></i>
                    </span>
                    <input type="password" 
                           class="form-control border-start-0 border-end-0 ps-0" 
                           id="password" 
                           name="password" 
                           placeholder="Enter admin password" 
                           required>
                    <button class="btn btn-outline-secondary border-start-0" type="button" id="togglePasswordBtn" style="border-color: #374151; background: #0b0f19;" aria-label="Toggle password visibility">
                        <i class="bi bi-eye" id="togglePasswordIcon"></i>
                    </button>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="d-grid mb-3">
                <button type="submit" class="btn btn-admin-submit d-flex align-items-center justify-content-center gap-2">
                    <i class="bi bi-shield-check"></i>
                    <span>Secure Admin Login</span>
                </button>
            </div>
        </form>

        <!-- Demo Credentials Helper -->
        <div class="demo-credential-chip text-center mt-3">
            <div class="fw-semibold text-white mb-1"><i class="bi bi-key me-1 text-primary"></i> Default Admin Credentials:</div>
            <div>Username: <code class="text-info">admin</code> or <code class="text-info">admin@payben.com</code></div>
            <div>Password: <code class="text-warning">admin123</code></div>
        </div>
    </div>

    <!-- Back to Main Site Link -->
    <div class="text-center mt-4">
        <a href="<?php echo APP_URL; ?>/index.php" class="text-secondary text-decoration-none small hover-white d-inline-flex align-items-center gap-1">
            <i class="bi bi-arrow-left"></i>
            <span>Return to Customer Site</span>
        </a>
    </div>

    <!-- Security Footer Badges -->
    <div class="text-center mt-3">
        <span class="badge bg-dark-subtle text-secondary border border-secondary border-opacity-25 px-2 py-1 small">
            <i class="bi bi-lock-fill text-success me-1"></i> End-to-End Encrypted &bull; 256-Bit SSL
        </span>
    </div>
</div>

<!-- Bootstrap 5.3 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Toggle Password Visibility
    const toggleBtn = document.getElementById('togglePasswordBtn');
    const passwordInput = document.getElementById('password');
    const toggleIcon = document.getElementById('togglePasswordIcon');

    if (toggleBtn && passwordInput && toggleIcon) {
        toggleBtn.addEventListener('click', function () {
            const isPassword = passwordInput.getAttribute('type') === 'password';
            passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
            toggleIcon.classList.toggle('bi-eye', !isPassword);
            toggleIcon.classList.toggle('bi-eye-slash', isPassword);
        });
    }
</script>

</body>
</html>
