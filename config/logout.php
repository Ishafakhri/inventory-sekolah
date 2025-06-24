<?php
// filepath: c:\xampp\htdocs\inventory-sekolah\config\logout.php
require_once 'auth.php';

$auth = new Auth();
$auth->logout();
?>