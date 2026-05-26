<?php
// Task model holds task data in memory.
// It matches the database fields for tasks.
class Task {
    // Properties that hold task values
    public $id = "";
    public $account_id = "";
    public $title = "";
    public $description = "";
    public $due_date = "";
    public $priority = "Medium";
    public $category = "Personal";
    public $status = "pending";
    public $created_at = "";

    // Build a new Task object from form or database values.
    function __construct($account_id, $title, $description, $due_date = "", $priority = "Medium", $category = "Personal", $status = "pending", $id = "", $created_at = "") {
        $this->id = $id;
        $this->account_id = $account_id;
        $this->title = $title;
        $this->description = $description;
        $this->due_date = $due_date;
        $this->priority = $priority;
        $this->category = $category;
        $this->status = $status;
        $this->created_at = $created_at;
    }
}