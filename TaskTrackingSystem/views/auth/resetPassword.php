<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

require_once __DIR__ . '/../../public/database.config.php';
require_once __DIR__ . '/../../controllers/account.php';

if (isset($_SESSION['account_id'])) {
    header("Location: " . BASE_URL . "/views/dashboard/dashboard.php");
    exit();
}

$token = trim($_GET['token'] ?? '');

$errors = "";
$message = "";
$validToken = false;

if (empty($token)) {
    header("Location: " . BASE_URL . "/views/auth/forgotPassword.php");
    exit();
}


/*
 * Validate the reset token using the existing
 * AccountController method.
 */
$controller = new AccountController(
    $SERVER_NAME,
    $USERNAME,
    $PASSWORD,
    $DB_NAME
);

$email = $controller->validateResetToken($token);

if (!$email) {

    $errors =
        "This reset link is invalid or has expired. Please request a new one.";

} else {

    $validToken = true;
}


/*
 * Handle password reset.
 */
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["reset"]) &&
    $validToken
) {

    $password = $_POST["password"] ?? "";
    $confirm = $_POST["confirm_password"] ?? "";

    if (empty($password) || empty($confirm)) {

        $errors = "Please fill in all fields.";

    } elseif (strlen($password) < 6) {

        $errors =
            "Password must be at least 6 characters.";

    } elseif ($password !== $confirm) {

        $errors =
            "Passwords do not match.";

    } else {

        /*
         * Use the existing AccountController method
         * to update the password and delete the token.
         */
        $result = $controller->resetPassword(
            $token,
            $password
        );

        if ($result) {

            $message =
                "Password reset successfully! You can now log in.";

            $validToken = false;

        } else {

            $errors =
                "Something went wrong. Please try again.";
        }
    }
}
?>

<?php require __DIR__ . '/../partial/header.php'; ?>


<div class="auth-container flex-center">

    <div class="card auth-card">

        <div style="text-align:center; margin-bottom:1.5rem;">

            <div class="auth-logo">
                🐜
            </div>

            <h1 class="auth-title">
                Reset Password
            </h1>

            <p class="auth-subtitle">
                Choose a new password for your Antrack account
            </p>

        </div>


        <?php if (!empty($message)): ?>

            <div class="alert alert-success">
                <?= htmlspecialchars($message) ?>
            </div>

            <p style="text-align:center; margin-top:1rem;">

                <a
                    href="<?= BASE_URL ?>/index.php"
                    class="btn btn-primary"
                    style="display:inline-block;"
                >
                    Go to Login
                </a>

            </p>

        <?php endif; ?>


        <?php if (!empty($errors)): ?>

            <div class="alert alert-danger">
                <?= htmlspecialchars($errors) ?>
            </div>

        <?php endif; ?>


        <?php if ($validToken): ?>

            <form method="POST">

                <div class="form-group">

                    <label for="password">
                        New Password
                    </label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="At least 6 characters"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="confirm_password">
                        Confirm New Password
                    </label>

                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        placeholder="Repeat your new password"
                        required
                    >

                </div>


                <button
                    type="submit"
                    name="reset"
                    class="btn btn-primary"
                    style="width:100%; padding:0.65rem;"
                >
                    Reset Password
                </button>

            </form>

        <?php elseif (empty($message)): ?>

            <p style="text-align:center;">

                <a
                    href="<?= BASE_URL ?>/views/auth/forgotPassword.php"
                >
                    Request a new reset link
                </a>

            </p>

        <?php endif; ?>


        <p
            style="
                text-align:center;
                margin-top:1.25rem;
                margin-bottom:0;
                font-size:0.9rem;
            "
        >

            <a href="<?= BASE_URL ?>/index.php">
                &larr; Back to login
            </a>

        </p>

    </div>

</div>


<?php require __DIR__ . '/../partial/footer.php'; ?>