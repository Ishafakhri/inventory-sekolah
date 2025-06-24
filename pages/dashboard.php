<?php
// filepath: c:\xampp\htdocs\inventory-sekolah\pages\dashboard.php
require_once '../config/auth.php';
require_once '../config/database.php';

$auth = new Auth();
$auth->requireLogin();

$database = new Database();
$db = $database->connect();

// Get statistics
$stats = [
    'total_items' => 0,
    'low_stock' => 0,
    'total_categories' => 0,
    'pending_requests' => 0
];

// Total items
$stmt = $db->query("SELECT COUNT(*) as count FROM items");
$stats['total_items'] = $stmt->fetch()['count'];

// Low stock items
$stmt = $db->query("SELECT COUNT(*) as count FROM items WHERE quantity <= min_stock");
$stats['low_stock'] = $stmt->fetch()['count'];

// Total categories
$stmt = $db->query("SELECT COUNT(*) as count FROM categories");
$stats['total_categories'] = $stmt->fetch()['count'];

// Pending requests
$stmt = $db->query("SELECT COUNT(*) as count FROM requests WHERE status = 'PENDING'");
$stats['pending_requests'] = $stmt->fetch()['count'];

// Get notification counts
$notification_counts = [
    'low_stock' => $stats['low_stock'],
    'pending_requests' => $stats['pending_requests'],
    'out_of_stock' => 0,
    'overdue_maintenance' => 0
];

// Out of stock items
$stmt = $db->query("SELECT COUNT(*) as count FROM items WHERE quantity = 0");
$notification_counts['out_of_stock'] = $stmt->fetch()['count'];

// Overdue maintenance (if maintenance table exists)
try {
    $stmt = $db->query("SELECT COUNT(*) as count FROM maintenance WHERE status = 'SCHEDULED' AND scheduled_date < CURDATE()");
    $notification_counts['overdue_maintenance'] = $stmt->fetch()['count'];
} catch (Exception $e) {
    $notification_counts['overdue_maintenance'] = 0;
}

$total_notifications = array_sum($notification_counts);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistem Inventory Sekolah</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="../assets/css/notifications.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
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
                <li><a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="items.php"><i class="fas fa-boxes"></i> Data Barang</a></li>
                <li><a href="categories.php"><i class="fas fa-tags"></i> Kategori</a></li>
                
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
                    <!-- Notification Bell -->
                    <div class="notification-bell" id="notificationBell">
                        <i class="fas fa-bell"></i>
                        <?php if($total_notifications > 0): ?>
                        <span class="notification-badge"><?php echo $total_notifications > 99 ? '99+' : $total_notifications; ?></span>
                        <?php endif; ?>
                        
                        <!-- Notification Dropdown -->
                        <div class="notification-dropdown" id="notificationDropdown">
                            <div class="notification-header">
                                <h4><i class="fas fa-bell"></i> Notifikasi (<?php echo $total_notifications; ?>)</h4>
                            </div>
                            <div class="notification-list">
                                <?php if($total_notifications > 0): ?>
                                
                                    <?php if($notification_counts['out_of_stock'] > 0): ?>
                                    <div class="notification-item priority-high">
                                        <div class="notification-icon-wrapper">
                                            <div class="notification-icon-item danger">
                                                <i class="fas fa-exclamation-circle"></i>
                                            </div>
                                            <div class="notification-content">
                                                <div class="notification-title">Stok Habis</div>
                                                <div class="notification-desc"><?php echo $notification_counts['out_of_stock']; ?> barang kehabisan stok</div>
                                                <span class="notification-stock danger">Perlu segera diisi ulang</span>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if($notification_counts['overdue_maintenance'] > 0): ?>
                                    <div class="notification-item priority-high">
                                        <div class="notification-icon-wrapper">
                                            <div class="notification-icon-item danger">
                                                <i class="fas fa-tools"></i>
                                            </div>
                                            <div class="notification-content">
                                                <div class="notification-title">Maintenance Terlambat</div>
                                                <div class="notification-desc"><?php echo $notification_counts['overdue_maintenance']; ?> maintenance melewati jadwal</div>
                                                <span class="notification-stock danger">Segera lakukan maintenance</span>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if($notification_counts['low_stock'] > 0): ?>
                                    <div class="notification-item priority-medium">
                                        <div class="notification-icon-wrapper">
                                            <div class="notification-icon-item warning">
                                                <i class="fas fa-exclamation-triangle"></i>
                                            </div>
                                            <div class="notification-content">
                                                <div class="notification-title">Stok Menipis</div>
                                                <div class="notification-desc"><?php echo $notification_counts['low_stock']; ?> barang stok di bawah minimum</div>
                                                <span class="notification-stock warning">Perlu perhatian</span>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if($notification_counts['pending_requests'] > 0): ?>
                                    <div class="notification-item priority-medium">
                                        <div class="notification-icon-wrapper">
                                            <div class="notification-icon-item warning">
                                                <i class="fas fa-clock"></i>
                                            </div>
                                            <div class="notification-content">
                                                <div class="notification-title">Permintaan Menunggu</div>
                                                <div class="notification-desc"><?php echo $notification_counts['pending_requests']; ?> permintaan menunggu approval</div>
                                                <span class="notification-stock warning">Perlu ditinjau</span>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    
                                <?php else: ?>
                                <div class="no-notifications">
                                    <i class="fas fa-check-circle"></i>
                                    <p>Semua sistem berjalan normal</p>
                                    <small>Tidak ada notifikasi penting</small>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="notification-footer">
                                <a href="items.php">Kelola Inventory</a>
                            </div>
                        </div>
                    </div>
                    
                    <span>Selamat datang, <?php echo $_SESSION['full_name']; ?></span>
                    <div class="user-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="content">
                <div class="page-title">
                    <h1>Dashboard</h1>
                    <p>Ringkasan sistem inventory sekolah</p>
                </div>

                <!-- Notification Alerts -->
                <?php if($notification_counts['out_of_stock'] > 0): ?>
                <div class="notification-alert-banner danger">
                    <div class="alert-icon">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <div class="alert-content">
                        <strong>Peringatan Kritis!</strong>
                        <?php echo $notification_counts['out_of_stock']; ?> barang kehabisan stok. 
                        <a href="items.php">Lihat detail</a>
                    </div>
                    <button class="alert-close" onclick="this.parentElement.style.display='none'">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <?php endif; ?>

                <?php if($notification_counts['overdue_maintenance'] > 0): ?>
                <div class="notification-alert-banner warning">
                    <div class="alert-icon">
                        <i class="fas fa-tools"></i>
                    </div>
                    <div class="alert-content">
                        <strong>Maintenance Terlambat!</strong>
                        <?php echo $notification_counts['overdue_maintenance']; ?> maintenance melewati jadwal. 
                        <a href="maintenance.php">Lihat jadwal</a>
                    </div>
                    <button class="alert-close" onclick="this.parentElement.style.display='none'">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <?php endif; ?>

                <!-- Statistics Cards -->
                <div class="stats-grid">
                    <div class="stat-card primary">
                        <div class="icon">
                            <i class="fas fa-boxes"></i>
                        </div>
                        <h3><?php echo $stats['total_items']; ?></h3>
                        <p>Total Barang</p>
                    </div>
                    
                    <div class="stat-card <?php echo $stats['low_stock'] > 0 ? 'danger' : 'warning'; ?> <?php echo $stats['low_stock'] > 0 ? 'animated-pulse' : ''; ?>">
                        <div class="icon">
                            <i class="fas fa-exclamation-triangle"></i>
                            <?php if($stats['low_stock'] > 0): ?>
                            <span class="notification-dot"></span>
                            <?php endif; ?>
                        </div>
                        <h3><?php echo $stats['low_stock']; ?></h3>
                        <p>Stok Menipis</p>
                    </div>
                    
                    <div class="stat-card success">
                        <div class="icon">
                            <i class="fas fa-tags"></i>
                        </div>
                        <h3><?php echo $stats['total_categories']; ?></h3>
                        <p>Kategori</p>
                    </div>
                    
                    <div class="stat-card <?php echo $stats['pending_requests'] > 0 ? 'warning' : 'info'; ?> <?php echo $stats['pending_requests'] > 0 ? 'animated-pulse' : ''; ?>">
                        <div class="icon">
                            <i class="fas fa-clock"></i>
                            <?php if($stats['pending_requests'] > 0): ?>
                            <span class="notification-dot"></span>
                            <?php endif; ?>
                        </div>
                        <h3><?php echo $stats['pending_requests']; ?></h3>
                        <p>Permintaan Pending</p>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="table-container">
                    <div class="table-header">
                        <h3><i class="fas fa-activity"></i> Aktivitas Terbaru</h3>
                        <div class="notification-summary">
                            <span class="summary-item">
                                <i class="fas fa-bell"></i>
                                <?php echo $total_notifications; ?> notifikasi aktif
                            </span>
                        </div>
                    </div>
                    <div style="padding: 20px; text-align: center; color: #666;">
                        <i class="fas fa-chart-line" style="font-size: 48px; margin-bottom: 15px;"></i>
                        <p>Dashboard aktivitas dan monitoring sistem</p>
                        <div class="quick-actions">
                            <a href="items.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-boxes"></i> Kelola Barang
                            </a>
                            <?php if($auth->hasRole(['Administrator', 'Manager', 'Procurement'])): ?>
                            <a href="requests.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-clipboard-list"></i> Review Permintaan
                            </a>
                            <?php endif; ?>
                            <?php if($auth->hasRole(['Administrator', 'Technician'])): ?>
                            <a href="maintenance.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-tools"></i> Maintenance
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/notifications.js"></script>
    <style>
        /* Enhanced Modal Styles for Scrollable Content */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            justify-content: center;
            align-items: flex-start;
            z-index: 1000;
            overflow-y: auto;
            padding: 20px 0;
        }
        
        .modal-content {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            margin: auto;
            position: relative;
        }
        
        .modal-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
            position: sticky;
            top: 0;
            background: white;
            border-radius: 10px 10px 0 0;
            z-index: 1001;
        }
        
        .modal-body {
            padding: 20px;
            max-height: none;
        }
        
        .modal-footer {
            padding: 15px 20px;
            border-top: 1px solid #eee;
            position: sticky;
            bottom: 0;
            background: white;
            border-radius: 0 0 10px 10px;
            z-index: 1001;
        }
        
        /* Form spacing improvements */
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        /* Fix icon positioning in stat cards */
        .stat-card .icon {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            margin-bottom: 15px;
        }
        
        .stat-card .icon i {
            font-size: 24px;
            color: white;
            z-index: 1;
        }
        
        .notification-dot {
            position: absolute;
            top: 5px;
            right: 5px;
            width: 12px;
            height: 12px;
            background: #ff4757;
            border-radius: 50%;
            border: 2px solid white;
            animation: pulseNotificationDot 1.5s infinite;
            z-index: 2;
        }
        
        @keyframes pulseNotificationDot {
            0%, 100% { 
                opacity: 1; 
                transform: scale(1); 
            }
            50% { 
                opacity: 0.7; 
                transform: scale(1.2); 
            }
        }
        
        /* Mobile responsiveness */
        @media (max-width: 768px) {
            .modal {
                padding: 10px;
            }
            
            .modal-content {
                width: 95%;
                max-height: 95vh;
            }
            
            .form-row {
                grid-template-columns: 1fr;
                gap: 10px;
            }
            
            .modal-header {
                padding: 15px;
            }
            
            .modal-body {
                padding: 15px;
            }
            
            .modal-footer {
                padding: 15px;
            }
        }
    </style>
    <script src="../assets/js/sidebar.js"></script>
    <script>
        // Initialize notification system
        document.addEventListener('DOMContentLoaded', function() {
            // Notification bell click handler
            const notificationBell = document.getElementById('notificationBell');
            const notificationDropdown = document.getElementById('notificationDropdown');
            let isDropdownOpen = false;
            
            if (notificationBell) {
                notificationBell.addEventListener('click', function(e) {
                    e.stopPropagation();
                    isDropdownOpen = !isDropdownOpen;
                    notificationDropdown.classList.toggle('show', isDropdownOpen);
                });
            }
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function() {
                if (isDropdownOpen) {
                    notificationDropdown.classList.remove('show');
                    isDropdownOpen = false;
                }
            });
            
            // Auto-refresh notifications every 5 minutes
            setInterval(function() {
                window.location.reload();
            }, 300000);
            
            // Show alert for critical notifications
            <?php if($notification_counts['out_of_stock'] > 0): ?>
            setTimeout(function() {
                if (typeof showNotificationAlert === 'function') {
                    showNotificationAlert('Stok Habis', '<?php echo $notification_counts['out_of_stock']; ?> barang kehabisan stok!', 'danger');
                }
            }, 2000);
            <?php endif; ?>
        });
        
        // Function to fetch and display low stock details
        async function fetchLowStockDetails() {
            try {
                const response = await fetch('../api/low-stock-details.php');
                const data = await response.json();
                
                if (data.success) {
                    showLowStockModal(data.items);
                } else {
                    alert('Gagal memuat data stok menipis');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat memuat data');
            }
        }
        
        // Function to fetch and display overdue maintenance details
        async function fetchOverdueMaintenanceDetails() {
            try {
                const response = await fetch('../api/overdue-maintenance-details.php');
                const data = await response.json();
                
                if (data.success) {
                    showMaintenanceModal(data.items);
                } else {
                    alert('Gagal memuat data maintenance terlambat');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat memuat data');
            }
        }
        
        // Function to show low stock modal
        function showLowStockModal(items) {
            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.style.display = 'block';
            
            let itemsHtml = '';
            items.forEach(item => {
                const stockPercentage = ((item.quantity / item.min_stock) * 100).toFixed(1);
                const statusClass = item.quantity === 0 ? 'danger' : 'warning';
                const statusText = item.quantity === 0 ? 'HABIS' : 'MENIPIS';
                
                itemsHtml += `
                    <tr>
                        <td>
                            <strong>${item.item_name}</strong><br>
                            <small>${item.item_code}</small>
                        </td>
                        <td>${item.category_name || 'Tidak ada'}</td>
                        <td>
                            <span class="badge badge-${statusClass}">
                                ${item.quantity} ${item.unit}
                            </span>
                        </td>
                        <td>${item.min_stock} ${item.unit}</td>
                        <td>
                            <span class="badge badge-${statusClass}">
                                ${statusText} (${stockPercentage}%)
                            </span>
                        </td>
                        <td>${item.location || 'Tidak diketahui'}</td>
                    </tr>
                `;
            });
            
            modal.innerHTML = `
                <div class="modal-content" style="max-width: 900px;">
                    <div class="modal-header">
                        <h3><i class="fas fa-exclamation-triangle"></i> Detail Stok Menipis</h3>
                        <span class="close" onclick="this.parentElement.parentElement.parentElement.remove()">&times;</span>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <i class="fas fa-info-circle"></i>
                            Ditemukan ${items.length} barang dengan stok di bawah minimum atau habis.
                        </div>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Nama Barang</th>
                                    <th>Kategori</th>
                                    <th>Stok Saat Ini</th>
                                    <th>Min. Stok</th>
                                    <th>Status</th>
                                    <th>Lokasi</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${itemsHtml}
                            </tbody>
                        </table>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" onclick="this.parentElement.parentElement.parentElement.remove()">Tutup</button>
                        <a href="items.php" class="btn btn-primary">Kelola Barang</a>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
        }
        
        // Function to show maintenance modal
        function showMaintenanceModal(items) {
            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.style.display = 'block';
            
            let itemsHtml = '';
            items.forEach(item => {
                const daysPast = Math.floor((new Date() - new Date(item.scheduled_date)) / (1000 * 60 * 60 * 24));
                const urgencyClass = daysPast > 30 ? 'danger' : (daysPast > 7 ? 'warning' : 'info');
                
                itemsHtml += `
                    <tr>
                        <td>
                            <strong>${item.maintenance_code}</strong><br>
                            <small>${item.maintenance_type}</small>
                        </td>
                        <td>
                            <strong>${item.item_name}</strong><br>
                            <small>${item.item_code}</small>
                        </td>
                        <td>${new Date(item.scheduled_date).toLocaleDateString('id-ID')}</td>
                        <td>
                            <span class="badge badge-${urgencyClass}">
                                ${daysPast} hari
                            </span>
                        </td>
                        <td>${item.technician_name}</td>
                        <td>
                            <button class="btn btn-sm btn-primary" onclick="window.location.href='maintenance.php'">
                                <i class="fas fa-tools"></i> Proses
                            </button>
                        </td>
                    </tr>
                `;
            });
            
            modal.innerHTML = `
                <div class="modal-content" style="max-width: 900px;">
                    <div class="modal-header">
                        <h3><i class="fas fa-tools"></i> Detail Maintenance Terlambat</h3>
                        <span class="close" onclick="this.parentElement.parentElement.parentElement.remove()">&times;</span>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i>
                            Ditemukan ${items.length} maintenance yang melewati jadwal.
                        </div>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Kode Maintenance</th>
                                    <th>Barang</th>
                                    <th>Jadwal</th>
                                    <th>Terlambat</th>
                                    <th>Teknisi</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${itemsHtml}
                            </tbody>
                        </table>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" onclick="this.parentElement.parentElement.parentElement.remove()">Tutup</button>
                        <a href="maintenance.php" class="btn btn-warning">Kelola Maintenance</a>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
        }
        
        // Add click handlers to notification items
        document.addEventListener('click', function(e) {
            if (e.target.closest('.notification-item')) {
                const item = e.target.closest('.notification-item');
                const title = item.querySelector('.notification-title').textContent;
                
                if (title === 'Stok Menipis' || title === 'Stok Habis') {
                    fetchLowStockDetails();
                } else if (title === 'Maintenance Terlambat') {
                    fetchOverdueMaintenanceDetails();
                }
            }
        });
        
        function showNotificationAlert(title, message, type = 'warning') {
            const alert = document.createElement('div');
            alert.className = `notification-alert ${type} show`;
            alert.innerHTML = `
                <div class="notification-alert-header">
                    <div class="notification-alert-icon ${type}">
                        <i class="fas fa-${type === 'danger' ? 'exclamation-circle' : 'exclamation-triangle'}"></i>
                    </div>
                    <div class="notification-alert-title">${title}</div>
                    <button class="notification-alert-close" onclick="this.parentElement.parentElement.remove()">×</button>
                </div>
                <div class="notification-alert-body">${message}</div>
            `;
            
            document.body.appendChild(alert);
            
            // Auto remove after 8 seconds
            setTimeout(() => {
                if (alert.parentElement) {
                    alert.remove();
                }
            }, 8000);
        }
    </script>
</body>
</html>