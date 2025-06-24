<?php
require_once '../config/auth.php';
require_once '../config/database.php';

$auth = new Auth();
$auth->requireLogin();

if(!$auth->hasRole(['Administrator', 'Technician'])) {
    header("Location: dashboard.php");
    exit();
}

$database = new Database();
$db = $database->connect();

// Handle maintenance operations
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $maintenance_code = 'MNT' . date('Ymd') . sprintf('%04d', rand(1, 9999));
                // Allow administrator to assign maintenance to other technicians
                $technician_id = ($_SESSION['role_name'] == 'Administrator' && !empty($_POST['technician_id'])) 
                    ? $_POST['technician_id'] 
                    : $_SESSION['user_id'];
                $stmt = $db->prepare("INSERT INTO maintenance (maintenance_code, item_id, technician_id, maintenance_type, scheduled_date, description) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$maintenance_code, $_POST['item_id'], $technician_id, $_POST['maintenance_type'], $_POST['scheduled_date'], $_POST['description']]);
                break;
            case 'update_status':
                $stmt = $db->prepare("UPDATE maintenance SET status=?, findings=?, parts_used=?, cost=?, started_date=?, completed_date=? WHERE id=?");
                $started_date = $_POST['status'] == 'IN_PROGRESS' && empty($_POST['started_date']) ? date('Y-m-d H:i:s') : $_POST['started_date'];
                $completed_date = $_POST['status'] == 'COMPLETED' && empty($_POST['completed_date']) ? date('Y-m-d H:i:s') : $_POST['completed_date'];
                $stmt->execute([$_POST['status'], $_POST['findings'], $_POST['parts_used'], $_POST['cost'], $started_date, $completed_date, $_POST['id']]);
                
                // Update next maintenance date if completed
                if($_POST['status'] == 'COMPLETED' && !empty($_POST['next_maintenance_date'])) {
                    $stmt = $db->prepare("UPDATE maintenance SET next_maintenance_date=? WHERE id=?");
                    $stmt->execute([$_POST['next_maintenance_date'], $_POST['id']]);
                }
                break;
            case 'complete_maintenance':
                // Mark maintenance as completed
                $stmt = $db->prepare("UPDATE maintenance SET status='COMPLETED', completed_date=NOW(), findings=?, parts_used=?, cost=?, next_maintenance_date=? WHERE id=?");
                $stmt->execute([$_POST['findings'], $_POST['parts_used'], $_POST['cost'], $_POST['next_maintenance_date'], $_POST['id']]);
                break;
            case 'delete':
                $stmt = $db->prepare("DELETE FROM maintenance WHERE id=?");
                $stmt->execute([$_POST['id']]);
                break;
        }
        header("Location: maintenance.php");
        exit();
    }
}

// Get maintenance records
$where_clause = "";
$params = [];

// Filter by technician for non-admin users
if($_SESSION['role_name'] == 'Technician') {
    $where_clause = "WHERE m.technician_id = ?";
    $params[] = $_SESSION['user_id'];
}

$stmt = $db->prepare("SELECT m.*, i.item_name, i.item_code, u.full_name as technician_name, c.category_name
                     FROM maintenance m 
                     JOIN items i ON m.item_id = i.id 
                     JOIN users u ON m.technician_id = u.id 
                     JOIN categories c ON i.category_id = c.id 
                     $where_clause
                     ORDER BY m.scheduled_date DESC");
$stmt->execute($params);
$maintenances = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get electronic items only for maintenance
$stmt = $db->query("SELECT i.*, c.category_name 
                   FROM items i 
                   JOIN categories c ON i.category_id = c.id 
                   WHERE c.category_name LIKE '%elektronik%' OR c.category_name LIKE '%komputer%' 
                   ORDER BY i.item_name");
$electronic_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get technicians for admin
$technicians = [];
if($_SESSION['role_name'] == 'Administrator') {
    $stmt = $db->query("SELECT u.* FROM users u JOIN roles r ON u.role_id = r.id WHERE r.role_name = 'Technician' ORDER BY u.full_name");
    $technicians = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance - Sistem Inventory Sekolah</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="../assets/css/additional.css" rel="stylesheet">
    <style>
        .maintenance-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .status-scheduled { background: #fff3cd; color: #856404; }
        .status-in-progress { background: #cce5ff; color: #004085; }
        .status-completed { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        
        .maintenance-type {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .type-preventive { background: #d1ecf1; color: #0c5460; }
        .type-corrective { background: #fff3cd; color: #856404; }
        .type-inspection { background: #e2e3e5; color: #383d41; }
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
                <li><a href="my-requests.php"><i class="fas fa-paper-plane"></i> Permintaan Saya</a></li>
                <?php endif; ?>
                
                <?php if($auth->hasRole(['Administrator', 'Technician'])): ?>
                <li><a href="maintenance.php" class="active"><i class="fas fa-tools"></i> Maintenance</a></li>
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
                    <h1><i class="fas fa-tools"></i> Maintenance Barang Elektronik</h1>
                    <p>Kelola jadwal dan riwayat perawatan peralatan elektronik</p>
                </div>

                <!-- Maintenance Statistics -->
                <div class="maintenance-stats">
                    <?php
                    $stats = [
                        'scheduled' => 0,
                        'in_progress' => 0,
                        'completed' => 0,
                        'overdue' => 0
                    ];
                    
                    foreach($maintenances as $maintenance) {
                        $stats[strtolower(str_replace('-', '_', $maintenance['status']))]++;
                        if($maintenance['status'] == 'SCHEDULED' && $maintenance['scheduled_date'] < date('Y-m-d')) {
                            $stats['overdue']++;
                        }
                    }
                    ?>
                    <div class="stat-card primary">
                        <div class="icon"><i class="fas fa-calendar"></i></div>
                        <h3><?php echo $stats['scheduled']; ?></h3>
                        <p>Terjadwal</p>
                    </div>
                    <div class="stat-card warning">
                        <div class="icon"><i class="fas fa-wrench"></i></div>
                        <h3><?php echo $stats['in_progress']; ?></h3>
                        <p>Sedang Dikerjakan</p>
                    </div>
                    <div class="stat-card success">
                        <div class="icon"><i class="fas fa-check"></i></div>
                        <h3><?php echo $stats['completed']; ?></h3>
                        <p>Selesai</p>
                    </div>
                    <div class="stat-card danger">
                        <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
                        <h3><?php echo $stats['overdue']; ?></h3>
                        <p>Terlambat</p>
                    </div>
                </div>

                <!-- Maintenance Table -->
                <div class="table-container">
                    <div class="table-header">
                        <h3>Daftar Maintenance</h3>
                        <button class="btn btn-primary" onclick="openModal('addModal')">
                            <i class="fas fa-plus"></i> Jadwalkan Maintenance
                        </button>
                    </div>
                    
                    <table>
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Barang</th>
                                <th>Jenis</th>
                                <th>Status</th>
                                <th>Tanggal</th>
                                <th>Teknisi</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($maintenances as $maintenance): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($maintenance['maintenance_code']); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($maintenance['item_name']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($maintenance['item_code']); ?></small>
                                </td>
                                <td>
                                    <span class="maintenance-type type-<?php echo strtolower($maintenance['maintenance_type']); ?>">
                                        <?php echo $maintenance['maintenance_type']; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge status-<?php echo strtolower(str_replace('_', '-', $maintenance['status'])); ?>">
                                        <?php echo str_replace('_', ' ', $maintenance['status']); ?>
                                    </span>
                                    <?php if($maintenance['status'] == 'COMPLETED'): ?>
                                    <br><small style="color: green;">
                                        <i class="fas fa-check-circle"></i> 
                                        Selesai: <?php echo date('d/m/Y H:i', strtotime($maintenance['completed_date'])); ?>
                                    </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo date('d/m/Y', strtotime($maintenance['scheduled_date'])); ?>
                                    <?php if($maintenance['status'] == 'SCHEDULED' && $maintenance['scheduled_date'] < date('Y-m-d')): ?>
                                    <br><small style="color: red;"><i class="fas fa-exclamation-triangle"></i> Terlambat</small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($maintenance['technician_name']); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-info" onclick="viewMaintenance(<?php echo htmlspecialchars(json_encode($maintenance)); ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    
                                    <?php if($maintenance['status'] != 'COMPLETED'): ?>
                                    <button class="btn btn-sm btn-warning" onclick="updateMaintenance(<?php echo htmlspecialchars(json_encode($maintenance)); ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    
                                    <?php if($maintenance['status'] == 'IN_PROGRESS' || $maintenance['status'] == 'SCHEDULED'): ?>
                                    <button class="btn btn-sm btn-success" onclick="completeMaintenance(<?php echo htmlspecialchars(json_encode($maintenance)); ?>)">
                                        <i class="fas fa-check"></i> Selesai
                                    </button>
                                    <?php endif; ?>
                                    
                                    <button class="btn btn-sm btn-danger" onclick="deleteMaintenance(<?php echo $maintenance['id']; ?>, '<?php echo htmlspecialchars($maintenance['maintenance_code']); ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php else: ?>
                                    <span class="badge badge-success">
                                        <i class="fas fa-check-circle"></i> Completed
                                    </span>
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

    <!-- Add Maintenance Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Jadwalkan Maintenance Baru</h3>
                <span class="close" onclick="closeModal('addModal')">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                
                <?php if($_SESSION['role_name'] == 'Administrator' && !empty($technicians)): ?>
                <div class="form-group">
                    <label>Teknisi</label>
                    <select name="technician_id">
                        <option value="">Assign ke saya</option>
                        <?php foreach($technicians as $technician): ?>
                        <option value="<?php echo $technician['id']; ?>">
                            <?php echo htmlspecialchars($technician['full_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label>Barang Elektronik</label>
                    <select name="item_id" required>
                        <option value="">Pilih Barang</option>
                        <?php foreach($electronic_items as $item): ?>
                        <option value="<?php echo $item['id']; ?>">
                            <?php echo htmlspecialchars($item['item_name']); ?> - <?php echo htmlspecialchars($item['item_code']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Jenis Maintenance</label>
                        <select name="maintenance_type" required>
                            <option value="PREVENTIVE">Preventive</option>
                            <option value="CORRECTIVE">Corrective</option>
                            <option value="INSPECTION">Inspection</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Tanggal Jadwal</label>
                        <input type="date" name="scheduled_date" required min="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label>Deskripsi Pekerjaan</label>
                    <textarea name="description" rows="3" placeholder="Jelaskan pekerjaan maintenance yang akan dilakukan..." required></textarea>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addModal')">Batal</button>
                    <button type="submit" class="btn btn-primary">Jadwalkan</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script>
        function viewMaintenance(maintenance) {
            const statusText = {
                'SCHEDULED': 'Terjadwal',
                'IN_PROGRESS': 'Sedang Dikerjakan', 
                'COMPLETED': 'Selesai',
                'CANCELLED': 'Dibatalkan'
            };
            
            let details = `Detail Maintenance:\n\n`;
            details += `Kode: ${maintenance.maintenance_code}\n`;
            details += `Barang: ${maintenance.item_name} (${maintenance.item_code})\n`;
            details += `Jenis: ${maintenance.maintenance_type}\n`;
            details += `Status: ${statusText[maintenance.status] || maintenance.status}\n`;
            details += `Tanggal Jadwal: ${new Date(maintenance.scheduled_date).toLocaleDateString('id-ID')}\n`;
            details += `Teknisi: ${maintenance.technician_name}\n`;
            details += `Deskripsi: ${maintenance.description}\n`;
            
            if (maintenance.findings) {
                details += `Temuan: ${maintenance.findings}\n`;
            }
            if (maintenance.parts_used) {
                details += `Spare Part: ${maintenance.parts_used}\n`;
            }
            if (maintenance.cost > 0) {
                details += `Biaya: Rp ${parseFloat(maintenance.cost).toLocaleString('id-ID')}\n`;
            }
            if (maintenance.completed_date) {
                details += `Tanggal Selesai: ${new Date(maintenance.completed_date).toLocaleDateString('id-ID')} ${new Date(maintenance.completed_date).toLocaleTimeString('id-ID')}\n`;
            }
            if (maintenance.next_maintenance_date) {
                details += `Maintenance Berikutnya: ${new Date(maintenance.next_maintenance_date).toLocaleDateString('id-ID')}\n`;
            }
            
            alert(details);
        }

        function completeMaintenance(maintenance) {
            const modal = `
                <div id="completeModal" class="modal" style="display: block;">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3><i class="fas fa-check-circle"></i> Selesaikan Maintenance</h3>
                            <span class="close" onclick="closeModal('completeModal')">&times;</span>
                        </div>
                        <form method="POST">
                            <input type="hidden" name="action" value="complete_maintenance">
                            <input type="hidden" name="id" value="${maintenance.id}">
                            
                            <div class="modal-body">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i>
                                    Maintenance akan ditandai sebagai selesai. Pastikan semua pekerjaan sudah completed.
                                </div>
                                
                                <div class="maintenance-info">
                                    <p><strong>Kode:</strong> ${maintenance.maintenance_code}</p>
                                    <p><strong>Barang:</strong> ${maintenance.item_name}</p>
                                    <p><strong>Jenis:</strong> ${maintenance.maintenance_type}</p>
                                </div>
                                
                                <div class="form-group">
                                    <label>Hasil/Temuan Maintenance <span style="color: red;">*</span></label>
                                    <textarea name="findings" rows="3" placeholder="Jelaskan hasil maintenance dan kondisi barang setelah maintenance..." required>${maintenance.findings || ''}</textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label>Spare Part yang Digunakan</label>
                                    <textarea name="parts_used" rows="2" placeholder="Sebutkan spare part yang digunakan (jika ada)...">${maintenance.parts_used || ''}</textarea>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Total Biaya (Rp)</label>
                                        <input type="number" name="cost" step="0.01" min="0" value="${maintenance.cost || 0}" placeholder="0">
                                    </div>
                                    <div class="form-group">
                                        <label>Maintenance Berikutnya</label>
                                        <input type="date" name="next_maintenance_date" value="${maintenance.next_maintenance_date || ''}" min="${new Date().toISOString().split('T')[0]}">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="button" class="btn btn-secondary" onclick="closeModal('completeModal')">Batal</button>
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-check"></i> Selesaikan Maintenance
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', modal);
            document.body.style.overflow = 'hidden';
        }

        function updateMaintenance(maintenance) {
            const modal = `
                <div id="updateModal" class="modal" style="display: block;">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3>Update Status Maintenance</h3>
                            <span class="close" onclick="closeModal('updateModal')">&times;</span>
                        </div>
                        <form method="POST">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="id" value="${maintenance.id}">
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status" required onchange="toggleFields(this.value)">
                                    <option value="SCHEDULED" ${maintenance.status == 'SCHEDULED' ? 'selected' : ''}>Scheduled</option>
                                    <option value="IN_PROGRESS" ${maintenance.status == 'IN_PROGRESS' ? 'selected' : ''}>In Progress</option>
                                    <option value="COMPLETED" ${maintenance.status == 'COMPLETED' ? 'selected' : ''}>Completed</option>
                                    <option value="CANCELLED" ${maintenance.status == 'CANCELLED' ? 'selected' : ''}>Cancelled</option>
                                </select>
                            </div>
                            <div class="form-group" id="findingsGroup">
                                <label>Temuan/Hasil</label>
                                <textarea name="findings" rows="3">${maintenance.findings || ''}</textarea>
                            </div>
                            <div class="form-group" id="partsGroup">
                                <label>Spare Part yang Digunakan</label>
                                <textarea name="parts_used" rows="2">${maintenance.parts_used || ''}</textarea>
                            </div>
                            <div class="form-row">
                                <div class="form-group" id="costGroup">
                                    <label>Biaya (Rp)</label>
                                    <input type="number" name="cost" step="0.01" value="${maintenance.cost || 0}">
                                </div>
                                <div class="form-group" id="nextMaintenanceGroup">
                                    <label>Maintenance Berikutnya</label>
                                    <input type="date" name="next_maintenance_date" value="${maintenance.next_maintenance_date || ''}">
                                </div>
                            </div>
                            <input type="hidden" name="started_date" value="${maintenance.started_date || ''}">
                            <input type="hidden" name="completed_date" value="${maintenance.completed_date || ''}">
                            <div class="form-actions">
                                <button type="button" class="btn btn-secondary" onclick="closeModal('updateModal')">Batal</button>
                                <button type="submit" class="btn btn-primary">Update</button>
                            </div>
                        </form>
                    </div>
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', modal);
            document.body.style.overflow = 'hidden';
        }

        function deleteMaintenance(id, code) {
            if(confirm(`Apakah Anda yakin ingin menghapus maintenance "${code}"?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function toggleFields(status) {
            const fields = ['findingsGroup', 'partsGroup', 'costGroup', 'nextMaintenanceGroup'];
            fields.forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if(field) {
                    field.style.display = (status === 'IN_PROGRESS' || status === 'COMPLETED') ? 'block' : 'none';
                }
            });
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const completeModal = document.getElementById('completeModal');
            const updateModal = document.getElementById('updateModal');
            
            if (completeModal && event.target == completeModal) {
                closeModal('completeModal');
            }
            if (updateModal && event.target == updateModal) {
                closeModal('updateModal');
            }
        }
    </script>

    <style>
        .maintenance-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #007bff;
        }
        
        .maintenance-info p {
            margin: 5px 0;
            font-size: 14px;
        }
        
        .alert {
            padding: 12px 15px;
            border-radius: 5px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert.alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border-left: 4px solid #17a2b8;
        }
        
        .badge.badge-success {
            background: #28a745;
            color: white;
            padding: 5px 10px;
            border-radius: 12px;
            font-size: 12px;
        }
        
        .btn.btn-success {
            background: #28a745;
            border-color: #28a745;
        }
        
        .btn.btn-success:hover {
            background: #218838;
            border-color: #1e7e34;
        }
    </style>
</body>
</html>
