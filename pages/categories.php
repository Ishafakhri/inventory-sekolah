<?php
require_once '../config/auth.php';
require_once '../config/database.php';

$auth = new Auth();
$auth->requireLogin();

$database = new Database();
$db = $database->connect();

// Handle CRUD operations
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $stmt = $db->prepare("INSERT INTO categories (category_name, description) VALUES (?, ?)");
                $stmt->execute([$_POST['category_name'], $_POST['description']]);
                break;
            case 'edit':
                $stmt = $db->prepare("UPDATE categories SET category_name=?, description=? WHERE id=?");
                $stmt->execute([$_POST['category_name'], $_POST['description'], $_POST['id']]);
                break;
            case 'delete':
                $stmt = $db->prepare("DELETE FROM categories WHERE id=?");
                $stmt->execute([$_POST['id']]);
                break;
        }
        header("Location: categories.php");
        exit();
    }
}

// Get categories with item count
$stmt = $db->query("SELECT c.*, COUNT(i.id) as item_count FROM categories c LEFT JOIN items i ON c.id = i.category_id GROUP BY c.id ORDER BY c.category_name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kategori - Sistem Inventory Sekolah</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
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
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="items.php"><i class="fas fa-boxes"></i> Data Barang</a></li>
                <li><a href="categories.php" class="active"><i class="fas fa-tags"></i> Kategori</a></li>
                
                <?php if($auth->hasRole(['Administrator', 'Manager', 'Procurement', 'Warehouse'])): ?>
                <li><a href="transactions.php"><i class="fas fa-exchange-alt"></i> Transaksi</a></li>
                <?php endif; ?>
                
                <?php if($auth->hasRole(['Administrator', 'Manager', 'Procurement'])): ?>
                <li><a href="requests.php"><i class="fas fa-clipboard-list"></i> Permintaan</a></li>
                <?php endif; ?>
                
                <?php if($auth->hasRole(['User'])): ?>
                <li><a href="my-requests.php"><i class="fas fa-paper-plane"></i> Permintaan Saya</a></li>
                <?php endif; ?>
                
                <?php if($auth->hasRole(['Administrator', 'Technician'])): ?>
                <li><a href="maintenance.php"><i class="fas fa-tools"></i> Maintenance</a></li>
                <?php endif; ?>
                
                <?php if($auth->hasRole(['Administrator', 'Manager', 'Auditor'])): ?>
                <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Laporan</a></li>
                <?php endif; ?>
                
                <?php if($auth->hasRole(['Administrator'])): ?>
                <li><a href="users.php"><i class="fas fa-users"></i> Manajemen User</a></li>
                <?php endif; ?>
                
                <li><a href="../config/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
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
                    <h1>Kategori Barang</h1>
                    <p>Kelola kategori untuk pengelompokan barang</p>
                </div>

                <div class="table-container">
                    <div class="table-header">
                        <h3>Daftar Kategori</h3>
                        <?php if($auth->hasRole(['Administrator', 'Manager'])): ?>
                        <button class="btn btn-primary" onclick="openModal('addModal')">
                            <i class="fas fa-plus"></i> Tambah Kategori
                        </button>
                        <?php endif; ?>
                    </div>
                    
                    <table>
                        <thead>
                            <tr>
                                <th>Nama Kategori</th>
                                <th>Deskripsi</th>
                                <th>Jumlah Barang</th>
                                <th>Tanggal Dibuat</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($categories as $category): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($category['category_name']); ?></td>
                                <td><?php echo htmlspecialchars($category['description']); ?></td>
                                <td>
                                    <span class="badge badge-info">
                                        <?php echo $category['item_count']; ?> barang
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($category['created_at'])); ?></td>
                                <td>
                                    <?php if($auth->hasRole(['Administrator', 'Manager'])): ?>
                                    <button class="btn btn-sm btn-warning" onclick="editCategory(<?php echo htmlspecialchars(json_encode($category)); ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="deleteCategory(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['category_name']); ?>')">
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
                <h3>Tambah Kategori Baru</h3>
                <span class="close" onclick="closeModal('addModal')">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label>Nama Kategori</label>
                    <input type="text" name="category_name" required>
                </div>
                <div class="form-group">
                    <label>Deskripsi</label>
                    <textarea name="description" rows="3"></textarea>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addModal')">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/categories.js"></script>
</body>
</html>
