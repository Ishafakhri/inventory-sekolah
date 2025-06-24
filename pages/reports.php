<?php
require_once '../config/auth.php';
require_once '../config/database.php';

$auth = new Auth();
$auth->requireLogin();

if(!$auth->hasRole(['Administrator', 'Manager', 'Auditor'])) {
    header("Location: dashboard.php");
    exit();
}

$database = new Database();
$db = $database->connect();

// Get date filters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-01-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Initialize all maintenance arrays
$maintenance_by_status = [];
$monthly_maintenance = [];
$maintenance_by_type = [];
$cost_stats = ['avg_cost' => 0, 'total_cost' => 0];
$all_maintenance = [];
$completed_maintenance = [];
$overdue_maintenance = [];
$upcoming_maintenance = [];

// Check if maintenance table exists first
$maintenance_exists = false;
try {
    $stmt = $db->query("SHOW TABLES LIKE 'maintenance'");
    $maintenance_exists = $stmt->rowCount() > 0;
} catch (Exception $e) {
    $maintenance_exists = false;
}

if ($maintenance_exists) {
    try {
        // Total maintenance by status (all time, not filtered by date)
        $stmt = $db->query("SELECT status, COUNT(*) as count FROM maintenance GROUP BY status");
        $maintenance_by_status = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Monthly maintenance completion (filtered by date)
        $stmt = $db->prepare("SELECT DATE_FORMAT(completed_date, '%Y-%m') as month, COUNT(*) as count 
                             FROM maintenance 
                             WHERE status = 'COMPLETED' AND completed_date IS NOT NULL 
                             AND completed_date BETWEEN ? AND ?
                             GROUP BY DATE_FORMAT(completed_date, '%Y-%m')
                             ORDER BY month");
        $stmt->execute([$start_date, $end_date]);
        $monthly_maintenance = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Maintenance by type (all time)
        $stmt = $db->query("SELECT maintenance_type, COUNT(*) as count FROM maintenance GROUP BY maintenance_type");
        $maintenance_by_type = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Average maintenance cost (completed only)
        $stmt = $db->query("SELECT AVG(COALESCE(cost, 0)) as avg_cost, SUM(COALESCE(cost, 0)) as total_cost 
                           FROM maintenance WHERE status = 'COMPLETED'");
        $cost_result = $stmt->fetch(PDO::FETCH_ASSOC);
        $cost_stats = [
            'avg_cost' => $cost_result['avg_cost'] ?: 0,
            'total_cost' => $cost_result['total_cost'] ?: 0
        ];
        
        // Get ALL maintenance records (filtered by date)
        $stmt = $db->prepare("SELECT m.*, i.item_name, i.item_code, u.full_name as technician_name, c.category_name
                             FROM maintenance m 
                             JOIN items i ON m.item_id = i.id 
                             JOIN users u ON m.technician_id = u.id 
                             LEFT JOIN categories c ON i.category_id = c.id 
                             WHERE m.created_at BETWEEN ? AND ?
                             ORDER BY m.created_at DESC");
        $stmt->execute([$start_date, $end_date]);
        $all_maintenance = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get completed maintenance (filtered by completion date)
        $stmt = $db->prepare("SELECT m.*, i.item_name, i.item_code, u.full_name as technician_name, c.category_name
                             FROM maintenance m 
                             JOIN items i ON m.item_id = i.id 
                             JOIN users u ON m.technician_id = u.id 
                             LEFT JOIN categories c ON i.category_id = c.id 
                             WHERE m.status = 'COMPLETED' AND m.completed_date IS NOT NULL
                             AND m.completed_date BETWEEN ? AND ?
                             ORDER BY m.completed_date DESC");
        $stmt->execute([$start_date, $end_date]);
        $completed_maintenance = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get overdue maintenance (not filtered by date)
        $stmt = $db->query("SELECT m.*, i.item_name, i.item_code, u.full_name as technician_name, c.category_name,
                                  DATEDIFF(CURDATE(), m.scheduled_date) as days_overdue
                           FROM maintenance m 
                           JOIN items i ON m.item_id = i.id 
                           JOIN users u ON m.technician_id = u.id 
                           LEFT JOIN categories c ON i.category_id = c.id 
                           WHERE m.status = 'SCHEDULED' AND m.scheduled_date < CURDATE()
                           ORDER BY m.scheduled_date ASC");
        $overdue_maintenance = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get upcoming maintenance (next 30 days)
        $stmt = $db->query("SELECT m.*, i.item_name, i.item_code, u.full_name as technician_name, c.category_name,
                                  DATEDIFF(m.scheduled_date, CURDATE()) as days_until
                           FROM maintenance m 
                           JOIN items i ON m.item_id = i.id 
                           JOIN users u ON m.technician_id = u.id 
                           LEFT JOIN categories c ON i.category_id = c.id 
                           WHERE m.status = 'SCHEDULED' AND m.scheduled_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                           ORDER BY m.scheduled_date ASC");
        $upcoming_maintenance = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        // Keep arrays empty if there's an error
        error_log("Maintenance query error: " . $e->getMessage());
    }
}

// Get ALL items for inventory report
$stmt = $db->query("SELECT i.*, c.category_name 
                   FROM items i 
                   LEFT JOIN categories c ON i.category_id = c.id 
                   ORDER BY (i.quantity - i.min_stock) ASC");
$all_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get low stock items
$low_stock_items = array_filter($all_items, function($item) {
    return $item['quantity'] <= $item['min_stock'];
});

// Get general statistics
$stmt = $db->query("SELECT COUNT(*) as total_items FROM items");
$total_items = $stmt->fetch()['total_items'];

$stmt = $db->query("SELECT COUNT(*) as total_categories FROM categories");
$total_categories = $stmt->fetch()['total_categories'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan - Sistem Inventory Sekolah</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="../assets/css/sidebar.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .report-tabs {
            display: flex;
            background: white;
            border-radius: 10px;
            padding: 5px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .report-tab {
            flex: 1;
            padding: 12px 20px;
            text-align: center;
            text-decoration: none;
            color: #666;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .report-tab.active {
            background: #667eea;
            color: white;
        }
        
        .chart-container {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .chart-wrapper {
            position: relative;
            height: 300px;
        }
        
        .maintenance-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .maintenance-stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .maintenance-stat-card .icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 20px;
            color: white;
        }
        
        .maintenance-stat-card.tools .icon {
            background: linear-gradient(135deg, #667eea, #764ba2);
        }
        
        .maintenance-stat-card.cost .icon {
            background: linear-gradient(135deg, #f093fb, #f5576c);
        }
        
        .maintenance-stat-card.items .icon {
            background: linear-gradient(135deg, #4facfe, #00f2fe);
        }
        
        .maintenance-stat-card h3 {
            font-size: 24px;
            margin: 10px 0 5px 0;
            color: #333;
        }
        
        .filter-section {
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
        
        .report-section {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            overflow: hidden;
        }
        
        .report-section-header {
            background: #f8f9fa;
            padding: 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .export-buttons {
            display: flex;
            gap: 10px;
        }
        
        .overview-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .overview-stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .overview-stat-card .icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 20px;
            color: white;
        }
        
        .overview-stat-card.inventory .icon {
            background: linear-gradient(135deg, #667eea, #764ba2);
        }
        
        .overview-stat-card.categories .icon {
            background: linear-gradient(135deg, #36d1dc, #5b86e5);
        }
        
        .section-divider {
            margin: 50px 0 30px 0;
            text-align: center;
            position: relative;
        }
        
        .section-divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #e0e0e0;
            z-index: 1;
        }
        
        .section-divider h2 {
            background: #f8f9fa;
            padding: 0 30px;
            margin: 0;
            display: inline-block;
            position: relative;
            z-index: 2;
            color: #333;
            font-size: 24px;
        }
        
        @media print {
            .no-print {
                display: none !important;
            }
            
            .chart-wrapper {
                height: 200px;
            }
        }
        
        .alert-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            border-left: 4px solid;
        }
        
        .alert-card.danger {
            border-left-color: #dc3545;
        }
        
        .alert-card.warning {
            border-left-color: #ffc107;
        }
        
        .alert-card .alert-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: white;
        }
        
        .alert-card.danger .alert-icon {
            background: #dc3545;
        }
        
        .alert-card.warning .alert-icon {
            background: #ffc107;
        }
        
        .alert-content h4 {
            margin: 0 0 5px 0;
            color: #333;
        }
        
        .alert-content p {
            margin: 0 0 10px 0;
            color: #666;
            font-size: 14px;
        }
        
        .badge-success {
            background: #28a745;
            color: white;
            border-radius: 10px;
            padding: 2px 6px;
        }
        
        .text-muted {
            color: #6c757d;
        }
        
        /* Sidebar minimize/maximize styles */
        .sidebar {
            transition: width 0.3s ease;
        }
        
        .sidebar.minimized {
            width: 80px;
        }
        
        .sidebar.minimized .sidebar-header h3,
        .sidebar.minimized .sidebar-header p,
        .sidebar.minimized .sidebar-nav li a span {
            display: none;
        }
        
        .sidebar.minimized .sidebar-nav li a {
            justify-content: center;
            padding: 15px 0;
        }
        
        .sidebar.minimized .sidebar-nav li a i {
            margin-right: 0;
        }
        
        .main-content {
            transition: margin-left 0.3s ease;
        }
        
        .main-content.expanded {
            margin-left: 80px;
        }
    </style>
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
                <li><a href="items.php"><i class="fas fa-boxes"></i> <span>Data Barang</span></a></li>
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
                <li><a href="reports.php" class="active"><i class="fas fa-chart-bar"></i> <span>Laporan</span></a></li>
                <?php endif; ?>
                
                <?php if($auth->hasRole(['Administrator'])): ?>
                <li><a href="users.php"><i class="fas fa-users"></i> <span>Manajemen User</span></a></li>
                <?php endif; ?>
                
                <li><a href="../config/logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
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

            <!-- Content -->
            <div class="content">
                <div class="page-title">
                    <h1>Laporan Sistem Inventory</h1>
                    <p>Laporan komprehensif inventory dan maintenance barang elektronik</p>
                </div>

                <!-- Date Filter -->
                <div class="filter-section no-print">
                    <form class="filter-form" method="GET">
                        <div class="form-group">
                            <label>Dari Tanggal</label>
                            <input type="date" name="start_date" value="<?php echo $start_date; ?>">
                        </div>
                        <div class="form-group">
                            <label>Sampai Tanggal</label>
                            <input type="date" name="end_date" value="<?php echo $end_date; ?>">
                        </div>
                        <div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter"></i> Filter
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Overview Statistics -->
                <div class="overview-stats-grid">
                    <div class="overview-stat-card inventory">
                        <div class="icon">
                            <i class="fas fa-boxes"></i>
                        </div>
                        <h3><?php echo $total_items; ?></h3>
                        <p>Total Barang</p>
                    </div>
                    <div class="overview-stat-card categories">
                        <div class="icon">
                            <i class="fas fa-tags"></i>
                        </div>
                        <h3><?php echo $total_categories; ?></h3>
                        <p>Total Kategori</p>
                    </div>
                    <div class="overview-stat-card inventory">
                        <div class="icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <h3><?php echo count($low_stock_items); ?></h3>
                        <p>Stok Menipis</p>
                    </div>
                    <div class="overview-stat-card categories">
                        <div class="icon">
                            <i class="fas fa-tools"></i>
                        </div>
                        <h3><?php echo count($completed_maintenance); ?></h3>
                        <p>Maintenance Selesai</p>
                    </div>
                </div>

                <!-- Inventory Section -->
                <div class="section-divider">
                    <h2><i class="fas fa-boxes"></i> Laporan Inventory</h2>
                </div>

                <!-- Low Stock Report -->
                <div class="report-section">
                    <div class="report-section-header">
                        <h3><i class="fas fa-exclamation-triangle"></i> Barang dengan Stok Menipis</h3>
                        <div class="export-buttons no-print">
                            <button class="btn btn-success" onclick="exportInventoryReport('excel')">
                                <i class="fas fa-file-excel"></i> Excel
                            </button>
                            <button class="btn btn-info" onclick="printInventoryReport()">
                                <i class="fas fa-print"></i> Print
                            </button>
                        </div>
                    </div>
                    <div style="overflow-x: auto;">
                        <table class="table" id="inventoryTable">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Kode Barang</th>
                                    <th>Nama Barang</th>
                                    <th>Kategori</th>
                                    <th>Stok Saat Ini</th>
                                    <th>Min. Stok</th>
                                    <th>Status</th>
                                    <th>Lokasi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1; foreach($low_stock_items as $item): ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><?php echo htmlspecialchars($item['item_code']); ?></td>
                                    <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                    <td><?php echo htmlspecialchars($item['category_name'] ?: 'N/A'); ?></td>
                                    <td><?php echo $item['quantity'] . ' ' . $item['unit']; ?></td>
                                    <td><?php echo $item['min_stock'] . ' ' . $item['unit']; ?></td>
                                    <td>
                                        <?php if($item['quantity'] == 0): ?>
                                        <span class="badge badge-danger">HABIS</span>
                                        <?php else: ?>
                                        <span class="badge badge-warning">MENIPIS</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($item['location'] ?: 'N/A'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Maintenance Section -->
                <div class="section-divider">
                    <h2><i class="fas fa-tools"></i> Laporan Maintenance Barang Elektronik</h2>
                </div>

                <!-- Enhanced Maintenance Statistics -->
                <div class="maintenance-stats-grid">
                    <div class="maintenance-stat-card tools">
                        <div class="icon">
                            <i class="fas fa-list"></i>
                        </div>
                        <h3><?php echo $maintenance_exists ? array_sum(array_column($maintenance_by_status, 'count')) : 0; ?></h3>
                        <p>Total Maintenance</p>
                    </div>
                    <div class="maintenance-stat-card cost">
                        <div class="icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h3><?php 
                        $completed_count = 0;
                        foreach($maintenance_by_status as $status) {
                            if($status['status'] == 'COMPLETED') {
                                $completed_count = $status['count'];
                                break;
                            }
                        }
                        echo $completed_count;
                        ?></h3>
                        <p>Maintenance Selesai (Total)</p>
                    </div>
                    <div class="maintenance-stat-card items" style="background: linear-gradient(135deg, #ff6b6b, #ee5a24);">
                        <div class="icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <h3><?php echo count($overdue_maintenance); ?></h3>
                        <p>Maintenance Terlambat</p>
                    </div>
                    <div class="maintenance-stat-card cost">
                        <div class="icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <h3><?php echo count($upcoming_maintenance); ?></h3>
                        <p>Maintenance Mendatang</p>
                    </div>
                </div>

                <!-- Maintenance Charts -->
                <?php if($maintenance_exists && !empty($maintenance_by_type)): ?>
                <div class="chart-container">
                    <h3><i class="fas fa-chart-bar"></i> Statistik Jenis Maintenance</h3>
                    <div style="display: flex; justify-content: center;">
                        <div class="chart-wrapper" style="height: 300px; width: 60%; max-width: 500px;">
                            <canvas id="maintenanceTypeChart"></canvas>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if(!empty($monthly_maintenance)): ?>
                <div class="chart-container">
                    <h3><i class="fas fa-chart-line"></i> Trend Maintenance Bulanan (Periode Filter)</h3>
                    <div class="chart-wrapper" style="height: 280px;">
                        <canvas id="monthlyMaintenanceChart"></canvas>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Quick Actions for Maintenance -->
                <?php if($auth->hasRole(['Administrator', 'Technician']) && $maintenance_exists): ?>
                <div class="report-section">
                    <div class="report-section-header">
                        <h3><i class="fas fa-bolt"></i> Aksi Cepat Maintenance</h3>
                        <div class="export-buttons no-print">
                            <a href="maintenance.php" class="btn btn-primary">
                                <i class="fas fa-tools"></i> Kelola Maintenance
                            </a>
                        </div>
                    </div>
                    <div style="padding: 20px;">
                        <?php if(count($overdue_maintenance) > 0 || count($upcoming_maintenance) > 0): ?>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                            <?php if(count($overdue_maintenance) > 0): ?>
                            <div class="alert-card danger">
                                <div class="alert-icon">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                                <div class="alert-content">
                                    <h4>Maintenance Terlambat</h4>
                                    <p><?php echo count($overdue_maintenance); ?> maintenance melewati jadwal</p>
                                    <a href="maintenance.php" class="btn btn-sm btn-danger">Lihat Detail</a>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if(count($upcoming_maintenance) > 0): ?>
                            <div class="alert-card warning">
                                <div class="alert-icon">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                                <div class="alert-content">
                                    <h4>Maintenance Mendatang</h4>
                                    <p><?php echo count($upcoming_maintenance); ?> maintenance dalam 30 hari</p>
                                    <a href="maintenance.php" class="btn btn-sm btn-warning">Lihat Jadwal</a>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php else: ?>
                        <div style="text-align: center; padding: 20px; color: #666;">
                            <i class="fas fa-check-circle" style="font-size: 48px; margin-bottom: 15px; color: #28a745;"></i>
                            <p>Tidak ada maintenance yang terlambat atau mendatang</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- All Maintenance Report -->
                <?php if($maintenance_exists): ?>
                <div class="report-section">
                    <div class="report-section-header">
                        <h3><i class="fas fa-list"></i> Daftar Semua Maintenance (Periode: <?php echo date('d/m/Y', strtotime($start_date)) . ' - ' . date('d/m/Y', strtotime($end_date)); ?>)</h3>
                        <div class="export-buttons no-print">
                            <button class="btn btn-success" onclick="exportAllMaintenanceReport('excel')">
                                <i class="fas fa-file-excel"></i> Excel
                            </button>
                            <button class="btn btn-info" onclick="printAllMaintenanceReport()">
                                <i class="fas fa-print"></i> Print
                            </button>
                        </div>
                    </div>
                    <div style="overflow-x: auto;">
                        <?php if(!empty($all_maintenance)): ?>
                        <table class="table" id="allMaintenanceTable">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Kode</th>
                                    <th>Barang</th>
                                    <th>Kategori</th>
                                    <th>Jenis</th>
                                    <th>Status</th>
                                    <th>Teknisi</th>
                                    <th>Tgl Jadwal</th>
                                    <th>Tgl Selesai</th>
                                    <th>Biaya</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1; foreach($all_maintenance as $maintenance): ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><?php echo htmlspecialchars($maintenance['maintenance_code']); ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($maintenance['item_name']); ?></strong><br>
                                        <small><?php echo htmlspecialchars($maintenance['item_code']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($maintenance['category_name'] ?: 'N/A'); ?></td>
                                    <td>
                                        <span class="badge badge-info">
                                            <?php echo $maintenance['maintenance_type']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $status_class = '';
                                        switch($maintenance['status']) {
                                            case 'SCHEDULED': $status_class = 'badge-warning'; break;
                                            case 'IN_PROGRESS': $status_class = 'badge-info'; break;
                                            case 'COMPLETED': $status_class = 'badge-success'; break;
                                            case 'CANCELLED': $status_class = 'badge-danger'; break;
                                        }
                                        ?>
                                        <span class="badge <?php echo $status_class; ?>">
                                            <?php echo str_replace('_', ' ', $maintenance['status']); ?>
                                        </span>
                                        <?php if($maintenance['status'] == 'COMPLETED'): ?>
                                        <br><small style="color: green; border-radius:10px;">
                                            <i class="fas fa-check-circle"></i> 
                                            Selesai: <?php echo date('d/m/Y H:i', strtotime($maintenance['completed_date'])); ?>
                                        </small>
                                        <?php elseif($maintenance['status'] == 'SCHEDULED' && $maintenance['scheduled_date'] < date('Y-m-d')): ?>
                                        <br><small style="color: red;"><i class="fas fa-exclamation-triangle"></i> Terlambat</small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($maintenance['technician_name']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($maintenance['scheduled_date'])); ?></td>
                                    <td>
                                        <?php if($maintenance['completed_date']): ?>
                                        <?php echo date('d/m/Y H:i', strtotime($maintenance['completed_date'])); ?>
                                        <?php else: ?>
                                        <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>Rp <?php echo number_format($maintenance['cost'] ?: 0, 0, ',', '.'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                        <div style="text-align: center; padding: 40px; color: #666;">
                            <i class="fas fa-calendar-times" style="font-size: 48px; margin-bottom: 15px;"></i>
                            <p>Tidak ada data maintenance pada periode yang dipilih</p>
                            <small>Coba ubah rentang tanggal filter</small>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Completed Maintenance Report -->
                <div class="report-section">
                    <div class="report-section-header">
                        <h3><i class="fas fa-check-circle"></i> Detail Maintenance yang Telah Diselesaikan (Periode: <?php echo date('d/m/Y', strtotime($start_date)) . ' - ' . date('d/m/Y', strtotime($end_date)); ?>)</h3>
                        <div class="export-buttons no-print">
                            <button class="btn btn-success" onclick="exportMaintenanceReport('excel')">
                                <i class="fas fa-file-excel"></i> Excel
                            </button>
                            <button class="btn btn-info" onclick="printMaintenanceReport()">
                                <i class="fas fa-print"></i> Print
                            </button>
                        </div>
                    </div>
                    <div style="overflow-x: auto;">
                        <?php if(!empty($completed_maintenance)): ?>
                        <table class="table" id="maintenanceTable">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Kode</th>
                                    <th>Barang</th>
                                    <th>Kategori</th>
                                    <th>Jenis</th>
                                    <th>Teknisi</th>
                                    <th>Tgl Selesai</th>
                                    <th>Biaya</th>
                                    <th>Hasil/Findings</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1; foreach($completed_maintenance as $maintenance): ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><?php echo htmlspecialchars($maintenance['maintenance_code']); ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($maintenance['item_name']); ?></strong><br>
                                        <small><?php echo htmlspecialchars($maintenance['item_code']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($maintenance['category_name'] ?: 'N/A'); ?></td>
                                    <td>
                                        <span class="badge badge-info">
                                            <?php echo $maintenance['maintenance_type']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($maintenance['technician_name']); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($maintenance['completed_date'])); ?></td>
                                    <td>Rp <?php echo number_format($maintenance['cost'] ?: 0, 0, ',', '.'); ?></td>
                                    <td><?php echo htmlspecialchars(substr($maintenance['findings'] ?: '', 0, 50) . (strlen($maintenance['findings'] ?: '') > 50 ? '...' : '')); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                        <div style="text-align: center; padding: 40px; color: #666;">
                            <i class="fas fa-check-circle" style="font-size: 48px; margin-bottom: 15px; color: #28a745;"></i>
                            <p>Tidak ada maintenance yang diselesaikan pada periode yang dipilih</p>
                            <small>Coba ubah rentang tanggal filter</small>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php else: ?>
                <!-- No Maintenance Table -->
                <div class="report-section">
                    <div class="report-section-header">
                        <h3><i class="fas fa-info-circle"></i> Informasi Maintenance</h3>
                    </div>
                    <div style="text-align: center; padding: 40px; color: #666;">
                        <i class="fas fa-database" style="font-size: 48px; margin-bottom: 15px;"></i>
                        <p>Tabel maintenance belum tersedia di sistem</p>
                        <small>Hubungi administrator untuk mengaktifkan fitur maintenance</small>
                    </div>
                </div>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/sidebar.js"></script>
    <script>
        // Chart.js configurations
        Chart.defaults.font.family = 'Inter, system-ui, sans-serif';
        Chart.defaults.color = '#666';

        // Maintenance Type Chart
        const typeData = <?php echo json_encode($maintenance_by_type); ?>;
        if(typeData && typeData.length > 0) {
            const typeLabels = typeData.map(item => item.maintenance_type);
            const typeCounts = typeData.map(item => parseInt(item.count));

            new Chart(document.getElementById('maintenanceTypeChart'), {
                type: 'bar',
                data: {
                    labels: typeLabels,
                    datasets: [{
                        label: 'Jumlah',
                        data: typeCounts,
                        backgroundColor: '#667eea',
                        borderColor: '#667eea',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Jenis Maintenance'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }

        // Monthly Maintenance Chart
        const monthlyData = <?php echo json_encode($monthly_maintenance); ?>;
        if(monthlyData && monthlyData.length > 0) {
            const monthlyLabels = monthlyData.map(item => item.month);
            const monthlyCounts = monthlyData.map(item => parseInt(item.count));

            new Chart(document.getElementById('monthlyMaintenanceChart'), {
                type: 'line',
                data: {
                    labels: monthlyLabels,
                    datasets: [{
                        label: 'Maintenance Selesai',
                        data: monthlyCounts,
                        borderColor: '#28a745',
                        backgroundColor: 'rgba(40, 167, 69, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Trend Maintenance Bulanan'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }

        // Export functions
        function exportMaintenanceReport(format) {
            const table = document.getElementById('maintenanceTable');
            if (format === 'excel') {
                exportToExcel(table, 'Laporan_Maintenance_Selesai');
            }
        }

        function exportInventoryReport(format) {
            const table = document.getElementById('inventoryTable');
            if (format === 'excel') {
                exportToExcel(table, 'Laporan_Stok_Menipis');
            }
        }

        function printMaintenanceReport() {
            const originalContent = document.body.innerHTML;
            const table = document.getElementById('maintenanceTable').outerHTML;
            const printContent = `
                <html>
                <head>
                    <title>Laporan Maintenance Selesai</title>
                    <style>
                        table { width: 100%; border-collapse: collapse; }
                        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                        th { background-color: #f2f2f2; }
                        .badge { padding: 2px 6px; border-radius: 3px; }
                        .badge-info { background: #d1ecf1; color: #0c5460; }
                    </style>
                </head>
                <body>
                    <h2>Laporan Maintenance yang Telah Diselesaikan</h2>
                    <p>Periode: ${document.querySelector('input[name="start_date"]').value} - ${document.querySelector('input[name="end_date"]').value}</p>
                    ${table}
                </body>
                </html>
            `;
            
            const printWindow = window.open('', '_blank');
            printWindow.document.write(printContent);
            printWindow.document.close();
            printWindow.print();
        }

        function printInventoryReport() {
            const table = document.getElementById('inventoryTable').outerHTML;
            const printContent = `
                <html>
                <head>
                    <title>Laporan Stok Menipis</title>
                    <style>
                        table { width: 100%; border-collapse: collapse; }
                        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                        th { background-color: #f2f2f2; }
                        .badge { padding: 2px 6px; border-radius: 3px; }
                        .badge-danger { background: #f8d7da; color: #721c24; }
                        .badge-warning { background: #fff3cd; color: #856404; }
                    </style>
                </head>
                <body>
                    <h2>Laporan Barang dengan Stok Menipis</h2>
                    <p>Tanggal: ${new Date().toLocaleDateString('id-ID')}</p>
                    ${table}
                </body>
                </html>
            `;
            
            const printWindow = window.open('', '_blank');
            printWindow.document.write(printContent);
            printWindow.document.close();
            printWindow.print();
        }

        function exportToExcel(table, filename) {
            const wb = XLSX.utils.table_to_book(table);
            XLSX.writeFile(wb, filename + '.xlsx');
        }

        // Export functions for all maintenance
        function exportAllMaintenanceReport(format) {
            const table = document.getElementById('allMaintenanceTable');
            if (format === 'excel') {
                exportToExcel(table, 'Laporan_Semua_Maintenance');
            }
        }

        function printAllMaintenanceReport() {
            const table = document.getElementById('allMaintenanceTable').outerHTML;
            const printContent = `
                <html>
                <head>
                    <title>Laporan Semua Maintenance</title>
                    <style>
                        table { width: 100%; border-collapse: collapse; font-size: 12px; }
                        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
                        th { background-color: #f2f2f2; }
                        .badge { padding: 2px 6px; border-radius: 10px; font-size: 10px; }
                        .badge-info { background: #d1ecf1; color: #0c5460; }
                        .badge-warning { background: #fff3cd; color: #856404; }
                        .badge-success { background: #d4edda; color: #155724; }
                        .badge-danger { background: #f8d7da; color: #721c24; }
                    </style>
                </head>
                <body>
                    <h2>Laporan Semua Maintenance</h2>
                    <p>Periode: ${document.querySelector('input[name="start_date"]').value} - ${document.querySelector('input[name="end_date"]').value}</p>
                    ${table}
                </body>
                </html>
            `;
            
            const printWindow = window.open('', '_blank');
            printWindow.document.write(printContent);
            printWindow.document.close();
            printWindow.print();
        }
    </script>

    <!-- Include XLSX library for Excel export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>
</body>
</html>

