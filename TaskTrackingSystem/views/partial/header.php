<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$is_logged_in = isset($_SESSION['account_id']);
$nav_username = $is_logged_in ? htmlspecialchars($_SESSION['username']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Tracker</title>
    <link rel="stylesheet" href="/TaskTrackingSystem/public/styles.css">
</head>
<body>

<?php if ($is_logged_in): ?>
    <nav class="navbar">
        <div class="container flex-between">
            <span class="nav-brand">&#10003; Task Tracker</span>
            <div class="nav-links">
                <span style="color:#94a3b8; font-size:0.9rem;">Hi, <?= $nav_username ?>!</span>
                <a href="/TaskTrackingSystem/views/dashboard/dashboard.php">Dashboard</a>
                <a href="/TaskTrackingSystem/logout.php" onclick="return confirm('Are you sure you want to log out?');">Logout</a>
            </div>
        </div>
    </nav>
<?php else: ?>
    <nav class="navbar">
        <div class="container flex-between">
            <span class="nav-brand">&#10003; Task Tracker</span>
            <div class="nav-links">
                <a href="/TaskTrackingSystem/index.php">Login</a>
                <a href="/TaskTrackingSystem/views/auth/register.php">Register</a>
            </div>
        </div>
    </nav>
<?php endif; ?>