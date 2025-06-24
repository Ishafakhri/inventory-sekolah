<?php
require_once '../config/auth.php';
require_once '../config/database.php';

$auth = new Auth();
$auth->requireLogin();

$database = new Database();
$db = $database->connect();

// Handle request actions
if ($_POST && isset($_POST['action'])) {
    if ($_POST['action'] == 'approve') {
        $stmt = $db->prepare("UPDATE requests SET status = 'APPROVED', approved_by = ?, approved_date = NOW() WHERE id = ?");
        $stmt->execute([$_SESSION['user_id'], $_POST['request_id']]);
    } elseif ($_POST['action'] == 'reject') {
        $stmt = $db->prepare("UPDATE requests SET status = 'REJECTED', approved_by = ?, approved_date = NOW(), notes = ? WHERE id = ?");
        $stmt->execute([$_SESSION['user_id'], $_POST['notes'], $_POST['request_id']]);
    }
    header("Location: requests.php");
    exit();
}

// Get requests
$stmt = $db->query("SELECT r.*, i.item_name, u.full_name as requester_name, a.full_name as approver_name 
                   FROM requests r 
                   JOIN items i ON r.item_id = i.id 
                   JOIN users u ON r.requester_id = u.id 
                   LEFT JOIN users a ON r.approved_by = a.id 
                   ORDER BY r.request_date DESC");
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Permintaan Barang - Sistem Inventory Sekolah</title>
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
                <li><a href="requests.php" class="active"><i class="fas fa-clipboard-list"></i> Permintaan</a></li>
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
                    <h1>Permintaan Barang</h1>
                    <p>Kelola permintaan barang dari pengguna</p>
                </div>

                <div class="table-container">
                    <div class="table-header">
                        <h3>Daftar Permintaan</h3>
                    </div>
                    
                    <table>
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Pemohon</th>
                                <th>Barang</th>
                                <th>Jumlah</th>
                                <th>Status</th>
                                <th>Tanggal</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($requests as $request): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($request['request_code']); ?></td>
                                <td><?php echo htmlspecialchars($request['requester_name']); ?></td>
                                <td><?php echo htmlspecialchars($request['item_name']); ?></td>
                                <td><?php echo $request['quantity_requested']; ?></td>
                                <td>
                                    <span class="badge badge-<?php 
                                        echo $request['status'] == 'PENDING' ? 'warning' : 
                                            ($request['status'] == 'APPROVED' ? 'success' : 'danger'); 
                                    ?>">
                                        <?php echo $request['status']; ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($request['request_date'])); ?></td>
                                <td>
                                    <?php if($request['status'] == 'PENDING' && $auth->hasRole(['Administrator', 'Manager'])): ?>
                                    <button class="btn btn-sm btn-success" onclick="approveRequest(<?php echo $request['id']; ?>)">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="rejectRequest(<?php echo $request['id']; ?>)">
                                        <i class="fas fa-times"></i>
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

    <script src="../assets/js/main.js"></script>
    <script>
        function approveRequest(id) {
            if(confirm('Apakah Anda yakin ingin menyetujui permintaan ini?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="approve">
                    <input type="hidden" name="request_id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function rejectRequest(id) {
            const notes = prompt('Alasan penolakan:');
            if(notes) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="reject">
                    <input type="hidden" name="request_id" value="${id}">
                    <input type="hidden" name="notes" value="${notes}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>
