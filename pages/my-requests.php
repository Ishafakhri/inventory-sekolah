<?php
require_once '../config/auth.php';
require_once '../config/database.php';

$auth = new Auth();
$auth->requireLogin();

$database = new Database();
$db = $database->connect();

// Handle new request
if ($_POST && isset($_POST['action']) && $_POST['action'] == 'add_request') {
    try {
        // Generate request code
        $request_code = 'REQ' . date('Ymd') . sprintf('%04d', rand(1, 9999));
        
        // Insert request
        $stmt = $db->prepare("INSERT INTO requests (request_code, requester_id, item_id, quantity_requested, status, notes) VALUES (?, ?, ?, ?, 'PENDING', ?)");
        $stmt->execute([
            $request_code,
            $_SESSION['user_id'],
            $_POST['item_id'],
            $_POST['quantity_requested'],
            $_POST['notes']
        ]);
        
        $success = "Permintaan berhasil diajukan dengan kode: " . $request_code;
    } catch(Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Get user's requests
$stmt = $db->prepare("SELECT r.*, i.item_name, i.quantity as available_stock, i.unit, a.full_name as approver_name 
                     FROM requests r 
                     JOIN items i ON r.item_id = i.id 
                     LEFT JOIN users a ON r.approved_by = a.id 
                     WHERE r.requester_id = ? 
                     ORDER BY r.request_date DESC");
$stmt->execute([$_SESSION['user_id']]);
$my_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get available items for request
$stmt = $db->query("SELECT * FROM items WHERE quantity > 0 ORDER BY item_name");
$available_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Permintaan Saya - Sistem Inventory Sekolah</title>
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
                <li><a href="transactions.php"><i class="fas fa-exchange-alt"></i> Transaksi</a></li>
                <?php endif; ?>
                
                <?php if($auth->hasRole(['Administrator', 'Manager', 'Procurement'])): ?>
                <li><a href="requests.php"><i class="fas fa-clipboard-list"></i> Permintaan</a></li>
                <?php endif; ?>
                
                <?php if($auth->hasRole(['User'])): ?>
                <li><a href="my-requests.php" class="active"><i class="fas fa-paper-plane"></i> Permintaan Saya</a></li>
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
                    <h1>Permintaan Saya</h1>
                    <p>Ajukan permintaan barang dan pantau statusnya</p>
                </div>

                <?php if(isset($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success; ?>
                </div>
                <?php endif; ?>

                <?php if(isset($error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
                <?php endif; ?>

                <!-- Request Form -->
                <div class="table-container">
                    <div class="table-header">
                        <h3><i class="fas fa-plus-circle"></i> Ajukan Permintaan Baru</h3>
                    </div>
                    <div style="padding: 20px;">
                        <form method="POST" class="request-form">
                            <input type="hidden" name="action" value="add_request">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Pilih Barang</label>
                                    <select name="item_id" id="item_select" required onchange="updateStock()">
                                        <option value="">-- Pilih Barang --</option>
                                        <?php foreach($available_items as $item): ?>
                                        <option value="<?php echo $item['id']; ?>" 
                                                data-stock="<?php echo $item['quantity']; ?>" 
                                                data-unit="<?php echo $item['unit']; ?>">
                                            <?php echo htmlspecialchars($item['item_name']); ?> 
                                            (Stok: <?php echo $item['quantity']; ?> <?php echo $item['unit']; ?>)
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Jumlah Diminta</label>
                                    <input type="number" name="quantity_requested" id="quantity_input" min="1" required>
                                    <small id="stock_info" style="color: #666;"></small>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Keterangan/Keperluan</label>
                                <textarea name="notes" rows="3" placeholder="Jelaskan keperluan penggunaan barang..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i> Ajukan Permintaan
                            </button>
                        </form>
                    </div>
                </div>

                <!-- My Requests Table -->
                <div class="table-container">
                    <div class="table-header">
                        <h3><i class="fas fa-list"></i> Riwayat Permintaan Saya</h3>
                    </div>
                    
                    <table>
                        <thead>
                            <tr>
                                <th>Kode Permintaan</th>
                                <th>Barang</th>
                                <th>Jumlah</th>
                                <th>Status</th>
                                <th>Tanggal Permintaan</th>
                                <th>Disetujui Oleh</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($my_requests)): ?>
                            <tr>
                                <td colspan="7" class="text-center">
                                    <i class="fas fa-inbox" style="font-size: 48px; color: #ddd; margin-bottom: 10px;"></i>
                                    <p style="color: #666;">Belum ada permintaan yang diajukan</p>
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach($my_requests as $request): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($request['request_code']); ?></td>
                                <td><?php echo htmlspecialchars($request['item_name']); ?></td>
                                <td><?php echo $request['quantity_requested']; ?> <?php echo htmlspecialchars($request['unit']); ?></td>
                                <td>
                                    <span class="badge badge-<?php 
                                        echo $request['status'] == 'PENDING' ? 'warning' : 
                                            ($request['status'] == 'APPROVED' ? 'success' : 'danger'); 
                                    ?>">
                                        <?php 
                                        $status_text = [
                                            'PENDING' => 'Menunggu',
                                            'APPROVED' => 'Disetujui', 
                                            'REJECTED' => 'Ditolak',
                                            'COMPLETED' => 'Selesai'
                                        ];
                                        echo $status_text[$request['status']] ?? $request['status']; 
                                        ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($request['request_date'])); ?></td>
                                <td><?php echo $request['approver_name'] ? htmlspecialchars($request['approver_name']) : '-'; ?></td>
                                <td><?php echo htmlspecialchars($request['notes']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script>
        function updateStock() {
            const select = document.getElementById('item_select');
            const quantityInput = document.getElementById('quantity_input');
            const stockInfo = document.getElementById('stock_info');
            
            if (select.value) {
                const option = select.options[select.selectedIndex];
                const stock = option.getAttribute('data-stock');
                const unit = option.getAttribute('data-unit');
                
                quantityInput.max = stock;
                stockInfo.textContent = `Stok tersedia: ${stock} ${unit}`;
                
                if (parseInt(quantityInput.value) > parseInt(stock)) {
                    quantityInput.value = stock;
                }
            } else {
                quantityInput.max = '';
                stockInfo.textContent = '';
            }
        }
        
        // Validate quantity on input
        document.getElementById('quantity_input').addEventListener('input', function() {
            const max = this.max;
            if (max && parseInt(this.value) > parseInt(max)) {
                this.value = max;
                alert('Jumlah yang diminta tidak boleh melebihi stok yang tersedia');
            }
        });
    </script>
</body>
</html>
