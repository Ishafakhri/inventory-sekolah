<?php
header('Content-Type: application/json');
require_once '../config/auth.php';
require_once '../config/database.php';
require_once '../includes/notifications.php';

$auth = new Auth();

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

try {
    $database = new Database();
    $db = $database->connect();
    $notifications = new Notifications($db);
    
    $data = $notifications->getNotificationData();
    
    echo json_encode($data);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal Server Error']);
}
?>
