<?php
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (!strpos($line, '=')) continue;
        list($k, $v) = explode('=', $line, 2);
        $k = trim($k);
        $v = trim($v);
        putenv("{$k}={$v}");
        $_ENV[$k] = $v;
    }
}

$SERVER_NAME = getenv('DB_SERVER')    ?: getenv('SERVER_NAME') ?: 'localhost';
$dbUserEnv   = getenv('DB_USERNAME');
$legacyUser  = getenv('USERNAME');
if ($dbUserEnv) {
    $USERNAME = $dbUserEnv;
} elseif ($legacyUser && $legacyUser === 'root') {
    $USERNAME = $legacyUser;
} else {
    $USERNAME = 'root';
}
$PASSWORD = getenv('DB_PASSWORD') ?: getenv('PASSWORD') ?: '';
$DB_NAME  = getenv('DB_NAME')     ?: 'task_tracker';

define('BASE_URL', getenv('BASE_URL') ?: '');