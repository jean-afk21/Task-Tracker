<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

require_once __DIR__ . '/../../public/database.config.php';
require_once __DIR__ . '/../../public/mailer.php';
require_once __DIR__ . '/../../controllers/account.php';

if (isset($_SESSION['account_id'])) {
    header("Location: " . BASE_URL . "/views/dashboard/dashboard.php");
    exit();
}

$errors    = "";
$message   = "";
$resetLink = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["forgot"])) {
    $email = trim($_POST["email"] ?? "");

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors = "Please enter a valid email address.";
    } else {
        $controller = new AccountController($SERVER_NAME, $USERNAME, $PASSWORD, $DB_NAME);
        $token      = $controller->forgotPassword($email);

        if ($token) {
            $appUrl    = getenv('APP_URL') ?: BASE_URL;
            $resetLink = $appUrl . "/views/auth/reset-password.php?token=" . $token;

            // Try to send email
            sendResetEmail($email, $resetLink);

            $message = "A password reset link has been sent to your email.";
        } else {
            // Don't reveal whether email exists for security
            $message = "If that email is registered, a reset link has been sent.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Antrack — Forgot Password</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/styles.css">
    <link rel="icon" type="image/x-icon" href="<?= BASE_URL ?>/favicon.ico">
</head>
<body>

<div class="auth-container flex-center">
    <div class="card auth-card">

        <div style="text-align:center; margin-bottom:1.5rem;">
            <img src="<?= BASE_URL ?>/public/images/logo.png" alt="Antrack" style="width:36px; height:36px;">
            <h1 class="auth-title">Forgot Password?</h1>
            <p class="auth-subtitle">Enter your email and we’ll send you a reset link</p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($errors) ?></div>
        <?php endif; ?>

        <!-- Show reset link directly as backup (remove this in production if email works) -->
        <?php if (!empty($resetLink)): ?>
            <div class="alert alert-warning" style="word-break:break-all;">
                <strong>Your reset link:</strong><br>
                <a href="<?= htmlspecialchars($resetLink) ?>"><?= htmlspecialchars($resetLink) ?></a>
            </div>
        <?php endif; ?>

        <?php if (empty($message)): ?>
        <form method="POST">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email"
                    placeholder="you@example.com"
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                    required>
            </div>
            <button type="submit" name="forgot" class="btn btn-primary"
                style="width:100%; padding:0.65rem;">
                Send Reset Link
            </button>
        </form>
        <?php endif; ?>

        <p style="text-align:center; margin-top:1.25rem; margin-bottom:0; font-size:0.9rem;">
            <a href="<?= BASE_URL ?>/index.php">&larr; Back to login</a>
        </p>

    </div>
</div>

</body>
</html>