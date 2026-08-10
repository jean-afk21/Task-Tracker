<?php
// Migration: create password_resets table for forgot/reset password flow
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . "/../public/database.config.php";

$mysqli = new mysqli($SERVER_NAME, $USERNAME, $PASSWORD, $DB_NAME);
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error . "\n");
}

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

if ($mysqli->query($sql) === TRUE) {
    echo "password_resets table created or already exists.\n";
} else {
    echo "Error creating password_resets table: " . $mysqli->error . "\n";
}

$mysqli->close();
