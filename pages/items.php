<?php
require_once '../config/auth.php';
require_once '../config/database.php';
require_once '../config/item_code_generator.php';

$auth = new Auth();
$auth->requireLogin();

$database = new Database();
$db = $database->connect();

// Handle CRUD operations
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                // Generate item code berdasarkan kategori
                $generator = new ItemCodeGenerator($db);
                $itemCode = $generator->generateUniqueItemCode($_POST['category_id']);
                
                $stmt = $db->prepare("INSERT INTO items (item_code, item_name, description, category_id, quantity, min_stock, unit, price, location) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$itemCode, $_POST['item_name'], $_POST['description'], $_POST['category_id'], $_POST['quantity'], $_POST['min_stock'], $_POST['unit'], $_POST['price'], $_POST['location']]);
                break;
            case 'edit':
                $stmt = $db->prepare("UPDATE items SET item_name=?, description=?, category_id=?, quantity=?, min_stock=?, unit=?, price=?, location=? WHERE id=?");
                $stmt->execute([$_POST['item_name'], $_POST['description'], $_POST['category_id'], $_POST['quantity'], $_POST['min_stock'], $_POST['unit'], $_POST['price'], $_POST['location'], $_POST['id']]);
                break;
            case 'delete':
                $stmt = $db->prepare("DELETE FROM items WHERE id=?");
                $stmt->execute([$_POST['id']]);
                break;
        }
        header("Location: items.php");
        exit();
    }
}

// Get items with categories
$stmt = $db->query("SELECT i.*, c.category_name FROM items i LEFT JOIN categories c ON i.category_id = c.id ORDER BY i.item_name");
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories for dropdown
$stmt = $db->query("SELECT * FROM categories ORDER BY category_name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Barang - Sistem Inventory Sekolah</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="../assets/css/sidebar.css" rel="stylesheet">
    <link href="../assets/css/additional.css" rel="stylesheet">
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <nav class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h3>Inventory System</h3>
                <p><?php echo $_SESSION['role_name']; ?></p>
            </div>
            <ul class="sidebar-nav">
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
                <li><a href="items.php" class="active"><i class="fas fa-boxes"></i> <span>Data Barang</span></a></li>
                <li><a href="categories.php"><i class="fas fa-tags"></i> <span>Kategori</span></a></li>
                
                <?php if($auth->hasRole(['Administrator', 'Manager', 'Procurement', 'Warehouse'])): ?>
                <li><a href="transactions.php"><i class="fas fa-exchange-alt"></i> <span>Transaksi</span></a></li>
                <?php endif; ?>
                
                <?php if($auth->hasRole(['Administrator', 'Manager', 'Procurement'])): ?>
                <li><a href="requests.php"><i class="fas fa-clipboard-list"></i> <span>Permintaan</span></a></li>
                <?php endif; ?>
                
                <?php if($auth->hasRole(['User'])): ?>
                <li><a href="my-requests.php"><i class="fas fa-paper-plane"></i> <span>Permintaan Saya</span></a></li>
                <?php endif; ?>
                
                <?php if($auth->hasRole(['Administrator', 'Technician'])): ?>
                <li><a href="maintenance.php"><i class="fas fa-tools"></i> <span>Maintenance</span></a></li>
                <?php endif; ?>
                
                <?php if($auth->hasRole(['Administrator', 'Manager', 'Auditor'])): ?>
                <li><a href="reports.php"><i class="fas fa-chart-bar"></i> <span>Laporan</span></a></li>
                <?php endif; ?>
                
                <?php if($auth->hasRole(['Administrator'])): ?>
                <li><a href="users.php"><i class="fas fa-users"></i> <span>Manajemen User</span></a></li>
                <?php endif; ?>
                
                <li><a href="../config/logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <div class="main-content" id="main-content">
            <!-- Top Bar -->
            <div class="topbar">
                <button class="menu-toggle" id="menu-toggle">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="user-info">
                    <span>Selamat datang, <?php echo $_SESSION['full_name']; ?></span>
                    <div class="user-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="content">
                <div class="page-title">
                    <h1>Data Barang</h1>
                    <p>Kelola data barang inventory sekolah</p>
                </div>

                <!-- Search Box -->
                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="Cari barang...">
                    <i class="fas fa-search"></i>
                </div>

                <div class="table-container">
                    <div class="table-header">
                        <h3>Daftar Barang</h3>
                        <?php if($auth->hasRole(['Administrator', 'Manager', 'Warehouse'])): ?>
                        <button class="btn btn-primary" onclick="openModal('addModal')">
                            <i class="fas fa-plus"></i> Tambah Barang
                        </button>
                        <?php endif; ?>
                    </div>
                    
                    <table id="itemsTable">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Nama Barang</th>
                                <th>Kategori</th>
                                <th>Stok</th>
                                <th>Min. Stok</th>
                                <th>Lokasi</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($items as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['item_code']); ?></td>
                                <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                <td><?php echo htmlspecialchars($item['category_name'] ?? 'Tidak ada kategori'); ?></td>
                                <td>
                                    <span class="badge <?php echo $item['quantity'] <= $item['min_stock'] ? 'badge-danger' : 'badge-success'; ?>">
                                        <?php echo $item['quantity']; ?> <?php echo htmlspecialchars($item['unit']); ?>
                                    </span>
                                </td>
                                <td><?php echo $item['min_stock']; ?></td>
                                <td><?php echo htmlspecialchars($item['location']); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $item['condition_status'] == 'Baik' ? 'success' : 'warning'; ?>">
                                        <?php echo htmlspecialchars($item['condition_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-info" onclick="viewItem(<?php echo htmlspecialchars(json_encode($item)); ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <?php if($auth->hasRole(['Administrator', 'Manager', 'Warehouse'])): ?>
                                    <button class="btn btn-sm btn-warning" onclick="editItem(<?php echo htmlspecialchars(json_encode($item)); ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="deleteItem(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['item_name']); ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Tambah Barang Baru</h3>
                <span class="close" onclick="closeModal('addModal')">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label>Kategori</label>
                    <select name="category_id" id="add_category_id" onchange="generateItemCode()" required>
                        <option value="">Pilih Kategori</option>
                        <?php foreach($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['category_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Kode Barang <small class="text-muted">(Otomatis dibuat berdasarkan kategori)</small></label>
                    <input type="text" id="preview_item_code" placeholder="Pilih kategori untuk generate kode" readonly style="background: #f8f9fa;">
                </div>
                <div class="form-group">
                    <label>Nama Barang</label>
                    <input type="text" name="item_name" required>
                </div>
                <div class="form-group">
                    <label>Deskripsi</label>
                    <textarea name="description" rows="3"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Jumlah</label>
                        <input type="number" name="quantity" value="0" required>
                    </div>
                    <div class="form-group">
                        <label>Min. Stok</label>
                        <input type="number" name="min_stock" value="0" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Satuan</label>
                        <input type="text" name="unit" placeholder="Pcs, Unit, Kg, dll" required>
                    </div>
                    <div class="form-group">
                        <label>Harga</label>
                        <input type="number" name="price" step="0.01">
                    </div>
                </div>
                <div class="form-group">
                    <label>Lokasi</label>
                    <input type="text" name="location">
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addModal')">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Barang</h3>
                <span class="close" onclick="closeModal('editModal')">&times;</span>
            </div>
            <form method="POST" id="editForm">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="form-group">
                    <label>Nama Barang</label>
                    <input type="text" name="item_name" id="edit_item_name" required>
                </div>
                <div class="form-group">
                    <label>Kategori</label>
                    <select name="category_id" id="edit_category_id" required>
                        <option value="">Pilih Kategori</option>
                        <?php foreach($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['category_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Deskripsi</label>
                    <textarea name="description" id="edit_description" rows="3"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Jumlah</label>
                        <input type="number" name="quantity" id="edit_quantity" required>
                    </div>
                    <div class="form-group">
                        <label>Min. Stok</label>
                        <input type="number" name="min_stock" id="edit_min_stock" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Satuan</label>
                        <input type="text" name="unit" id="edit_unit" required>
                    </div>
                    <div class="form-group">
                        <label>Harga</label>
                        <input type="number" name="price" id="edit_price" step="0.01">
                    </div>
                </div>
                <div class="form-group">
                    <label>Lokasi</label>
                    <input type="text" name="location" id="edit_location">
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">Batal</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/sidebar.js"></script>
    <script src="../assets/js/items.js"></script>
    <script>
        // Initialize search
        setupSearch('itemsTable', 'searchInput');
        
        // Function to generate preview item code
        function generateItemCode() {
            const categorySelect = document.getElementById('add_category_id');
            const previewField = document.getElementById('preview_item_code');
            
            if (categorySelect.value) {
                // Get category name
                const selectedOption = categorySelect.options[categorySelect.selectedIndex];
                const categoryName = selectedOption.text.toLowerCase();
                
                // Map category to code
                const categoryCodeMap = {
                    'olahraga': 'OLR',
                    'elektronik': 'ELK',
                    'furnitur': 'FUR',
                    'laboratorium': 'LAB',
                    'alat tulis': 'ATK',
                    'kebersihan': 'KBR'
                };
                
                let categoryCode = 'ITM'; // Default
                for (const [name, code] of Object.entries(categoryCodeMap)) {
                    if (categoryName.includes(name)) {
                        categoryCode = code;
                        break;
                    }
                }
                
                previewField.value = categoryCode + 'XXXX (akan dibuat otomatis)';
                previewField.style.color = '#28a745';
            } else {
                previewField.value = 'Pilih kategori untuk generate kode';
                previewField.style.color = '#6c757d';
            }
        }
    </script>
</body>
</html>
</html>
