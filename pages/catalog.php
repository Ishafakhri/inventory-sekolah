<?php
require_once '../config/database.php';

// Start session to check login status
session_start();

$database = new Database();
$db = $database->connect();

// Check if user is logged in for request feature
$is_logged_in = isset($_SESSION['user_id']);

// Get categories for filter
$stmt = $db->query("SELECT * FROM categories ORDER BY category_name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get items with filter
$where_clause = "WHERE i.quantity > 0";
$params = [];

if (isset($_GET['category']) && !empty($_GET['category'])) {
    $where_clause .= " AND i.category_id = ?";
    $params[] = $_GET['category'];
}

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $where_clause .= " AND (i.item_name LIKE ? OR i.description LIKE ?)";
    $search_term = '%' . $_GET['search'] . '%';
    $params[] = $search_term;
    $params[] = $search_term;
}

$stmt = $db->prepare("SELECT i.*, c.category_name 
                     FROM items i 
                     LEFT JOIN categories c ON i.category_id = c.id 
                     $where_clause 
                     ORDER BY i.item_name");
$stmt->execute($params);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Katalog Barang - Sistem Inventory Sekolah</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="../assets/css/additional.css" rel="stylesheet">
    <style>
        .catalog-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 20px;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .catalog-header h1 {
            margin: 0 0 10px 0;
            font-size: 2.5em;
        }
        
        .filters {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .filter-form {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 15px;
            align-items: end;
        }
        
        .item-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .item-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.3s ease;
        }
        
        .item-card:hover {
            transform: translateY(-5px);
        }
        
        .item-card-header {
            background: #f8f9fa;
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .item-card-body {
            padding: 15px;
        }
        
        .item-name {
            font-size: 18px;
            font-weight: 600;
            margin: 0 0 5px 0;
            color: #333;
        }
        
        .item-code {
            font-size: 12px;
            color: #666;
            font-family: monospace;
        }
        
        .item-description {
            color: #666;
            font-size: 14px;
            margin: 10px 0;
            line-height: 1.4;
        }
        
        .item-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin: 15px 0;
        }
        
        .info-item {
            text-align: center;
            padding: 8px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        
        .info-label {
            font-size: 12px;
            color: #666;
            margin-bottom: 3px;
        }
        
        .info-value {
            font-weight: 600;
            color: #333;
        }
        
        .stock-status {
            text-align: center;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        
        .stock-available {
            background: #d4edda;
            color: #155724;
        }
        
        .stock-low {
            background: #fff3cd;
            color: #856404;
        }
        
        .login-prompt {
            background: #e3f2fd;
            border: 1px solid #bbdefb;
            border-radius: 5px;
            padding: 15px;
            text-align: center;
            margin-top: 15px;
        }
        
        .login-prompt a {
            color: #1565c0;
            text-decoration: none;
            font-weight: 600;
        }
        
        .catalog-nav {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }
        
        .catalog-nav .btn {
            margin-left: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .guest-banner {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
            text-align: center;
            color: #856404;
        }
        
        .guest-banner i {
            margin-right: 8px;
        }
        
        .guest-banner a {
            color: #856404;
            font-weight: 600;
            text-decoration: none;
        }
        
        .guest-banner a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <div class="catalog-nav">
        <?php if(!$is_logged_in): ?>
        <a href="../login.php" class="btn btn-primary">
            <i class="fas fa-sign-in-alt"></i> Login
        </a>
        <?php else: ?>
        <a href="dashboard.php" class="btn btn-success">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a>
        <a href="../config/logout.php" class="btn btn-secondary">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
        <?php endif; ?>
    </div>

    <!-- Catalog Header -->
    <div class="catalog-header">
        <h1><i class="fas fa-boxes"></i> Katalog Barang</h1>
        <p>Lihat barang yang tersedia di inventory sekolah</p>
        <?php if(!$is_logged_in): ?>
        <div style="margin-top: 20px;">
            <a href="../login.php" class="btn btn-light">
                <i class="fas fa-sign-in-alt"></i> Login untuk Request Barang
            </a>
        </div>
        <?php endif; ?>
    </div>

    <div class="content" style="padding: 0 20px;">
        <?php if(!$is_logged_in): ?>
        <!-- Guest Banner -->
        <div class="guest-banner">
            <i class="fas fa-info-circle"></i>
            Anda mengakses sebagai <strong>Tamu</strong>. 
            <a href="../login.php">Login</a> untuk dapat melakukan request barang dan mengakses fitur lengkap.
        </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="filters">
            <h3><i class="fas fa-filter"></i> Filter Barang</h3>
            <form class="filter-form" method="GET">
                <div class="form-group">
                    <label>Kategori</label>
                    <select name="category">
                        <option value="">Semua Kategori</option>
                        <?php foreach($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>" <?php echo (isset($_GET['category']) && $_GET['category'] == $category['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['category_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Cari Barang</label>
                    <input type="text" name="search" placeholder="Nama atau deskripsi barang..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                </div>
                <div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Filter
                    </button>
                    <a href="catalog.php" class="btn btn-secondary">
                        <i class="fas fa-undo"></i> Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Items Grid -->
        <div class="item-grid">
            <?php if(empty($items)): ?>
            <div style="grid-column: 1 / -1; text-align: center; padding: 40px;">
                <i class="fas fa-search" style="font-size: 48px; color: #ddd; margin-bottom: 15px;"></i>
                <h3 style="color: #666;">Tidak ada barang yang ditemukan</h3>
                <p style="color: #999;">Coba ubah filter atau kata kunci pencarian</p>
            </div>
            <?php else: ?>
            <?php foreach($items as $item): ?>
            <div class="item-card">
                <div class="item-card-header">
                    <div class="item-name"><?php echo htmlspecialchars($item['item_name']); ?></div>
                    <div class="item-code"><?php echo htmlspecialchars($item['item_code']); ?></div>
                </div>
                <div class="item-card-body">
                    <div class="item-description">
                        <?php echo htmlspecialchars($item['description'] ?: 'Tidak ada deskripsi'); ?>
                    </div>
                    
                    <div class="item-info">
                        <div class="info-item">
                            <div class="info-label">Kategori</div>
                            <div class="info-value"><?php echo htmlspecialchars($item['category_name'] ?: 'Tidak ada'); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Lokasi</div>
                            <div class="info-value"><?php echo htmlspecialchars($item['location'] ?: 'Tidak diketahui'); ?></div>
                        </div>
                    </div>
                    
                    <div class="stock-status <?php echo $item['quantity'] <= $item['min_stock'] ? 'stock-low' : 'stock-available'; ?>">
                        <i class="fas fa-<?php echo $item['quantity'] <= $item['min_stock'] ? 'exclamation-triangle' : 'check-circle'; ?>"></i>
                        <strong><?php echo $item['quantity']; ?> <?php echo htmlspecialchars($item['unit']); ?></strong> tersedia
                        <?php if($item['quantity'] <= $item['min_stock']): ?>
                        <div style="font-size: 12px; margin-top: 5px;">Stok Menipis!</div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if($is_logged_in): ?>
                    <button class="btn btn-primary btn-block" onclick="requestItem(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['item_name']); ?>', <?php echo $item['quantity']; ?>, '<?php echo htmlspecialchars($item['unit']); ?>')">
                        <i class="fas fa-paper-plane"></i> Request Barang
                    </button>
                    <?php else: ?>
                    <div class="login-prompt">
                        <i class="fas fa-info-circle"></i>
                        <a href="../login.php">Login</a> untuk dapat melakukan request barang
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Back to Top Button -->
        <div style="text-align: center; margin: 40px 0;">
            <button onclick="scrollToTop()" class="btn btn-secondary">
                <i class="fas fa-arrow-up"></i> Kembali ke Atas
            </button>
        </div>
    </div>

    <!-- Request Modal -->
    <?php if($is_logged_in): ?>
    <div id="requestModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Request Barang</h3>
                <span class="close" onclick="closeModal('requestModal')">&times;</span>
            </div>
            <form method="POST" action="my-requests.php">
                <input type="hidden" name="action" value="add_request">
                <input type="hidden" name="item_id" id="request_item_id">
                <div class="form-group">
                    <label>Barang</label>
                    <input type="text" id="request_item_name" readonly>
                </div>
                <div class="form-group">
                    <label>Jumlah Diminta</label>
                    <input type="number" name="quantity_requested" id="request_quantity" min="1" required>
                    <small id="request_stock_info"></small>
                </div>
                <div class="form-group">
                    <label>Keterangan/Keperluan</label>
                    <textarea name="notes" rows="3" placeholder="Jelaskan keperluan penggunaan barang..." required></textarea>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('requestModal')">Batal</button>
                    <button type="submit" class="btn btn-primary">Request</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <script src="../assets/js/main.js"></script>
    <script>
        function scrollToTop() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }
        
        // Show back to top button when scrolling
        window.addEventListener('scroll', function() {
            // Optional: You can add a floating back to top button here
        });

        <?php if($is_logged_in): ?>
        function requestItem(itemId, itemName, stock, unit) {
            document.getElementById('request_item_id').value = itemId;
            document.getElementById('request_item_name').value = itemName;
            document.getElementById('request_quantity').max = stock;
            document.getElementById('request_stock_info').textContent = `Stok tersedia: ${stock} ${unit}`;
            
            openModal('requestModal');
        }
        
        document.getElementById('request_quantity').addEventListener('input', function() {
            const max = this.max;
            if (max && parseInt(this.value) > parseInt(max)) {
                this.value = max;
                alert('Jumlah yang diminta tidak boleh melebihi stok yang tersedia');
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>
