<?php
header('Content-Type: application/json');
require_once '../config/auth.php';
require_once '../config/database.php';

$auth = new Auth();

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

// Check if user has access to maintenance data
if (!$auth->hasRole(['Administrator', 'Manager', 'Technician'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit();
}

try {
    $database = new Database();
    $db = $database->connect();
    
    // Check if maintenance table exists
    $tableExists = false;
    try {
        $stmt = $db->query("SHOW TABLES LIKE 'maintenance'");
        $tableExists = $stmt->rowCount() > 0;
    } catch (Exception $e) {
        $tableExists = false;
    }
    
    if (!$tableExists) {
        echo json_encode([
            'success' => true,
            'items' => [],
            'total_count' => 0,
            'message' => 'Maintenance table not found'
        ]);
        exit();
    }
    
    // Build query based on user role
    $whereClause = "WHERE m.status = 'SCHEDULED' AND m.scheduled_date < CURDATE()";
    $params = [];
    
    // If technician, only show their assigned maintenance
    if ($auth->hasRole(['Technician']) && !$auth->hasRole(['Administrator'])) {
        $whereClause .= " AND m.technician_id = ?";
        $params[] = $_SESSION['user_id'];
    }
    
    // Get overdue maintenance with details
    $stmt = $db->prepare("SELECT m.*, 
                                 i.item_name, 
                                 i.item_code,
                                 u.full_name as technician_name,
                                 c.category_name,
                                 DATEDIFF(CURDATE(), m.scheduled_date) as days_overdue
                         FROM maintenance m 
                         JOIN items i ON m.item_id = i.id 
                         JOIN users u ON m.technician_id = u.id 
                         LEFT JOIN categories c ON i.category_id = c.id 
                         $whereClause
                         ORDER BY m.scheduled_date ASC");
    
    $stmt->execute($params);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate additional information
    foreach ($items as &$item) {
        $item['urgency'] = $item['days_overdue'] > 30 ? 'HIGH' : 
            ($item['days_overdue'] > 7 ? 'MEDIUM' : 'LOW');
        $item['priority'] = $item['maintenance_type'] === 'CORRECTIVE' ? 'HIGH' : 
            ($item['maintenance_type'] === 'PREVENTIVE' ? 'MEDIUM' : 'LOW');
    }
    
    echo json_encode([
        'success' => true,
        'items' => $items,
        'total_count' => count($items),
        'high_priority_count' => count(array_filter($items, function($item) {
            return $item['urgency'] === 'HIGH';
        })),
        'medium_priority_count' => count(array_filter($items, function($item) {
            return $item['urgency'] === 'MEDIUM';
        }))
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Internal Server Error: ' . $e->getMessage()
    ]);
}
?>
