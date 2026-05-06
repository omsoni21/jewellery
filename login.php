<?php

/**
 * Login Page
 */

require_once __DIR__ . '/includes/functions.php';
// ✅ SAFE fallback: agar logActivity exist nahi hai to create karo
if (!function_exists('logActivity')) {
    function logActivity($type, $message)
    {
        $file = __DIR__ . '/activity.log';
        $date = date('Y-m-d H:i:s');
        file_put_contents($file, "[$date] [$type] $message\n", FILE_APPEND);
    }
}

// Redirect if already logged in
if (isLoggedIn()) {
    header("Location: " . BASE_URL . "/dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        $db = getDBConnection();
        $stmt = $db->prepare("SELECT id, username, password_hash, email, full_name, role, is_active FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            if (!$user['is_active']) {
                $error = 'Your account has been deactivated. Please contact administrator.';
            } else {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['last_activity'] = time();

                // Update last login
                $stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $stmt->execute([$user['id']]);

                // Log activity
                logActivity('login', 'User logged in successfully');

                header("Location: " . BASE_URL . "/dashboard.php");
                exit();
            }
        } else {
            $error = 'Invalid username or password.';
            logActivity('login_failed', "Failed login attempt for username: $username");
        }
    }
}

// Check for timeout message
if (isset($_GET['timeout']) && $_GET['timeout'] == 1) {
    $error = 'Your session has expired. Please login again.';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            display: flex;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            position: relative;
            overflow-x: hidden;
        }

        /* Animated background particles */
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            overflow: hidden;
            z-index: 0;
        }

        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: rgba(255, 215, 0, 0.3);
            border-radius: 50%;
            animation: float 15s infinite;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(100vh) rotate(0deg);
                opacity: 0;
            }

            10% {
                opacity: 1;
            }

            90% {
                opacity: 1;
            }

            100% {
                transform: translateY(-100vh) rotate(720deg);
                opacity: 0;
            }
        }

        /* Left side - Branding */
        .brand-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 3rem;
            position: relative;
            z-index: 1;
        }

        .brand-content {
            text-align: center;
            color: white;
            max-width: 500px;
        }

        .logo-container {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            box-shadow: 0 20px 40px rgba(255, 215, 0, 0.3), 0 0 60px rgba(255, 215, 0, 0.2);
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.05);
            }
        }

        .logo-container i {
            font-size: 3.5rem;
            color: #1a1a2e;
        }

        .brand-title {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 50%, #FFD700 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .brand-subtitle {
            font-size: 1.1rem;
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 2rem;
        }

        .features {
            display: flex;
            gap: 2rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .feature-item {
            text-align: center;
            padding: 1rem;
        }

        .feature-item i {
            font-size: 1.5rem;
            color: #FFD700;
            margin-bottom: 0.5rem;
            display: block;
        }

        .feature-item span {
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.8);
        }

        /* Right side - Login Form */
        .login-section {
            width: 100%;
            max-width: 480px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            position: relative;
            z-index: 1;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 2.5rem;
            width: 100%;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255, 255, 255, 0.1);
            animation: slideIn 0.6s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(30px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-header h2 {
            font-family: 'Playfair Display', serif;
            font-size: 1.75rem;
            color: #1a1a2e;
            margin-bottom: 0.5rem;
        }

        .login-header p {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .form-floating {
            position: relative;
            margin-bottom: 1.25rem;
        }

        .form-floating>.form-control {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            height: 56px;
            padding-left: 3rem;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .form-floating>.form-control:focus {
            border-color: #FFD700;
            box-shadow: 0 0 0 4px rgba(255, 215, 0, 0.1);
        }

        .form-floating>label {
            padding-left: 3rem;
            color: #6c757d;
        }

        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #adb5bd;
            font-size: 1.1rem;
            z-index: 2;
            transition: color 0.3s ease;
        }

        .form-floating>.form-control:focus~.input-icon {
            color: #FFD700;
        }

        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #adb5bd;
            cursor: pointer;
            z-index: 2;
            padding: 0.25rem;
            transition: color 0.3s ease;
        }

        .password-toggle:hover {
            color: #FFD700;
        }

        .btn-login {
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
            border: none;
            border-radius: 12px;
            padding: 1rem;
            font-weight: 600;
            font-size: 1rem;
            color: #1a1a2e;
            width: 100%;
            margin-top: 0.5rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.4);
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 215, 0, 0.5);
            color: #1a1a2e;
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .alert {
            border: none;
            border-radius: 12px;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
        }

        .alert-danger {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a5a 100%);
            color: white;
        }

        .divider {
            display: flex;
            align-items: center;
            margin: 1.5rem 0;
            color: #adb5bd;
            font-size: 0.85rem;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: linear-gradient(90deg, transparent, #dee2e6, transparent);
        }

        .divider span {
            padding: 0 1rem;
        }

        .default-login {
            text-align: center;
            padding: 1rem;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 12px;
            border: 1px dashed #dee2e6;
        }

        .default-login small {
            color: #6c757d;
            font-size: 0.85rem;
        }

        .default-login strong {
            color: #1a1a2e;
        }

        /* Responsive */
        @media (max-width: 991px) {
            body {
                flex-direction: column;
            }

            .brand-section {
                padding: 2rem 1.5rem;
                min-height: auto;
            }

            .brand-title {
                font-size: 2rem;
            }

            .features {
                gap: 1rem;
            }

            .login-section {
                max-width: 100%;
                padding: 1.5rem;
            }

            .login-card {
                padding: 2rem;
            }
        }

        @media (max-width: 576px) {
            .brand-title {
                font-size: 1.75rem;
            }

            .logo-container {
                width: 100px;
                height: 100px;
            }

            .logo-container i {
                font-size: 2.5rem;
            }

            .login-card {
                padding: 1.5rem;
                border-radius: 20px;
            }
        }
    </style>
</head>

<body>
    <!-- Animated Particles -->
    <div class="particles" id="particles"></div>

    <!-- Brand Section -->
    <div class="brand-section">
        <div class="brand-content">
            <div class="logo-container">
                <i class="bi bi-gem"></i>
            </div>
            <h1 class="brand-title"><?php echo APP_NAME; ?></h1>
            <p class="brand-subtitle">JewelSync ERP - Premium Jewellery Management</p>

            <div class="features">
                <div class="feature-item">
                    <i class="bi bi-receipt"></i>
                    <span>GST Invoicing</span>
                </div>
                <div class="feature-item">
                    <i class="bi bi-box-seam"></i>
                    <span>Inventory</span>
                </div>
                <div class="feature-item">
                    <i class="bi bi-graph-up"></i>
                    <span>Reports</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Login Section -->
    <div class="login-section">
        <div class="login-card">
            <div class="login-header">
                <h2>Welcome Back</h2>
                <p>Sign in to access your dashboard</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-circle-fill me-2"></i>
                    <?php echo $error; ?>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="loginForm">
                <div class="form-floating">
                    <i class="bi bi-person input-icon"></i>
                    <input type="text" class="form-control" id="username" name="username" placeholder="Username" required autofocus>
                    <label for="username">Username</label>
                </div>

                <div class="form-floating">
                    <i class="bi bi-lock input-icon"></i>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                    <label for="password">Password</label>
                    <button type="button" class="password-toggle" onclick="togglePassword()">
                        <i class="bi bi-eye" id="toggleIcon"></i>
                    </button>
                </div>

                <button type="submit" class="btn btn-login">
                    <i class="bi bi-box-arrow-in-right me-2"></i> Sign In
                </button>
            </form>


        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Create floating particles
        function createParticles() {
            const container = document.getElementById('particles');
            const particleCount = 30;

            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 15 + 's';
                particle.style.animationDuration = (10 + Math.random() * 10) + 's';
                container.appendChild(particle);
            }
        }

        createParticles();

        // Toggle password visibility
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('bi-eye');
                toggleIcon.classList.add('bi-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('bi-eye-slash');
                toggleIcon.classList.add('bi-eye');
            }
        }

        // Add subtle animation on input focus
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.02)';
            });
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });
        });
    </script>
</body>

</html>