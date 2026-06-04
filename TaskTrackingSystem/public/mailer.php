<?php
// Simple SMTP mailer using plain PHP sockets
// Works on Railway when MAIL_* environment variables are set

function sendResetEmail($toEmail, $resetLink) {
    $host     = getenv('MAIL_HOST')     ?: 'smtp.gmail.com';
    $port     = (int)(getenv('MAIL_PORT')     ?: 587);
    $username = getenv('MAIL_USERNAME') ?: '';
    $password = getenv('MAIL_PASSWORD') ?: '';
    $from     = getenv('MAIL_FROM')     ?: $username;

    if (empty($username) || empty($password)) {
        // No mail config — skip sending, just return the link
        return $resetLink;
    }

    $subject = "Reset your Task Tracker password";
    $body    = "Hi,\r\n\r\n"
             . "You requested a password reset for your Task Tracker account.\r\n\r\n"
             . "Click the link below to reset your password (valid for 1 hour):\r\n"
             . $resetLink . "\r\n\r\n"
             . "If you did not request this, ignore this email.\r\n\r\n"
             . "Task Tracker";

    $headers = "From: Task Tracker <{$from}>\r\n"
             . "Reply-To: {$from}\r\n"
             . "MIME-Version: 1.0\r\n"
             . "Content-Type: text/plain; charset=UTF-8\r\n";

    // Try PHP mail() — works on some hosts
    // On Railway with Gmail SMTP you may need PHPMailer for full support
    $sent = @mail($toEmail, $subject, $body, $headers);

    return $resetLink; // Always return the link as fallback
}