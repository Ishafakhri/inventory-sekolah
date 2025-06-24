<?php
require_once 'config/auth.php';
require_once 'config/database.php';

// Redirect jika sudah login
if (isset($_SESSION['user_id'])) {
    header("Location: pages/dashboard.php");
    exit();
}

$error = '';
$success = '';

// Data akun demo
$demo_accounts = [
    [
        'username' => 'admin',
        'password' => 'admin123',
        'role' => 'Administrator',
        'description' => 'Akses penuh sistem',
        'icon' => 'fas fa-user-shield'
    ],
    [
        'username' => 'manager',
        'password' => 'manager123',
        'role' => 'Manager',
        'description' => 'Kelola dan laporan',
        'icon' => 'fas fa-user-tie'
    ],
    [
        'username' => 'warehouse',
        'password' => 'warehouse123',
        'role' => 'Warehouse',
        'description' => 'Kelola barang',
        'icon' => 'fas fa-boxes'
    ],
    [
        'username' => 'technician',
        'password' => 'tech123',
        'role' => 'Technician',
        'description' => 'Maintenance barang',
        'icon' => 'fas fa-tools'
    ],
    [
        'username' => 'user',
        'password' => 'user123',
        'role' => 'User',
        'description' => 'Akses dasar',
        'icon' => 'fas fa-user'
    ]
];

if ($_POST) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    if (empty($username) || empty($password)) {
        $error = "Username dan password harus diisi";
    } else {
        try {
            $database = new Database();
            $db = $database->connect();
            
            // Query sederhana tanpa cek status
            $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                // Get role name terpisah
                $roleStmt = $db->prepare("SELECT role_name FROM roles WHERE id = ?");
                $roleStmt->execute([$user['role_id']]);
                $role = $roleStmt->fetch(PDO::FETCH_ASSOC);
                
                // Set session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'] ?? $user['username'];
                $_SESSION['role_id'] = $user['role_id'];
                $_SESSION['role_name'] = $role['role_name'] ?? 'User';
                
                // Redirect
                header("Location: pages/dashboard.php");
                exit();
            } else {
                $error = "Username atau password salah";
            }
        } catch (Exception $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Inventory Sekolah</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 900px;
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header i {
            font-size: 48px;
            color: #667eea;
            margin-bottom: 15px;
        }

        .login-header h2 {
            color: #333;
            font-size: 24px;
            margin-bottom: 8px;
        }

        .login-header p {
            color: #666;
            font-size: 14px;
        }

        .alert {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        .input-group {
            position: relative;
        }

        .input-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
        }

        .input-group input {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }

        .input-group input:focus {
            outline: none;
            border-color: #667eea;
        }

        .btn {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-outline {
            background: transparent;
            color: #667eea;
            border: 2px solid #667eea;
        }

        .btn-outline:hover {
            background: #667eea;
            color: white;
        }

        .login-tips {
            background: #e3f2fd;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 3px solid #2196f3;
            font-size: 12px;
        }

        .login-tips i {
            color: #2196f3;
        }

        .guest-access {
            margin: 20px 0;
        }

        .divider {
            text-align: center;
            margin: 20px 0;
            position: relative;
        }

        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #e1e5e9;
        }

        .divider span {
            background: white;
            padding: 0 15px;
            color: #666;
            position: relative;
        }

        .guest-info {
            text-align: center;
            font-size: 12px;
            color: #666;
            margin-top: 10px;
        }

        .demo-accounts {
            margin-top: 30px;
        }

        .demo-accounts h4 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .demo-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
        }

        .demo-item {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 12px;
            text-align: left;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .demo-item:hover {
            border-color: #667eea;
            background: #f0f4ff;
            transform: translateX(5px);
        }

        .demo-item i {
            font-size: 20px;
            color: #667eea;
            width: 24px;
            text-align: center;
        }

        .demo-info {
            flex: 1;
        }

        .demo-item .role {
            font-weight: 600;
            color: #333;
            margin-bottom: 2px;
        }

        .demo-item .description {
            font-size: 11px;
            color: #666;
            margin-bottom: 4px;
        }

        .demo-item .credentials {
            font-size: 10px;
            color: #667eea;
            background: white;
            padding: 2px 6px;
            border-radius: 4px;
            border: 1px solid #e1e5e9;
            display: inline-block;
        }

        .login-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            align-items: start;
        }

        .login-form-section {
            padding-right: 20px;
        }

        .demo-section {
            padding-left: 20px;
            border-left: 1px solid #e1e5e9;
        }

        @media (max-width: 768px) {
            .login-content {
                grid-template-columns: 1fr;
                gap: 30px;
            }

            .login-form-section,
            .demo-section {
                padding: 0;
            }

            .demo-section {
                border-left: none;
                border-top: 1px solid #e1e5e9;
                padding-top: 30px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <i class="fas fa-school"></i>
            <h2>Sistem Inventory Sekolah</h2>
            <p>Silakan login untuk melanjutkan</p>
        </div>

        <div class="login-content">
            <div class="login-form-section">
                <?php if($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <?php if($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="login-form" id="loginForm">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <div class="input-group">
                            <i class="fas fa-user"></i>
                            <input type="text" id="username" name="username" required autocomplete="username">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-group">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="password" name="password" required autocomplete="current-password">
                        </div>
                    </div>

                    <button type="submit" name="login" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt"></i>
                        Login
                    </button>
                </form>

                <div class="divider">
                    <span>atau</span>
                </div>

                <div style="text-align: center;">
                    <a href="index.php" class="btn btn-outline">
                        <i class="fas fa-home"></i>
                        Kembali ke Halaman Utama
                    </a>
                </div>
            </div>

            <div class="demo-section">
                <div class="login-tips">
                    <i class="fas fa-info-circle"></i>
                    <strong>Tips:</strong> Klik salah satu akun demo untuk mengisi form otomatis.
                </div>

                <div class="demo-accounts">
                    <h4><i class="fas fa-users"></i> Akun Demo</h4>
                    <div class="demo-grid">
                        <?php foreach($demo_accounts as $account): ?>
                        <div class="demo-item" onclick="fillDemo('<?php echo $account['username']; ?>', '<?php echo $account['password']; ?>')">
                            <i class="<?php echo $account['icon']; ?>"></i>
                            <div class="demo-info">
                                <div class="role"><?php echo htmlspecialchars($account['role']); ?></div>
                                <div class="description"><?php echo htmlspecialchars($account['description']); ?></div>
                                <div class="credentials"><?php echo $account['username']; ?> / <?php echo $account['password']; ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function fillDemo(username, password) {
            document.getElementById('username').value = username;
            document.getElementById('password').value = password;

            // Add visual feedback
            const usernameField = document.getElementById('username');
            const passwordField = document.getElementById('password');

            usernameField.style.backgroundColor = '#e8f5e8';
            passwordField.style.backgroundColor = '#e8f5e8';

            setTimeout(() => {
                usernameField.style.backgroundColor = '';
                passwordField.style.backgroundColor = '';
            }, 1000);
        }

        // Focus on username field when page loads
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('username').focus();
        });

        // Form validation
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();

            if (!username || !password) {
                e.preventDefault();
                alert('Username dan password harus diisi!');
                return false;
            }
        });
    </script>
</body>
</html>