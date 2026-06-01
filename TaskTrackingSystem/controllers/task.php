<?php
// TaskController manages task-related database operations.
// It provides methods for adding, editing, deleting, and fetching tasks.
require_once __DIR__ . "/../public/database.config.php";

class TaskController {
    // Database connection object
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

    // Get all tasks for the given user account, with optional search filtering.
    function getAllTasks($account_id, $searchTerm = '') {
        if (strlen($searchTerm) > 0) {
            $query = "SELECT * FROM tasks WHERE account_id = ? AND (title LIKE ? OR description LIKE ? OR category LIKE ? OR priority LIKE ? OR status LIKE ?) ORDER BY created_at DESC";
            $searchParam = '%' . $searchTerm . '%';
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("isssss", $account_id, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam);
        } else {
            $stmt = $this->conn->prepare(
                "SELECT * FROM tasks WHERE account_id = ? ORDER BY created_at DESC"
            );
            $stmt->bind_param("i", $account_id);
        }
        $stmt->execute();
        return $stmt->get_result();
    }

    // Add a new task with optional due date, priority, and category.
    function addTask($account_id, $title, $description, $due_date = null, $priority = 'Medium', $category = 'Personal') {
        $stmt = $this->conn->prepare(
            "INSERT INTO tasks (account_id, title, description, due_date, priority, category, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')"
        );
        $stmt->bind_param("isssss", $account_id, $title, $description, $due_date, $priority, $category);
        return $stmt->execute();
    }

    // Fetch a single task by ID for this account.
    // This prevents one user from editing or viewing another user's tasks.
    function getTaskById($id, $account_id) {
        $stmt = $this->conn->prepare(
            "SELECT * FROM tasks WHERE id = ? AND account_id = ?"
        );
        $stmt->bind_param("ii", $id, $account_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // Update task details and optional metadata.
    function editTask($id, $account_id, $title, $description, $due_date = null, $priority = 'Medium', $category = 'Personal') {
        $stmt = $this->conn->prepare(
            "UPDATE tasks SET title = ?, description = ?, due_date = ?, priority = ?, category = ? WHERE id = ? AND account_id = ?"
        );
        $stmt->bind_param("sssssii", $title, $description, $due_date, $priority, $category, $id, $account_id);
        return $stmt->execute();
    }

    // Remove a task owned by this user.
    function deleteTask($id, $account_id) {
        $stmt = $this->conn->prepare(
            "DELETE FROM tasks WHERE id = ? AND account_id = ?"
        );
        $stmt->bind_param("ii", $id, $account_id);
        return $stmt->execute();
    }

    // Mark a task as complete so it shows as finished.
    function markComplete($id, $account_id) {
        $stmt = $this->conn->prepare(
            "UPDATE tasks SET status = 'complete' WHERE id = ? AND account_id = ?"
        );
        $stmt->bind_param("ii", $id, $account_id);
        return $stmt->execute();
    }

    // Return task counts for a given account (total and finished)
    // Return task totals and completed task totals for one user.
    function getTaskCounts($account_id) {
        $stmt = $this->conn->prepare(
            "SELECT COUNT(*) AS total, SUM(status = 'complete') AS finished FROM tasks WHERE account_id = ?"
        );
        $stmt->bind_param("i", $account_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
}
