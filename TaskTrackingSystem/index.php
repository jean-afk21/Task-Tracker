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
<?php require 'views/partial/header.php'; ?>

<div class="auth-container flex-center">
    <div class="card auth-card">

        <div style="text-align:center; margin-bottom:1.5rem;">
            <div class="auth-logo">&#10003;</div>
            <h1 class="auth-title">Task Tracker</h1>
            <p class="auth-subtitle">Sign in to manage your tasks</p>
        </div>

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

            <!-- Forgot password link -->
            <div style="text-align:left; margin-top:-0.5rem; margin-bottom:1rem;">
                <a href="<?= BASE_URL ?>/views/auth/forgot-password.php"
                   style="font-size:0.83rem; color:var(--muted);">
                    Forgot password?
                </a>
            </div>

            <button type="submit" name="login" class="btn btn-primary"
                style="width:100%; padding:0.65rem;">
                Sign In
            </button>
        </form>

        <p style="text-align:center; margin-top:1.25rem; margin-bottom:0; font-size:0.9rem;">
            Don't have an account?
            <a href="<?= BASE_URL ?>/views/auth/register.php"><strong>Register here</strong></a>
        </p>

    </div>
</div>

<?php require 'views/partial/footer.php'; ?>