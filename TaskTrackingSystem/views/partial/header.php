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
        <img src="<?= BASE_URL ?>/public/images/logo.png" alt="Antrack" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
        <div class="brand-fallback" aria-hidden="true">A</div>
        <h2>Antrack</h2>
    </div>

    <div class="menu-title">Menu</div>

    <a href="<?= BASE_URL ?>/views/dashboard/dashboard.php" class="nav-item <?= $active === 'dashboard' ? 'active' : '' ?>">
        <span>🏠</span>
        <span>Dashboard</span>
    </a>

    <a href="<?= BASE_URL ?>/views/tasks/index.php" class="nav-item <?= $active === 'tasks' ? 'active' : '' ?>">
        <span>🗂️</span>
        <span>Tasks</span>
    </a>

    <a href="<?= BASE_URL ?>/views/calendar.php" class="nav-item <?= $active === 'calendar' ? 'active' : '' ?>">
        <span>🗓️</span>
        <span>Calendar</span>
    </a>

    <a href="<?= BASE_URL ?>/views/tasks/analytics.php" class="nav-item <?= $active === 'analytics' ? 'active' : '' ?>">
        <span>📊</span>
        <span>Analytics</span>
    </a>

    <a href="<?= BASE_URL ?>/logout.php" class="nav-item logout" onclick="return confirm('Are you sure you want to log out?');">
        <span>⇦</span>
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