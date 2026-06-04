<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

require_once __DIR__ . '/public/database.config.php';

if (isset($_SESSION['account_id'])) {
    header("Location: " . BASE_URL . "/views/dashboard/dashboard.php");
    exit();
}

require_once __DIR__ . '/models/account.php';
require_once __DIR__ . '/controllers/account.php';

$errors  = "";
$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["login"])) {
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"]      ?? "";

    if (empty($username) || empty($password)) {
        $errors = "Please fill in all fields.";
    } else {
        $credentials = new Account($username, $password);
        $controller  = new AccountController($SERVER_NAME, $USERNAME, $PASSWORD, $DB_NAME);
        $result      = $controller->login($credentials->username, $credentials->password);

        if ($result) {
            header("Location: " . BASE_URL . "/views/dashboard/dashboard.php");
            exit();
        } else {
            $errors = "Invalid username or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Antrack — Sign In</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/styles.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        .split-layout {
            display: flex;
            min-height: 100vh;
        }

        /* LEFT BRAND PANEL */
        .brand-panel {
            flex: 1.1;
            background: linear-gradient(145deg, #1a0533 0%, #3b0764 40%, #5b21b6 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: flex-start;
            padding: 60px 64px;
            position: relative;
            overflow: hidden;
        }

        .brand-panel::before {
            content: '';
            position: absolute;
            top: -120px; right: -120px;
            width: 420px; height: 420px;
            border-radius: 50%;
            background: rgba(139,92,246,0.15);
        }

        .brand-panel::after {
            content: '';
            position: absolute;
            bottom: -80px; left: -80px;
            width: 300px; height: 300px;
            border-radius: 50%;
            background: rgba(139,92,246,0.1);
        }

        .brand-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 56px;
            z-index: 1;
        }

        .brand-logo-icon {
            width: 44px; height: 44px;
            background: rgba(255,255,255,0.15);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
        }

        .brand-logo-text {
            font-size: 1.4rem;
            font-weight: 800;
            color: white;
            letter-spacing: -0.5px;
        }

        .brand-illustration {
            z-index: 1;
            margin-bottom: 48px;
            width: 100%;
            max-width: 380px;
        }

        .brand-tagline {
            font-size: 2.6rem;
            font-weight: 800;
            color: white;
            line-height: 1.2;
            letter-spacing: -0.5px;
            margin-bottom: 16px;
            z-index: 1;
        }

        .brand-tagline span {
            color: #c4b5fd;
        }

        .brand-desc {
            font-size: 1rem;
            color: rgba(255,255,255,0.65);
            line-height: 1.6;
            max-width: 340px;
            z-index: 1;
        }

        .brand-features {
            margin-top: 40px;
            display: flex;
            flex-direction: column;
            gap: 14px;
            z-index: 1;
        }

        .brand-feature {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand-feature-dot {
            width: 32px; height: 32px;
            border-radius: 8px;
            background: rgba(255,255,255,0.12);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            flex-shrink: 0;
        }

        .brand-feature-text {
            font-size: 0.88rem;
            color: rgba(255,255,255,0.75);
        }

        /* RIGHT FORM PANEL */
        .form-panel {
            flex: 0.9;
            background: #fafafa;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 60px 48px;
        }

        .form-box {
            width: 100%;
            max-width: 400px;
        }

        .form-heading {
            font-size: 1.75rem;
            font-weight: 800;
            color: #1e1b4b;
            margin-bottom: 6px;
        }

        .form-subheading {
            font-size: 0.9rem;
            color: #6b7280;
            margin-bottom: 32px;
        }

        .form-group { margin-bottom: 16px; }

        .form-group label {
            display: block;
            font-size: 0.83rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 6px;
        }

        .form-group input {
            width: 100%;
            padding: 0.65rem 0.9rem;
            border: 1.5px solid #e5e7eb;
            border-radius: 10px;
            font-size: 0.9rem;
            font-family: inherit;
            background: white;
            color: #1e1b4b;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-group input:focus {
            border-color: #7c3aed;
            outline: none;
            box-shadow: 0 0 0 3px rgba(124,58,237,0.1);
        }

        .forgot-row {
            display: flex;
            justify-content: flex-start;
            margin-top: -8px;
            margin-bottom: 20px;
        }

        .forgot-link {
            font-size: 0.82rem;
            color: #7c3aed;
            text-decoration: none;
        }

        .forgot-link:hover { text-decoration: underline; }

        .submit-btn {
            width: 100%;
            padding: 0.75rem;
            background: linear-gradient(135deg, #5b21b6, #7c3aed);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            font-family: inherit;
            transition: all 0.2s;
            box-shadow: 0 4px 14px rgba(124,58,237,0.3);
        }

        .submit-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(124,58,237,0.4);
        }

        .divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 24px 0;
            color: #d1d5db;
            font-size: 0.8rem;
        }

        .divider::before, .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e5e7eb;
        }

        .register-row {
            text-align: center;
            font-size: 0.88rem;
            color: #6b7280;
        }

        .register-row a {
            color: #7c3aed;
            font-weight: 700;
            text-decoration: none;
        }

        .register-row a:hover { text-decoration: underline; }

        .alert {
            padding: 0.75rem 1rem;
            border-radius: 10px;
            margin-bottom: 16px;
            font-size: 0.88rem;
            font-weight: 500;
        }

        .alert-danger  { background:#fff1f2; color:#9f1239; border-left:4px solid #e11d48; }
        .alert-success { background:#ecfdf5; color:#065f46; border-left:4px solid #059669; }

        @media (max-width: 768px) {
            .split-layout { flex-direction: column; }
            .brand-panel {
                padding: 40px 28px 36px;
                align-items: center;
                text-align: center;
            }
            .brand-tagline { font-size: 1.8rem; }
            .brand-desc { max-width: 100%; }
            .brand-illustration { display: none; }
            .brand-features { display: none; }
            .form-panel { padding: 40px 24px; }
        }
    </style>
</head>
<body>

<div class="split-layout">

    <!-- LEFT: Brand Panel -->
    <div class="brand-panel">

        <div class="brand-logo">
            <div class="brand-logo-icon">🐜</div>
            <span class="brand-logo-text">Antrack</span>
        </div>

        <!-- Illustration -->
        <div class="brand-illustration">
            <img
                src="<?= BASE_URL ?>/public/images/logo.png"
                alt="Antrack — task tracking illustration"
                style="width:100%; max-width:380px; border-radius:16px; opacity:0.92;"
            >
        </div>

        <h1 class="brand-tagline">Small steps.<br><span>Big progress.</span></h1>
        <p class="brand-desc">The ant-idote to overwhelming to-do lists — track every task, one step at a time.</p>

        <div class="brand-features">
            <div class="brand-feature">
                <div class="brand-feature-dot">📋</div>
                <span class="brand-feature-text">Organize tasks by priority and category</span>
            </div>
            <div class="brand-feature">
                <div class="brand-feature-dot">📅</div>
                <span class="brand-feature-text">Calendar view to never miss a deadline</span>
            </div>
            <div class="brand-feature">
                <div class="brand-feature-dot">📊</div>
                <span class="brand-feature-text">Analytics to track your productivity</span>
            </div>
        </div>

    </div>

    <!-- RIGHT: Login Form -->
    <div class="form-panel">
        <div class="form-box">

            <h2 class="form-heading">Welcome back</h2>
            <p class="form-subheading">Sign in to your Antrack account</p>

            <?php if (!empty($message)): ?>
                <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($errors) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username"
                        placeholder="Enter your username"
                        value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                        required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password"
                        placeholder="Enter your password" required>
                </div>

                <div class="forgot-row">
                    <a href="<?= BASE_URL ?>/views/auth/forgot-password.php" class="forgot-link">
                        Forgot password?
                    </a>
                </div>

                <button type="submit" name="login" class="submit-btn">Sign In</button>
            </form>

            <div class="divider">or</div>

            <div class="register-row">
                Don't have an account?
                <a href="<?= BASE_URL ?>/views/auth/register.php">Create one free</a>
            </div>

        </div>
    </div>

</div>

</body>
</html>