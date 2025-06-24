<?php
// filepath: c:\xampp\htdocs\inventory-sekolah\config\database.php

class Database {
    private $host = "localhost";
    private $username = "root";
    private $password = "";
    private $database = "inventory_sekolah";
    private $conn;

    public function connect() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->database, 
                                $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            echo "Connection error: " . $e->getMessage();
        }
        return $this->conn;
    }
}
?>