<?php
// This will contain all the processes/functions
// that affect the Account model
require_once __DIR__ . "/../public/database.config.php";
require_once __DIR__ . "/../vendor/autoload.php";

use Resend\Resend;

class AccountController {
    // Properties
    private $conn;

    function __construct($server_name, $username, $password, $db_name) {
        $this->conn = new mysqli(
            $server_name,
            $username,
            $password,
            $db_name
        );
        if ($this->conn->connect_error) {
            die("Database connection failed: " . $this->conn->connect_error);
        }

        $this->ensurePasswordResetsTable();
    }

    private function ensurePasswordResetsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS `password_resets` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `email` VARCHAR(191) NOT NULL,
            `token` VARCHAR(128) NOT NULL,
            `expires_at` DATETIME NOT NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `token_unique` (`token`),
            KEY `email_index` (`email`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->conn->query($sql);
    }

    function register($username, $email, $password) {
        // Check if username is already taken
        $check = $this->conn->prepare("SELECT id FROM accounts WHERE username = ? OR email = ?");
        $check->bind_param("ss", $username, $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            return 'taken'; // username or email already exists
        }

        // Hash the password before saving — never store plain text
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $this->conn->prepare("INSERT INTO accounts (username, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $email, $hashed);
        return $stmt->execute() ? 'ok' : 'error';
    }

    function login($username, $password) {
        $stmt = $this->conn->prepare("SELECT * FROM accounts WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        if ($result && password_verify($password, $result['password'])) {
            $_SESSION['account_id'] = $result['id'];
            $_SESSION['username']   = $result['username'];
            $_SESSION['email']      = $result['email'];
            return true;
        }
        return false;
    }

    function forgotPassword($email) {
    // Check if email exists
    $stmt = $this->conn->prepare("SELECT id FROM accounts WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        return false;
    }

    // Delete any existing reset tokens for this email
    $delete = $this->conn->prepare("DELETE FROM password_resets WHERE email = ?");
    $delete->bind_param("s", $email);
    $delete->execute();

    // Generate a secure random token
    $token = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

    // Save token
    $insert = $this->conn->prepare(
        "INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)"
    );
    $insert->bind_param("sss", $email, $token, $expiresAt);

    if (!$insert->execute()) {
        return false;
    }

    // Create reset link
    $baseUrl = getenv('BASE_URL');

    if (!$baseUrl) {
        $baseUrl = 'https://antrack-wv8s.onrender.com';
    }

    $resetLink = $baseUrl . '/views/auth/resetPassword.php?token=' . urlencode($token);

    // Get Resend API key from environment variable
    $apiKey = getenv('RESEND_API_KEY');

    if (!$apiKey) {
        return false;
    }

    try {
        $resend = new Resend($apiKey);

        $resend->emails->send([
            'from' => 'Antrack <onboarding@resend.dev>',
            'to' => [$email],
            'subject' => 'Reset your Antrack password',
            'html' => '
                <h2>Reset your Antrack password</h2>
                <p>You requested to reset your password.</p>
                <p>Click the button below to choose a new password:</p>
                <p>
                    <a href="' . htmlspecialchars($resetLink) . '"
                       style="
                           display:inline-block;
                           padding:12px 20px;
                           background:#87CEEB;
                           color:#000;
                           text-decoration:none;
                           border-radius:8px;
                           font-weight:bold;
                       ">
                        Reset Password
                    </a>
                </p>
                <p>This link will expire in 1 hour.</p>
                <p>If you did not request a password reset, you can ignore this email.</p>
            '
        ]);

    } catch (\Exception $e) {
        error_log("Resend email error: " . $e->getMessage());
        return false;
    }

    return $token;
}
    function validateResetToken($token) {
        $stmt = $this->conn->prepare(
            "SELECT email FROM password_resets WHERE token = ? AND expires_at > NOW()"
        );
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result ? $result['email'] : false;
    }

    function resetPassword($token, $newPassword) {
        $email = $this->validateResetToken($token);

        if (!$email) {
            return false; // Token invalid or expired
        }

        $hashed = password_hash($newPassword, PASSWORD_DEFAULT);

        $update = $this->conn->prepare("UPDATE accounts SET password = ? WHERE email = ?");
        $update->bind_param("ss", $hashed, $email);
        $update->execute();

        // Delete used token
        $delete = $this->conn->prepare("DELETE FROM password_resets WHERE token = ?");
        $delete->bind_param("s", $token);
        $delete->execute();

        return true;
    }

    function update($id, $username, $password) {
        // account updating logic
    }

    function delete($id, $username, $password) {
        // account deletion logic
    }
}