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

try {
    $database = new Database();
    $db = $database->connect();
    
    // Get items with low stock or out of stock
    $stmt = $db->query("SELECT i.*, c.category_name 
                       FROM items i 
                       LEFT JOIN categories c ON i.category_id = c.id 
                       WHERE i.quantity <= i.min_stock 
                       ORDER BY 
                           CASE WHEN i.quantity = 0 THEN 1 ELSE 2 END,
                           (i.quantity - i.min_stock) ASC,
                           i.item_name ASC");
    
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate additional information
    foreach ($items as &$item) {
        $item['stock_percentage'] = $item['min_stock'] > 0 ? 
            round(($item['quantity'] / $item['min_stock']) * 100, 1) : 0;
        $item['status'] = $item['quantity'] == 0 ? 'OUT_OF_STOCK' : 'LOW_STOCK';
        $item['urgency'] = $item['quantity'] == 0 ? 'HIGH' : 
            ($item['stock_percentage'] < 25 ? 'MEDIUM' : 'LOW');
    }
    
    echo json_encode([
        'success' => true,
        'items' => $items,
        'total_count' => count($items),
        'out_of_stock_count' => count(array_filter($items, function($item) {
            return $item['quantity'] == 0;
        })),
        'low_stock_count' => count(array_filter($items, function($item) {
            return $item['quantity'] > 0 && $item['quantity'] <= $item['min_stock'];
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
