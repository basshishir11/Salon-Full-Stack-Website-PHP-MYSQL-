<?php
date_default_timezone_set('Asia/Kathmandu');
// config/database.php

class Database {
    private $host = "localhost";
    private $db_name = "sharma_salon";
    private $username = "root";
    private $password = ""; 
    public $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->exec("set names utf8mb4");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $exception) {
            // Rethrow or let it bubble up, DO NOT ECHO
            throw $exception;
        }

        return $this->conn;
    }
}

