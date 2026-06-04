<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

require_once __DIR__ . '/../../public/database.config.php';

if (isset($_SESSION['account_id'])) {
    header("Location: " . BASE_URL . "/views/dashboard/dashboard.php");
    exit();
}

require_once __DIR__ . '/../../models/account.php';
require_once __DIR__ . '/../../controllers/account.php';

$errors  = "";
$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["register"])) {
    $username = trim($_POST["username"] ?? "");
    $email    = trim($_POST["email"]    ?? "");
    $password = $_POST["password"]      ?? "";
    $confirm  = $_POST["confirm_password"] ?? "";

    if (empty($username) || empty($email) || empty($password) || empty($confirm)) {
        $errors = "Please fill in all fields.";
    } elseif (strlen($username) < 3) {
        $errors = "Username must be at least 3 characters long.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors = "Please enter a valid email address.";
    } elseif (strlen($password) < 6) {
        $errors = "Password must be at least 6 characters long.";
    } elseif ($password !== $confirm) {
        $errors = "Passwords do not match.";
    } else {
        $controller = new AccountController($SERVER_NAME, $USERNAME, $PASSWORD, $DB_NAME);
        $result     = $controller->register($username, $email, $password);

        if ($result === 'ok') {
            $message = "Account created successfully! You can now log in.";
        } elseif ($result === 'taken') {
            $errors = "That username or email is already taken. Please choose another.";
        } else {
            $errors = "Something went wrong. Please try again.";
        }
    }
}
?>
<?php require __DIR__ . '/../partial/header.php'; ?>

<div class="auth-container flex-center">
    <div class="card auth-card">

        <div style="text-align:center; margin-bottom:1.5rem;">
            <div class="auth-logo">🐜</div>
            <h1 class="auth-title">Join Antrack</h1>
            <p class="auth-subtitle">Small steps. Big progress.</p>
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
                    placeholder="At least 3 characters"
                    value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                    required>
            </div>
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email"
                    placeholder="you@example.com"
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                    required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password"
                    placeholder="At least 6 characters" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password"
                    placeholder="Repeat your password" required>
            </div>
            <button type="submit" name="register" class="btn btn-primary"
                style="width:100%; padding:0.65rem;">
                Create Account
            </button>
        </form>

        <p style="text-align:center; margin-top:1.25rem; margin-bottom:0; font-size:0.9rem;">
            Already have an account?
            <a href="<?= BASE_URL ?>/index.php"><strong>Sign in</strong></a>
        </p>

    </div>
</div>

<?php require __DIR__ . '/../partial/footer.php'; ?>