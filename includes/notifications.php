<?php
class Notifications {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    public function getLowStockItems() {
        $stmt = $this->db->query("SELECT i.*, c.category_name 
                                 FROM items i 
                                 LEFT JOIN categories c ON i.category_id = c.id 
                                 WHERE i.quantity <= i.min_stock 
                                 ORDER BY (i.quantity - i.min_stock) ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getLowStockCount() {
        $stmt = $this->db->query("SELECT COUNT(*) as count FROM items WHERE quantity <= min_stock");
        return $stmt->fetch()['count'];
    }
    
    public function getOutOfStockItems() {
        $stmt = $this->db->query("SELECT i.*, c.category_name 
                                 FROM items i 
                                 LEFT JOIN categories c ON i.category_id = c.id 
                                 WHERE i.quantity = 0 
                                 ORDER BY i.item_name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getOutOfStockCount() {
        $stmt = $this->db->query("SELECT COUNT(*) as count FROM items WHERE quantity = 0");
        return $stmt->fetch()['count'];
    }
    
    public function getNotificationData() {
        return [
            'low_stock_count' => $this->getLowStockCount(),
            'out_of_stock_count' => $this->getOutOfStockCount(),
            'low_stock_items' => $this->getLowStockItems(),
            'out_of_stock_items' => $this->getOutOfStockItems()
        ];
    }
}
?>
