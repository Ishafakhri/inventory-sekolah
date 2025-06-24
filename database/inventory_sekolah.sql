CREATE DATABASE inventory_sekolah;
USE inventory_sekolah;

-- Tabel roles
CREATE TABLE roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    role_name VARCHAR(50) NOT NULL,
    description TEXT
);

-- Tabel users
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    role_id INT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id)
);

-- Tabel categories
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel items
CREATE TABLE items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    item_code VARCHAR(50) UNIQUE NOT NULL,
    item_name VARCHAR(100) NOT NULL,
    description TEXT,
    category_id INT,
    quantity INT DEFAULT 0,
    min_stock INT DEFAULT 0,
    unit VARCHAR(20),
    price DECIMAL(10,2),
    location VARCHAR(100),
    condition_status ENUM('Baik', 'Rusak Ringan', 'Rusak Berat') DEFAULT 'Baik',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

-- Tabel transactions
CREATE TABLE transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    transaction_code VARCHAR(50) UNIQUE NOT NULL,
    item_id INT,
    user_id INT,
    transaction_type ENUM('IN', 'OUT', 'ADJUSTMENT') NOT NULL,
    quantity INT NOT NULL,
    description TEXT,
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES items(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Tabel requests
CREATE TABLE requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    request_code VARCHAR(50) UNIQUE NOT NULL,
    requester_id INT,
    item_id INT,
    quantity_requested INT,
    status ENUM('PENDING', 'APPROVED', 'REJECTED', 'COMPLETED') DEFAULT 'PENDING',
    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    approved_by INT NULL,
    approved_date TIMESTAMP NULL,
    notes TEXT,
    FOREIGN KEY (requester_id) REFERENCES users(id),
    FOREIGN KEY (item_id) REFERENCES items(id),
    FOREIGN KEY (approved_by) REFERENCES users(id)
);

-- Tabel maintenance untuk tracking perawatan barang
CREATE TABLE maintenance (
    id INT PRIMARY KEY AUTO_INCREMENT,
    maintenance_code VARCHAR(50) UNIQUE NOT NULL,
    item_id INT,
    technician_id INT,
    maintenance_type ENUM('PREVENTIVE', 'CORRECTIVE', 'INSPECTION') DEFAULT 'PREVENTIVE',
    status ENUM('SCHEDULED', 'IN_PROGRESS', 'COMPLETED', 'CANCELLED') DEFAULT 'SCHEDULED',
    scheduled_date DATE,
    started_date DATETIME NULL,
    completed_date DATETIME NULL,
    description TEXT,
    findings TEXT,
    parts_used TEXT,
    cost DECIMAL(10,2) DEFAULT 0,
    next_maintenance_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES items(id),
    FOREIGN KEY (technician_id) REFERENCES users(id)
);

-- Insert roles
INSERT INTO roles (role_name, description) VALUES
('Administrator', 'Administrator Sistem - Kontrol penuh atas sistem'),
('Manager', 'Manajer Inventori/Kepala Bagian Sarana & Prasarana'),
('Procurement', 'Petugas Pengadaan - Fokus pada proses pembelian'),
('Warehouse', 'Petugas Gudang/Penyimpan Barang'),
('User', 'Pengguna/Pemohon Barang'),
('Technician', 'Teknisi/Staf Pemeliharaan'),
('Auditor', 'Auditor/Verifikator');

-- Insert demo users
INSERT INTO users (username, password, full_name, email, role_id) VALUES
('admin', 'admin123', 'Administrator Sistem', 'admin@sekolah.com', 1),
('manager', 'manager123', 'Kepala Sarana Prasarana', 'manager@sekolah.com', 2),
('procurement', 'procurement123', 'Petugas Pengadaan', 'procurement@sekolah.com', 3),
('warehouse', 'warehouse123', 'Petugas Gudang', 'warehouse@sekolah.com', 4),
('user', 'user123', 'Guru/Staff', 'user@sekolah.com', 5),
('technician', 'technician123', 'Teknisi Sekolah', 'technician@sekolah.com', 6),
('auditor', 'auditor123', 'Auditor Internal', 'auditor@sekolah.com', 7);

-- Insert sample categories
INSERT INTO categories (category_name, description) VALUES
('Elektronik', 'Peralatan elektronik dan komputer'),
('Furniture', 'Meja, kursi, lemari dan furniture lainnya'),
('Alat Tulis', 'Perlengkapan alat tulis kantor'),
('Laboratorium', 'Peralatan untuk laboratorium'),
('Olahraga', 'Peralatan olahraga dan kebugaran');

-- Insert sample items
INSERT INTO items (item_code, item_name, description, category_id, quantity, min_stock, unit, price, location) VALUES
('ELK001', 'Laptop ASUS', 'Laptop untuk administrasi', 1, 10, 3, 'Unit', 5000000, 'Ruang TU'),
('FUR001', 'Meja Guru', 'Meja kayu untuk guru', 2, 25, 5, 'Unit', 800000, 'Gudang Utama'),
('ATK001', 'Pulpen Pilot', 'Pulpen warna biru', 3, 100, 20, 'Pcs', 3000, 'Gudang ATK'),
('LAB001', 'Mikroskop', 'Mikroskop untuk lab biologi', 4, 5, 2, 'Unit', 2500000, 'Lab Biologi'),
('OLR001', 'Bola Voli', 'Bola voli standar', 5, 15, 3, 'Unit', 150000, 'Gudang Olahraga');

-- Insert sample maintenance records
INSERT INTO maintenance (maintenance_code, item_id, technician_id, maintenance_type, scheduled_date, description) VALUES
('MNT001', 1, 6, 'PREVENTIVE', '2024-01-15', 'Pembersihan dan pemeriksaan rutin laptop'),
('MNT002', 1, 6, 'INSPECTION', '2024-02-01', 'Pemeriksaan kondisi hardware dan software');
