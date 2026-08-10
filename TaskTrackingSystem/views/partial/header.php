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
<div class="app-layout">
    <aside class="app-sidebar">
        <div class="sidebar-brand">
            <div class="sidebar-avatar"><?= strtoupper(substr($nav_username ?: 'U', 0, 2)) ?></div>
            <div>
                <p class="sidebar-user-name"><?= $nav_username ?></p>
                <p class="sidebar-user-role">Antrack</p>
            </div>
        </div>
        <nav class="sidebar-nav">
            <a href="<?= BASE_URL ?>/views/dashboard/dashboard.php"
               class="sidebar-item <?= $active === 'dashboard' ? 'active' : '' ?>">
                <span class="sidebar-icon">🏠</span>
                <span>Dashboard</span>
            </a>
            <a href="<?= BASE_URL ?>/views/tasks/index.php"
               class="sidebar-item <?= $active === 'tasks' ? 'active' : '' ?>">
                <span class="sidebar-icon">🗂</span>
                <span>Tasks</span>
            </a>
            <a href="<?= BASE_URL ?>/views/tasks/calendar.php"
               class="sidebar-item <?= $active === 'calendar' ? 'active' : '' ?>">
                <span class="sidebar-icon">🗓</span>
                <span>Calendar</span>
            </a>
            <a href="<?= BASE_URL ?>/views/tasks/analytics.php"
               class="sidebar-item <?= $active === 'analytics' ? 'active' : '' ?>">
                <span class="sidebar-icon">📊</span>
                <span>Analytics</span>
            </a>
        </nav>
        <div class="sidebar-footer">
            <a href="<?= BASE_URL ?>/logout.php"
               class="sidebar-item logout-link"
               onclick="return confirm('Are you sure you want to log out?');">
                <span class="sidebar-icon">⇦</span>
                <span>Logout</span>
            </a>
        </div>
    </aside>

    <main class="app-main">
        <header class="app-topbar">
            <div class="topbar-title">Welcome back, <?= $nav_username ?>.</div>
        </header>

<?php else: ?>
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