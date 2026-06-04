<?php
// This mimics the table "accounts" in your database
class Account {
    // Properties
    public $id         = "";
    public $username   = "";
    public $email      = "";
    public $password   = "";
    public $created_at = "";

    function __construct($username, $password, $email = "", $created_at = "", $id = "") {
        $this->id         = $id;
        $this->username   = $username;
        $this->email      = $email;
        $this->password   = $password;
        $this->created_at = $created_at;
    }
}