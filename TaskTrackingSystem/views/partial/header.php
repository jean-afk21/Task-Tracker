<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$is_logged_in = isset($_SESSION['account_id']);
$nav_username = $is_logged_in ? htmlspecialchars($_SESSION['username']) : '';
$currentFile  = basename($_SERVER['SCRIPT_NAME']);
$currentDir   = basename(dirname($_SERVER['SCRIPT_NAME']));

$active = 'dashboard';
if ($currentFile === 'calendar.php')       { $active = 'calendar'; }
elseif ($currentFile === 'analytics.php')  { $active = 'analytics'; }
elseif ($currentDir  === 'tasks')          { $active = 'tasks'; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Antrack</title>
    <link rel="icon" type="image/x-icon" href="<?= BASE_URL ?>/favicon.ico">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/styles.css">
</head>
<body>

<?php if ($is_logged_in): ?>
<!-- SIDEBAR -->
<aside class="sidebar">
    <div class="brand">
        <img src="<?= BASE_URL ?>/public/images/logo.png" alt="Antrack">
        <h2>Antrack</h2>
    </div>

    <div class="menu-title">Menu</div>

    <a href="<?= BASE_URL ?>/views/dashboard/dashboard.php" class="nav-item <?= $active === 'dashboard' ? 'active' : '' ?>">
        <svg class="nav-icon" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M3 9.5 12 3l9 6.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1V9.5Z"/>
        </svg>
        <span>Dashboard</span>
    </a>

    <a href="<?= BASE_URL ?>/views/tasks/index.php" class="nav-item <?= $active === 'tasks' ? 'active' : '' ?>">
        <svg class="nav-icon" viewBox="0 0 24 24" aria-hidden="true">
            <rect x="3" y="4" width="18" height="16" rx="2"/>
            <path d="M3 10h18M8 4v16"/>
        </svg>
        <span>Tasks</span>
    </a>

    <a href="<?= BASE_URL ?>/views/calendar.php" class="nav-item <?= $active === 'calendar' ? 'active' : '' ?>">
        <svg class="nav-icon" viewBox="0 0 24 24" aria-hidden="true">
            <rect x="3" y="5" width="18" height="16" rx="2"/>
            <path d="M3 10h18M8 3v4M16 3v4"/>
        </svg>
        <span>Calendar</span>
    </a>

    <a href="<?= BASE_URL ?>/views/analytics.php" class="nav-item <?= $active === 'analytics' ? 'active' : '' ?>">
        <svg class="nav-icon" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>
        </svg>
        <span>Analytics</span>
    </a>

    <a href="<?= BASE_URL ?>/logout.php" class="nav-item logout" onclick="return confirm('Are you sure you want to log out?');">
        <svg class="nav-icon" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M15 17l-5-5 5-5M10 12h11M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h8"/>
        </svg>
        <span>Logout</span>
    </a>
</aside>

<?php else: ?>

<!-- NAVBAR FOR LOGGED OUT USERS -->
<nav class="navbar">
    <div class="container flex-between">
        <span class="nav-brand">🐜 Antrack</span>
        <div class="nav-links">
            <a href="<?= BASE_URL ?>/index.php">Login</a>
            <a href="<?= BASE_URL ?>/views/auth/register.php">Register</a>
        </div>
    </div>
</nav>

<?php endif; ?>