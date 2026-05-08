<?php

/**
 * Premium Login Page
 */

require_once __DIR__ . '/includes/functions.php';

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

        $stmt = $db->prepare("
            SELECT id, username, password_hash, email, full_name, role, is_active
            FROM users
            WHERE username = ?
        ");

        $stmt->execute([$username]);

        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {

            if (!$user['is_active']) {

                $error = 'Your account has been deactivated.';

            } else {

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['last_activity'] = time();

                // Update login time
                $stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $stmt->execute([$user['id']]);

                logActivity('login', 'User logged in successfully');

                header("Location: " . BASE_URL . "/dashboard.php");
                exit();
            }

        } else {

            $error = 'Invalid username or password.';
            logActivity('login_failed', "Failed login for: $username");
        }
    }
}

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

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Google Fonts -->
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
            overflow-x: hidden;
            background:
                radial-gradient(circle at top left, rgba(255, 215, 0, 0.12), transparent 30%),
                radial-gradient(circle at bottom right, rgba(255, 140, 0, 0.15), transparent 35%),
                linear-gradient(135deg, #0f172a 0%, #111827 45%, #1e293b 100%);
            position: relative;
        }

        body::before,
        body::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            z-index: 0;
        }

        body::before {
            width: 300px;
            height: 300px;
            background: rgba(255, 215, 0, 0.18);
            top: -100px;
            left: -100px;
        }

        body::after {
            width: 350px;
            height: 350px;
            background: rgba(255, 140, 0, 0.12);
            bottom: -120px;
            right: -120px;
        }

        /* Particles */

        .particles {
            position: fixed;
            width: 100%;
            height: 100%;
            overflow: hidden;
            pointer-events: none;
            z-index: 0;
        }

        .particle {
            position: absolute;
            width: 5px;
            height: 5px;
            background: rgba(255, 215, 0, 0.4);
            border-radius: 50%;
            animation: float 16s linear infinite;
        }

        @keyframes float {
            0% {
                transform: translateY(100vh) scale(0);
                opacity: 0;
            }

            10% {
                opacity: 1;
            }

            100% {
                transform: translateY(-100vh) scale(1);
                opacity: 0;
            }
        }

        /* Brand Section */

        .brand-section {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem;
            position: relative;
            z-index: 1;
        }

        .brand-content {
            max-width: 520px;
            text-align: center;
        }

        .logo-container {
            width: 130px;
            height: 130px;
            margin: 0 auto 2rem;
            border-radius: 50%;
            background: linear-gradient(135deg, #FFD700, #FFB800);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow:
                0 10px 40px rgba(255, 215, 0, 0.35),
                0 0 80px rgba(255, 215, 0, 0.2);
            animation: pulse 3s infinite ease-in-out;
        }

        .logo-container i {
            font-size: 3.8rem;
            color: #111827;
        }

        @keyframes pulse {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.06);
            }
        }

        .brand-title {
            font-family: 'Playfair Display', serif;
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 1rem;
            background: linear-gradient(90deg, #FFD700, #fff0a5, #FFB800);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .brand-subtitle {
            color: rgba(255, 255, 255, 0.75);
            font-size: 1.05rem;
            line-height: 1.8;
            margin-bottom: 2.5rem;
        }

        .features {
            display: flex;
            justify-content: center;
            gap: 1.8rem;
            flex-wrap: wrap;
        }

        .feature-item {
            width: 120px;
            padding: 1rem;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(10px);
            transition: 0.3s;
        }

        .feature-item:hover {
            transform: translateY(-6px);
        }

        .feature-item i {
            font-size: 1.7rem;
            color: #FFD700;
            margin-bottom: 0.7rem;
            display: block;
        }

        .feature-item span {
            color: rgba(255, 255, 255, 0.85);
            font-size: 0.88rem;
            font-weight: 500;
        }

        /* Login Section */

        .login-section {
            width: 100%;
            max-width: 500px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            position: relative;
            z-index: 1;
        }

        .login-card {
            width: 100%;
            padding: 2.8rem;
            border-radius: 28px;
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            box-shadow:
                0 20px 60px rgba(0, 0, 0, 0.35);
            animation: slideIn 0.7s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(40px);
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
            font-size: 2rem;
            color: white;
            margin-bottom: 0.5rem;
        }

        .login-header p {
            color: rgba(255, 255, 255, 0.7);
        }

        /* Form */

        .form-floating {
            position: relative;
            margin-bottom: 1.4rem;
        }

        .form-floating>.form-control {
            height: 60px;
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.15);
            background: rgba(255, 255, 255, 0.08);
            color: white;
            padding-left: 3.2rem;
            font-size: 0.95rem;
        }

        .form-floating>.form-control:focus {
            border-color: #FFD700;
            box-shadow: 0 0 0 4px rgba(255, 215, 0, 0.15);
            background: rgba(255, 255, 255, 0.12);
            color: white;
        }

        .form-floating>label {
            padding-left: 3.2rem;
            color: rgba(255, 255, 255, 0.6);
        }

        .input-icon {
            position: absolute;
            top: 50%;
            left: 1.1rem;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.55);
            z-index: 10;
            font-size: 1.1rem;
        }

        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            background: transparent;
            color: rgba(255, 255, 255, 0.6);
            z-index: 10;
            cursor: pointer;
        }

        .password-toggle:hover {
            color: #FFD700;
        }

        /* Button */

        .btn-login {
            width: 100%;
            border: none;
            border-radius: 16px;
            padding: 1rem;
            margin-top: 0.5rem;
            background: linear-gradient(135deg, #FFD700, #FFB800);
            color: #111827;
            font-weight: 700;
            font-size: 1rem;
            transition: 0.3s;
            box-shadow: 0 8px 25px rgba(255, 215, 0, 0.3);
        }

        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 14px 35px rgba(255, 215, 0, 0.4);
        }

        .alert {
            border: none;
            border-radius: 14px;
        }

        .alert-danger {
            background: rgba(255, 0, 0, 0.15);
            color: white;
            backdrop-filter: blur(10px);
        }

        /* Responsive */

        @media (max-width: 991px) {

            body {
                flex-direction: column;
            }

            .brand-section {
                padding: 2rem 1.5rem;
            }

            .brand-title {
                font-size: 2.3rem;
            }

            .login-section {
                max-width: 100%;
            }
        }

        @media (max-width: 576px) {

            .brand-title {
                font-size: 2rem;
            }

            .logo-container {
                width: 100px;
                height: 100px;
            }

            .logo-container i {
                font-size: 3rem;
            }

            .login-card {
                padding: 1.5rem;
            }

            .feature-item {
                width: 100px;
            }
        }
    </style>

</head>

<body>

    <!-- Particles -->
    <div class="particles" id="particles"></div>

    <!-- Brand Section -->
    <div class="brand-section">

        <div class="brand-content">

            <div class="logo-container">
                <i class="bi bi-gem"></i>
            </div>

            <h1 class="brand-title"><?php echo APP_NAME; ?></h1>

            <p class="brand-subtitle">
                Premium Jewellery ERP Management System
            </p>

            <div class="features">

                <div class="feature-item">
                    <i class="bi bi-receipt"></i>
                    <span>GST Billing</span>
                </div>

                <div class="feature-item">
                    <i class="bi bi-box-seam"></i>
                    <span>Inventory</span>
                </div>

                <div class="feature-item">
                    <i class="bi bi-bar-chart"></i>
                    <span>Analytics</span>
                </div>

            </div>

        </div>

    </div>

    <!-- Login Section -->

    <div class="login-section">

        <div class="login-card">

            <div class="login-header">
                <h2>Welcome Back</h2>
                <p>Login to continue your dashboard</p>
            </div>

            <?php if ($error): ?>

                <div class="alert alert-danger alert-dismissible fade show">

                    <i class="bi bi-exclamation-circle-fill me-2"></i>

                    <?php echo $error; ?>

                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>

                </div>

            <?php endif; ?>

            <form method="POST">

                <!-- Username -->

                <div class="form-floating">

                    <i class="bi bi-person input-icon"></i>

                    <input
                        type="text"
                        class="form-control"
                        id="username"
                        name="username"
                        placeholder="Username"
                        required>

                    <label for="username">Username</label>

                </div>

                <!-- Password -->

                <div class="form-floating">

                    <i class="bi bi-lock input-icon"></i>

                    <input
                        type="password"
                        class="form-control"
                        id="password"
                        name="password"
                        placeholder="Password"
                        required>

                    <label for="password">Password</label>

                    <button type="button" class="password-toggle" onclick="togglePassword()">

                        <i class="bi bi-eye" id="toggleIcon"></i>

                    </button>

                </div>

                <!-- Login Button -->

                <button type="submit" class="btn btn-login">

                    <i class="bi bi-box-arrow-in-right me-2"></i>

                    Sign In

                </button>

            </form>

        </div>

    </div>

    <!-- Bootstrap JS -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>

        // Floating particles

        function createParticles() {

            const container = document.getElementById('particles');

            for (let i = 0; i < 30; i++) {

                const particle = document.createElement('div');

                particle.className = 'particle';

                particle.style.left = Math.random() * 100 + '%';

                particle.style.animationDelay = Math.random() * 10 + 's';

                particle.style.animationDuration = (10 + Math.random() * 10) + 's';

                container.appendChild(particle);
            }
        }

        createParticles();

        // Password toggle

        function togglePassword() {

            const password = document.getElementById('password');

            const icon = document.getElementById('toggleIcon');

            if (password.type === 'password') {

                password.type = 'text';

                icon.classList.remove('bi-eye');

                icon.classList.add('bi-eye-slash');

            } else {

                password.type = 'password';

                icon.classList.remove('bi-eye-slash');

                icon.classList.add('bi-eye');
            }
        }

    </script>

</body>

</html>