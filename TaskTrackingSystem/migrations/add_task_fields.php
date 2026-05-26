<?php
// Migration: add optional due_date, priority, and category to tasks table
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . "/../public/database.config.php";

$mysqli = new mysqli($SERVER_NAME, $USERNAME, $PASSWORD, $DB_NAME);
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error . "\n");
}

$table = 'tasks';
$columns = [
    'due_date' => "ALTER TABLE `$table` ADD COLUMN `due_date` DATE NULL AFTER `description`",
    'priority' => "ALTER TABLE `$table` ADD COLUMN `priority` ENUM('Low','Medium','High') NOT NULL DEFAULT 'Medium' AFTER `due_date`",
    'category' => "ALTER TABLE `$table` ADD COLUMN `category` VARCHAR(64) NOT NULL DEFAULT 'Personal' AFTER `priority`"
];

foreach ($columns as $col => $sql) {
    $check = $mysqli->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?");
    $check->bind_param('sss', $DB_NAME, $table, $col);
    $check->execute();
    $check->bind_result($count);
    $check->fetch();
    $check->close();

    if ($count > 0) {
        echo "Column '$col' already exists.\n";
        continue;
    }

    if ($mysqli->query($sql) === TRUE) {
        echo "Added column '$col'.\n";
    } else {
        echo "Error adding '$col': " . $mysqli->error . "\n";
    }
}

$mysqli->close();
echo "Migration complete.\n";
