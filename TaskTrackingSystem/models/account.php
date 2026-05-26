<?php
// This mimics the table "accounts" in your database
class Account {
    // Properties
    public $id = "";
    public $username = "";
    public $password = "";
    public $created_at = "";

    function __construct($username, $password, $created_at = "", $id = "") {
        $this->id = $id;
        $this->username = $username;
        $this->password = $password;
        $this->created_at = $created_at;
    }
}