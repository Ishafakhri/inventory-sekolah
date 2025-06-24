<?php
require_once '../config/auth.php';
require_once '../config/database.php';

$auth = new Auth();
$auth->requireLogin();

$database = new Database();
$db = $database->connect();

// Handle transaction
if ($_POST && isset($_POST['action'])) {
    if ($_POST['action'] == 'add') {
        try {
            $db->beginTransaction();
            
            // Generate transaction code
            $transaction_code = 'TRX' . date('Ymd') . sprintf('%04d', rand(1, 9999));
            
            // Insert transaction
            $stmt = $db->prepare("INSERT INTO transactions (transaction_code, item_id, user_id, transaction_type, quantity, description) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $transaction_code,
                $_POST['item_id'],
                $_SESSION['user_id'],
                $_POST['transaction_type'],
                $_POST['quantity'],
                $_POST['description']
            ]);
            
            // Update item quantity
            if ($_POST['transaction_type'] == 'IN') {
                $stmt = $db->prepare("UPDATE items SET quantity = quantity + ? WHERE id = ?");
            } else {
                $stmt = $db->prepare("UPDATE items SET quantity = quantity - ? WHERE id = ?");
            }
            $stmt->execute([$_POST['quantity'], $_POST['item_id']]);
            
            $db->commit();
            header("Location: transactions.php?success=1");
            exit();
        } catch(Exception $e) {
            $db->rollback();
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Get transactions
$stmt = $db->query("SELECT t.*, i.item_name, u.full_name FROM transactions t 
                   JOIN items i ON t.item_id = i.id 
                   JOIN users u ON t.user_id = u.id 
                   ORDER BY t.transaction_date DESC");
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get items for dropdown
$stmt = $db->query("SELECT * FROM items ORDER BY item_name");
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaksi - Sistem Inventory Sekolah</title>
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
                <li><a href="categories.php"><i class="fas fa-tags"></i> Kategori</a></li>
                
                <?php if($auth->hasRole(['Administrator', 'Manager', 'Procurement', 'Warehouse'])): ?>
                <li><a href="transactions.php" class="active"><i class="fas fa-exchange-alt"></i> Transaksi</a></li>
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

            <div class="content">
                <div class="page-title">
                    <h1>Transaksi Barang</h1>
                    <p>Kelola transaksi masuk dan keluar barang</p>
                </div>

                <?php if(isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    Transaksi berhasil disimpan!
                </div>
                <?php endif; ?>

                <?php if(isset($error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
                <?php endif; ?>

                <div class="table-container">
                    <div class="table-header">
                        <h3>Riwayat Transaksi</h3>
                        <button class="btn btn-primary" onclick="openModal('addModal')">
                            <i class="fas fa-plus"></i> Tambah Transaksi
                        </button>
                    </div>
                    
                    <table>
                        <thead>
                            <tr>
                                <th>Kode Transaksi</th>
                                <th>Barang</th>
                                <th>Jenis</th>
                                <th>Jumlah</th>
                                <th>User</th>
                                <th>Tanggal</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($transactions as $transaction): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($transaction['transaction_code']); ?></td>
                                <td><?php echo htmlspecialchars($transaction['item_name']); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $transaction['transaction_type'] == 'IN' ? 'success' : 'warning'; ?>">
                                        <?php echo $transaction['transaction_type'] == 'IN' ? 'MASUK' : 'KELUAR'; ?>
                                    </span>
                                </td>
                                <td><?php echo $transaction['quantity']; ?></td>
                                <td><?php echo htmlspecialchars($transaction['full_name']); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($transaction['transaction_date'])); ?></td>
                                <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Transaction Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Tambah Transaksi</h3>
                <span class="close" onclick="closeModal('addModal')">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label>Barang</label>
                    <select name="item_id" required>
                        <option value="">Pilih Barang</option>
                        <?php foreach($items as $item): ?>
                        <option value="<?php echo $item['id']; ?>"><?php echo htmlspecialchars($item['item_name']); ?> (Stok: <?php echo $item['quantity']; ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Jenis Transaksi</label>
                    <select name="transaction_type" required>
                        <option value="">Pilih Jenis</option>
                        <option value="IN">Barang Masuk</option>
                        <option value="OUT">Barang Keluar</option>
                        <option value="ADJUSTMENT">Penyesuaian</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Jumlah</label>
                    <input type="number" name="quantity" min="1" required>
                </div>
                <div class="form-group">
                    <label>Keterangan</label>
                    <textarea name="description" rows="3" placeholder="Keterangan transaksi"></textarea>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addModal')">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
</body>
</html>
