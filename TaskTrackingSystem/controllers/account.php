<?php
// This will contain all the processes/functions
// that affect the Account model
require_once __DIR__ . "/../public/database.config.php";

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
    }

    function register($username, $password) {
        // Check if username is already taken
        $check = $this->conn->prepare("SELECT id FROM accounts WHERE username = ?");
        $check->bind_param("s", $username);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            return false;
        }

        // Hash the password before saving — never store plain text
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $this->conn->prepare("INSERT INTO accounts (username, password) VALUES (?, ?)");
        $stmt->bind_param("ss", $username, $hashed);
        return $stmt->execute();
    }

    function login($username, $password) {
        $stmt = $this->conn->prepare("SELECT * FROM accounts WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        if ($result && password_verify($password, $result['password'])) {
            $_SESSION['account_id'] = $result['id'];
            $_SESSION['username']   = $result['username'];
            return true;
        }
        return false;
    }

    function update($id, $username, $password) {
        // account updating logic
    }

    function delete($id, $username, $password) {
        // account deletion logic
    }
}