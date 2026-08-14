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

$errors = "";
$message = "";
$resetLink = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["forgot"])) {

    $email = trim($_POST["email"] ?? "");

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $errors = "Please enter a valid email address.";

    } else {

        /*
         * TEMPORARY PASSWORD RESET FLOW
         *
         * Email sending is disabled for now because Resend
         * is not available on the deployed server.
         *
         * We generate the reset token here instead.
         */

        $conn = new mysqli(
            $SERVER_NAME,
            $USERNAME,
            $PASSWORD,
            $DB_NAME
        );

        if ($conn->connect_error) {
            $errors = "Unable to connect to the database.";
        } else {

            // Check whether the email exists
            $stmt = $conn->prepare(
                "SELECT email FROM accounts WHERE email = ? LIMIT 1"
            );

            $stmt->bind_param("s", $email);
            $stmt->execute();

            $result = $stmt->get_result();

            if ($result->num_rows > 0) {

                /*
                 * Generate a secure reset token.
                 */
                $token = bin2hex(random_bytes(32));

                /*
                 * Token is valid for 1 hour.
                 */
                $expiresAt = date(
                    'Y-m-d H:i:s',
                    strtotime('+1 hour')
                );

                /*
                 * Remove any previous reset tokens
                 * belonging to this email.
                 */
                $deleteStmt = $conn->prepare(
                    "DELETE FROM password_resets WHERE email = ?"
                );

                $deleteStmt->bind_param("s", $email);
                $deleteStmt->execute();
                $deleteStmt->close();

                /*
                 * Save the new reset token.
                 */
                $insertStmt = $conn->prepare(
                    "INSERT INTO password_resets
                    (email, token, expires_at)
                    VALUES (?, ?, ?)"
                );

                $insertStmt->bind_param(
                    "sss",
                    $email,
                    $token,
                    $expiresAt
                );

                if ($insertStmt->execute()) {

                    $appUrl = getenv('APP_URL') ?: BASE_URL;

                    $resetLink =
                        $appUrl .
                        "/views/auth/resetPassword.php?token=" .
                        urlencode($token);

                    /*
                     * No email is sent for now.
                     */
                    $message =
                        "A password reset link has been sent to your email.";

                } else {

                    $errors =
                        "Unable to create a password reset link.";
                }

                $insertStmt->close();

            } else {

                /*
                 * Do not reveal whether an email exists.
                 */
                $message =
                    "If that email is registered, a password reset link has been sent.";
            }

            $stmt->close();
            $conn->close();
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
    <link rel="icon"
          type="image/x-icon"
          href="<?= BASE_URL ?>/favicon.ico">
</head>

<body>

<div class="auth-container flex-center">

    <div class="card auth-card">

        <div style="text-align:center; margin-bottom:1.5rem;">

            <img
                src="<?= BASE_URL ?>/public/images/logo.png"
                alt="Antrack"
                style="width:36px; height:36px;"
            >

            <h1 class="auth-title">
                Forgot Password?
            </h1>

            <p class="auth-subtitle">
                Enter your email and we'll send you a reset link
            </p>

        </div>


        <?php if (!empty($message)): ?>

            <div class="alert alert-success">
                <?= htmlspecialchars($message) ?>
            </div>

        <?php endif; ?>


        <?php if (!empty($errors)): ?>

            <div class="alert alert-danger">
                <?= htmlspecialchars($errors) ?>
            </div>

        <?php endif; ?>


        <?php if (!empty($resetLink)): ?>

            <!--
                TEMPORARY TESTING LINK

                This is only shown because email sending
                has been disabled for now.
            -->

            <div
                class="alert alert-warning"
                style="margin-top:1rem;"
            >

                <strong>Temporary reset link:</strong>

                <p style="margin:0.5rem 0 0;">
                    Email sending is currently disabled.
                    Use this link to test the password reset:
                </p>

                <a
                    href="<?= htmlspecialchars($resetLink) ?>"
                    style="word-break:break-all;"
                >
                    Continue to reset password
                </a>

            </div>

        <?php endif; ?>


        <?php if (empty($message)): ?>

            <form method="POST">

                <div class="form-group">

                    <label for="email">
                        Email Address
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="you@example.com"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        required
                    >

                </div>


                <button
                    type="submit"
                    name="forgot"
                    class="btn btn-primary"
                    style="width:100%; padding:0.65rem;"
                >
                    Send Reset Link
                </button>

            </form>

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

</body>
</html>