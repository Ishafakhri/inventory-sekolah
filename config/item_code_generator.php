<?php
/* filepath: c:\xampp\htdocs\inventory-sekolah\config\item_code_generator.php */
class ItemCodeGenerator {
    private $db;
    
    // Mapping kategori ke kode
    private $categoryCodeMap = [
        'olahraga' => 'OLR',
        'elektronik' => 'ELK', 
        'furnitur' => 'FUR',
        'laboratorium' => 'LAB',
        'alat tulis' => 'ATK',
        'kebersihan' => 'KBR'
    ];
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Generate kode barang berdasarkan kategori
     * Format: [KODE_KATEGORI][NOMOR_URUT_4_DIGIT]
     * Contoh: OLR0001, ELK0001, FUR0001
     */
    public function generateItemCode($categoryId) {
        try {
            // Ambil nama kategori
            $stmt = $this->db->prepare("SELECT category_name FROM categories WHERE id = ?");
            $stmt->execute([$categoryId]);
            $category = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$category) {
                throw new Exception("Kategori tidak ditemukan");
            }
            
            $categoryName = strtolower(trim($category['category_name']));
            
            // Cari kode kategori
            $categoryCode = $this->getCategoryCode($categoryName);
            
            // Hitung nomor urut berikutnya untuk kategori ini
            $nextNumber = $this->getNextSequenceNumber($categoryCode);
            
            // Format: KODE + 4 digit nomor urut
            $itemCode = $categoryCode . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            
            return $itemCode;
            
        } catch (Exception $e) {
            error_log("Error generating item code: " . $e->getMessage());
            // Fallback ke kode generic jika ada error
            return $this->generateGenericCode();
        }
    }
    
    /**
     * Mendapatkan kode kategori berdasarkan nama
     */
    private function getCategoryCode($categoryName) {
        // Cek mapping exact match
        if (isset($this->categoryCodeMap[$categoryName])) {
            return $this->categoryCodeMap[$categoryName];
        }
        
        // Cek partial match
        foreach ($this->categoryCodeMap as $name => $code) {
            if (strpos($categoryName, $name) !== false) {
                return $code;
            }
        }
        
        // Default ke kode generic jika tidak ada yang cocok
        return 'ITM'; // Generic item code
    }
    
    /**
     * Mendapatkan nomor urut berikutnya untuk kategori
     */
    private function getNextSequenceNumber($categoryCode) {
        $stmt = $this->db->prepare("
            SELECT MAX(CAST(SUBSTRING(item_code, 4) AS UNSIGNED)) as max_num 
            FROM items 
            WHERE item_code LIKE ?
        ");
        $stmt->execute([$categoryCode . '%']);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $maxNum = $result['max_num'] ?? 0;
        return $maxNum + 1;
    }
    
    /**
     * Generate kode generic untuk kategori yang tidak dikenal
     */
    private function generateGenericCode() {
        $stmt = $this->db->prepare("
            SELECT MAX(CAST(SUBSTRING(item_code, 4) AS UNSIGNED)) as max_num 
            FROM items 
            WHERE item_code LIKE 'ITM%'
        ");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $maxNum = $result['max_num'] ?? 0;
        return 'ITM' . str_pad($maxNum + 1, 4, '0', STR_PAD_LEFT);
    }
    
    /**
     * Validasi apakah kode barang sudah ada
     */
    public function isCodeExists($itemCode) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM items WHERE item_code = ?");
        $stmt->execute([$itemCode]);
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Generate kode barang yang unik (tidak duplikat)
     */
    public function generateUniqueItemCode($categoryId) {
        $attempts = 0;
        $maxAttempts = 10;
        
        do {
            $itemCode = $this->generateItemCode($categoryId);
            $attempts++;
            
            if (!$this->isCodeExists($itemCode)) {
                return $itemCode;
            }
            
        } while ($attempts < $maxAttempts);
        
        // Jika masih duplikat setelah 10 percobaan, tambahkan timestamp
        return $itemCode . time();
    }
}
?>