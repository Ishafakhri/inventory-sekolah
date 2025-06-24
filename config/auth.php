<?php
// filepath: c:\xampp\htdocs\inventory-sekolah\config\auth.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'database.php';

class Auth {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
    }
    
    public function login($username, $password) {
        $query = "SELECT u.*, r.role_name FROM users u 
                  JOIN roles r ON u.role_id = r.id 
                  WHERE u.username = :username AND u.password = :password AND u.is_active = 1";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':password', $password);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role_name'] = $user['role_name'];
            $_SESSION['role_id'] = $user['role_id'];
            return true;
        }
        return false;
    }
    
    public function logout() {
        session_destroy();
        header("Location: ../index.php");
        exit();
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public function requireLogin() {
        if(!$this->isLoggedIn()) {
            header("Location: ../index.php");
            exit();
        }
    }
    
    public function hasRole($roles) {
        if(!$this->isLoggedIn()) return false;
        
        if(is_array($roles)) {
            return in_array($_SESSION['role_name'], $roles);
        }
        return $_SESSION['role_name'] === $roles;
    }
    
    public function getUserRolePermissions() {
        if(!$this->isLoggedIn()) return [];
        
        $permissions = [
            'Administrator' => ['all'],
            'Manager' => ['dashboard', 'items', 'categories', 'transactions', 'requests', 'reports', 'users'],
            'Procurement' => ['dashboard', 'items', 'categories', 'transactions', 'requests'],
            'Warehouse' => ['dashboard', 'items', 'categories', 'transactions'],
            'User' => ['dashboard', 'items', 'my-requests', 'catalog'],
            'Technician' => ['dashboard', 'items', 'maintenance', 'catalog'],
            'Auditor' => ['dashboard', 'items', 'reports', 'catalog']
        ];
        
        return $permissions[$_SESSION['role_name']] ?? [];
    }
    
    public function requireRole($roles) {
        if(!$this->hasRole($roles)) {
            header("Location: ../index.php");
            exit();
        }
    }
}
?>