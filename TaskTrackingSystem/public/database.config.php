<?php
// Load .env file if present (simple parser)
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

// Prefer explicit DB_* variables. Fall back to legacy names, but avoid
// accidentally using the OS user (e.g. Windows USERNAME) as the DB user.
$SERVER_NAME = getenv('DB_SERVER') ?: getenv('SERVER_NAME') ?: 'localhost';

// DB username: prefer DB_USERNAME. If not set, only accept legacy USERNAME
// when it's 'root' (rare) — otherwise default to 'root' for safety.
$dbUserEnv = getenv('DB_USERNAME');
$legacyUser = getenv('USERNAME');
if ($dbUserEnv) {
	$USERNAME = $dbUserEnv;
} elseif ($legacyUser && $legacyUser === 'root') {
	$USERNAME = $legacyUser;
} else {
	$USERNAME = 'root';
}

// DB password: prefer DB_PASSWORD, then PASSWORD, else empty string
$PASSWORD = getenv('DB_PASSWORD') ?: getenv('PASSWORD') ?: '';

$DB_NAME = getenv('DB_NAME') ?: 'task_tracker';