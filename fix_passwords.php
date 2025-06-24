<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->connect();

// Cek struktur tabel users terlebih dahulu
echo "<h3>Struktur Tabel Users:</h3>";
$stmt = $db->query("DESCRIBE users");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
$hasStatusColumn = false;

echo "<table border='1'>";
foreach ($columns as $column) {
    echo "<tr><td>" . $column['Field'] . "</td><td>" . $column['Type'] . "</td></tr>";
    if ($column['Field'] === 'status') {
        $hasStatusColumn = true;
    }
}
echo "</table>";

// Demo accounts dengan password yang benar
$demo_accounts = [
    ['username' => 'admin', 'password' => 'admin123', 'role_id' => 1],
    ['username' => 'manager', 'password' => 'manager123', 'role_id' => 2], 
    ['username' => 'warehouse', 'password' => 'warehouse123', 'role_id' => 4],
    ['username' => 'technician', 'password' => 'tech123', 'role_id' => 6],
    ['username' => 'user', 'password' => 'user123', 'role_id' => 5]
];

echo "<h3>Memperbaiki Password Demo Accounts:</h3>";

foreach ($demo_accounts as $account) {
    // Cek apakah user sudah ada
    $stmt = $db->prepare("SELECT id, password FROM users WHERE username = ?");
    $stmt->execute([$account['username']]);
    $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $hashedPassword = password_hash($account['password'], PASSWORD_DEFAULT);
    
    if ($existingUser) {
        // Update password saja (tanpa status jika kolom tidak ada)
        if ($hasStatusColumn) {
            $updateStmt = $db->prepare("UPDATE users SET password = ?, status = 'active' WHERE username = ?");
            $updateStmt->execute([$hashedPassword, $account['username']]);
        } else {
            $updateStmt = $db->prepare("UPDATE users SET password = ? WHERE username = ?");
            $updateStmt->execute([$hashedPassword, $account['username']]);
        }
        echo "<p>✅ Updated password for: " . $account['username'] . "</p>";
    } else {
        // Insert new user (tanpa status jika kolom tidak ada)
        if ($hasStatusColumn) {
            $insertStmt = $db->prepare("INSERT INTO users (username, password, full_name, role_id, status) VALUES (?, ?, ?, ?, 'active')");
            $insertStmt->execute([
                $account['username'], 
                $hashedPassword, 
                ucfirst($account['username']), 
                $account['role_id'],
                'active'
            ]);
        } else {
            $insertStmt = $db->prepare("INSERT INTO users (username, password, full_name, role_id) VALUES (?, ?, ?, ?)");
            $insertStmt->execute([
                $account['username'], 
                $hashedPassword, 
                ucfirst($account['username']), 
                $account['role_id']
            ]);
        }
        echo "<p>✅ Created new user: " . $account['username'] . "</p>";
    }
    
    // Test password
    $testResult = password_verify($account['password'], $hashedPassword);
    echo "<p>🔍 Password test for " . $account['username'] . ": " . ($testResult ? 'PASS' : 'FAIL') . "</p>";
}

echo "<h3>Selesai! Silakan coba login dengan akun demo.</h3>";
echo "<p><a href='login.php'>Kembali ke Login</a></p>";
?>
