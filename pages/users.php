<?php
require_once '../config/auth.php';
require_once '../config/database.php';

$auth = new Auth();
$auth->requireLogin();

if(!$auth->hasRole(['Administrator'])) {
    header("Location: dashboard.php");
    exit();
}

$database = new Database();
$db = $database->connect();

// Handle user operations
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $stmt = $db->prepare("INSERT INTO users (username, password, full_name, email, role_id, is_active) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$_POST['username'], $_POST['password'], $_POST['full_name'], $_POST['email'], $_POST['role_id'], $_POST['is_active']]);
                break;
            case 'edit':
                if (!empty($_POST['password'])) {
                    $stmt = $db->prepare("UPDATE users SET username=?, password=?, full_name=?, email=?, role_id=?, is_active=? WHERE id=?");
                    $stmt->execute([$_POST['username'], $_POST['password'], $_POST['full_name'], $_POST['email'], $_POST['role_id'], $_POST['is_active'], $_POST['id']]);
                } else {
                    $stmt = $db->prepare("UPDATE users SET username=?, full_name=?, email=?, role_id=?, is_active=? WHERE id=?");
                    $stmt->execute([$_POST['username'], $_POST['full_name'], $_POST['email'], $_POST['role_id'], $_POST['is_active'], $_POST['id']]);
                }
                break;
            case 'delete':
                $stmt = $db->prepare("DELETE FROM users WHERE id=? AND id != ?");
                $stmt->execute([$_POST['id'], $_SESSION['user_id']]);
                break;
            case 'toggle_status':
                $stmt = $db->prepare("UPDATE users SET is_active = NOT is_active WHERE id=?");
                $stmt->execute([$_POST['id']]);
                break;
        }
        header("Location: users.php");
        exit();
    }
}

// Get users with roles
$stmt = $db->query("SELECT u.*, r.role_name FROM users u JOIN roles r ON u.role_id = r.id ORDER BY u.full_name");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get roles for dropdown
$stmt = $db->query("SELECT * FROM roles ORDER BY role_name");
$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user statistics
$stmt = $db->query("SELECT 
    COUNT(*) as total_users,
    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_users,
    SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive_users
    FROM users");
$user_stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Get users by role
$stmt = $db->query("SELECT r.role_name, COUNT(u.id) as user_count 
                   FROM roles r 
                   LEFT JOIN users u ON r.id = u.role_id 
                   GROUP BY r.id, r.role_name");
$users_by_role = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen User - Sistem Inventory Sekolah</title>
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
                <li><a href="my-requests.php"><i class="fas fa-paper-plane"></i> Permintaan Saya</a></li>
                <?php endif; ?>
                
                <?php if($auth->hasRole(['Administrator', 'Technician'])): ?>
                <li><a href="maintenance.php"><i class="fas fa-tools"></i> Maintenance</a></li>
                <?php endif; ?>
                
                <?php if($auth->hasRole(['Administrator', 'Manager', 'Auditor'])): ?>
                <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Laporan</a></li>
                <?php endif; ?>
                
                <?php if($auth->hasRole(['Administrator'])): ?>
                <li><a href="users.php" class="active"><i class="fas fa-users"></i> Manajemen User</a></li>
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
                    <h1>Manajemen User</h1>
                    <p>Kelola pengguna dan hak akses sistem</p>
                </div>

                <!-- User Statistics -->
                <div class="stats-grid">
                    <div class="stat-card primary">
                        <div class="icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3><?php echo $user_stats['total_users']; ?></h3>
                        <p>Total User</p>
                    </div>
                    
                    <div class="stat-card success">
                        <div class="icon">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <h3><?php echo $user_stats['active_users']; ?></h3>
                        <p>User Aktif</p>
                    </div>
                    
                    <div class="stat-card danger">
                        <div class="icon">
                            <i class="fas fa-user-slash"></i>
                        </div>
                        <h3><?php echo $user_stats['inactive_users']; ?></h3>
                        <p>User Nonaktif</p>
                    </div>
                </div>

                <!-- User Cards Grid -->
                <div class="user-grid">
                    <?php foreach($users as $user): ?>
                    <div class="user-card">
                        <div class="user-card-header">
                            <div class="user-avatar-large">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="user-info-card">
                                <h4><?php echo htmlspecialchars($user['full_name']); ?></h4>
                                <p><i class="fas fa-at"></i> <?php echo htmlspecialchars($user['username']); ?></p>
                                <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user['email']); ?></p>
                                <p><i class="fas fa-user-tag"></i> <?php echo htmlspecialchars($user['role_name']); ?></p>
                                <span class="user-status <?php echo $user['is_active'] ? 'active' : 'inactive'; ?>">
                                    <?php echo $user['is_active'] ? 'Aktif' : 'Nonaktif'; ?>
                                </span>
                            </div>
                        </div>
                        <div class="user-actions">
                            <button class="btn btn-sm btn-warning" onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="btn btn-sm btn-info" onclick="toggleUserStatus(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['full_name']); ?>')">
                                <i class="fas fa-power-off"></i> Toggle
                            </button>
                            <?php if($user['id'] != $_SESSION['user_id']): ?>
                            <button class="btn btn-sm btn-danger" onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['full_name']); ?>')">
                                <i class="fas fa-trash"></i> Hapus
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Add User Button -->
                <div class="text-center">
                    <button class="btn btn-primary btn-lg" onclick="openModal('addModal')">
                        <i class="fas fa-plus"></i> Tambah User Baru
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add User Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Tambah User Baru</h3>
                <span class="close" onclick="closeModal('addModal')">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="form-row">
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" required>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="full_name" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Role</label>
                        <select name="role_id" required>
                            <option value="">Pilih Role</option>
                            <?php foreach($roles as $role): ?>
                            <option value="<?php echo $role['id']; ?>"><?php echo htmlspecialchars($role['role_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="is_active" required>
                            <option value="1">Aktif</option>
                            <option value="0">Nonaktif</option>
                        </select>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addModal')">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script>
        const rolesData = <?php echo json_encode($roles); ?>;
        
        function editUser(user) {
            // Build role options
            let roleOptions = '';
            rolesData.forEach(role => {
                const selected = user.role_id == role.id ? 'selected' : '';
                roleOptions += `<option value="${role.id}" ${selected}>${escapeHtml(role.role_name)}</option>`;
            });
            
            // Create edit modal dynamically
            const modal = `
                <div id="editModal" class="modal" style="display: block;">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3>Edit User</h3>
                            <span class="close" onclick="closeModal('editModal')">&times;</span>
                        </div>
                        <form method="POST">
                            <input type="hidden" name="action" value="edit">
                            <input type="hidden" name="id" value="${user.id}">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Username</label>
                                    <input type="text" name="username" value="${escapeHtml(user.username)}" required>
                                </div>
                                <div class="form-group">
                                    <label>Password (Kosongkan jika tidak diubah)</label>
                                    <input type="password" name="password">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Nama Lengkap</label>
                                <input type="text" name="full_name" value="${escapeHtml(user.full_name)}" required>
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email" value="${escapeHtml(user.email)}" required>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Role</label>
                                    <select name="role_id" required>
                                        ${roleOptions}
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Status</label>
                                    <select name="is_active" required>
                                        <option value="1" ${user.is_active == 1 ? 'selected' : ''}>Aktif</option>
                                        <option value="0" ${user.is_active == 0 ? 'selected' : ''}>Nonaktif</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-actions">
                                <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">Batal</button>
                                <button type="submit" class="btn btn-primary">Update</button>
                            </div>
                        </form>
                    </div>
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', modal);
            document.body.style.overflow = 'hidden';
        }

        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        }

        function toggleUserStatus(id, name) {
            if(confirm(`Apakah Anda yakin ingin mengubah status user "${name}"?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="toggle_status">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function deleteUser(id, name) {
            if(confirm(`Apakah Anda yakin ingin menghapus user "${name}"?`)) {
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

        // Close modal when clicking outside
        window.onclick = function(event) {
            const editModal = document.getElementById('editModal');
            if (editModal && event.target == editModal) {
                closeModal('editModal');
            }
        }
    </script>
</body>
</html>
