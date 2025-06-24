# 📦 Sistem Inventory Sekolah

Sistem manajemen inventori sekolah yang modern dan terintegrasi untuk mengelola aset dan barang sekolah dengan efisien.

## 🚀 Fitur Utama

### 📋 Manajemen Barang
- ✅ CRUD barang dengan kode otomatis berdasarkan kategori
- ✅ Tracking stok real-time dengan notifikasi stok menipis
- ✅ Manajemen kategori dan lokasi barang
- ✅ Upload dan manajemen foto barang

### 👥 Multi-User System (7 Role)
- **Administrator** - Kontrol penuh sistem
- **Manager** - Kelola dan laporan strategis
- **Procurement** - Manajemen pengadaan
- **Warehouse** - Kelola gudang dan stok
- **User** - Ajukan permintaan barang
- **Technician** - Maintenance dan perbaikan
- **Auditor** - Akses read-only untuk audit

### 📊 Laporan & Analytics
- Dashboard interaktif dengan grafik Chart.js
- Laporan stok menipis dan barang habis
- Laporan maintenance barang elektronik
- Export ke Excel dan Print-friendly
- Trend analysis bulanan

### 🔧 Sistem Maintenance
- Jadwal maintenance preventif
- Tracking status: Scheduled → In Progress → Completed
- Riwayat maintenance dengan biaya
- Notifikasi maintenance terlambat

### 📝 Workflow Permintaan
- Sistem approval berlapis
- Tracking status permintaan real-time
- Notifikasi untuk approver
- History lengkap permintaan

## 🛠️ Teknologi Stack

- **Backend**: PHP 8.0+ dengan PDO
- **Database**: MySQL/MariaDB
- **Frontend**: HTML5, CSS3, JavaScript ES6
- **UI Framework**: Custom CSS dengan Font Awesome
- **Charts**: Chart.js untuk visualisasi data
- **Export**: SheetJS untuk export Excel

## 📋 Persyaratan Sistem

- PHP >= 8.0
- MySQL/MariaDB >= 5.7
- Apache/Nginx Web Server
- XAMPP/WAMP (untuk development)

## ⚡ Quick Start

### 1. Clone & Setup
```bash
# Clone ke folder xampp/htdocs
git clone [repository-url] inventory-sekolah
cd inventory-sekolah
```

### 2. Database Setup
```sql
-- Import struktur database
mysql -u root -p < database/inventory_sekolah.sql
```

### 3. Konfigurasi Database
```php
// config/database.php
private $host = "localhost";
private $db_name = "inventory_sekolah";
private $username = "root";
private $password = "";
```

### 4. Perbaiki Password Demo
```bash
# Akses melalui browser
http://localhost/inventory-sekolah/fix_passwords.php
```

### 5. Login
```bash
# Akses sistem
http://localhost/inventory-sekolah/login.php
```

## 👤 Akun Demo

| Role | Username | Password | Akses |
|------|----------|----------|-------|
| Administrator | `admin` | `admin123` | Semua fitur |
| Manager | `manager` | `manager123` | Laporan & analitik |
| Warehouse | `warehouse` | `warehouse123` | Kelola barang |
| Technician | `technician` | `tech123` | Maintenance |
| User | `user` | `user123` | Permintaan barang |

## 📁 Struktur Project

```
inventory-sekolah/
├── 📁 assets/
│   ├── 📁 css/
│   │   ├── style.css          # Main stylesheet
│   │   ├── sidebar.css        # Sidebar components
│   │   └── additional.css     # Additional styles
│   ├── 📁 js/
│   │   ├── main.js           # Core JavaScript
│   │   ├── sidebar.js        # Sidebar functionality
│   │   └── items.js          # Items management
│   └── 📁 uploads/           # Upload directory
├── 📁 config/
│   ├── auth.php              # Authentication class
│   ├── database.php          # Database connection
│   └── item_code_generator.php # Auto item code
├── 📁 pages/
│   ├── dashboard.php         # Main dashboard
│   ├── items.php            # Items management
│   ├── categories.php       # Categories
│   ├── transactions.php     # Transactions
│   ├── requests.php         # Request management
│   ├── maintenance.php      # Maintenance system
│   ├── reports.php          # Reports & analytics
│   └── users.php           # User management
├── 📁 database/
│   └── inventory_sekolah.sql # Database structure
├── login.php               # Login page
├── index.php              # Landing page
└── README.md              # Documentation
```

## 🎯 Fitur Unggulan

### 🏷️ Auto Item Code Generation
Sistem otomatis generate kode barang berdasarkan kategori:
- **OLR**#### - Olahraga
- **ELK**#### - Elektronik  
- **FUR**#### - Furnitur
- **LAB**#### - Laboratorium
- **ATK**#### - Alat Tulis
- **KBR**#### - Kebersihan

### 🔔 Smart Notifications
- Stok menipis/habis
- Maintenance terlambat
- Permintaan pending
- Approval notifications

### 📱 Responsive Design
- Mobile-first approach
- Sidebar minimizable
- Touch-friendly interface
- Cross-browser compatibility

### 🔐 Security Features
- Password hashing dengan PHP
- Role-based access control
- SQL injection protection
- XSS prevention
- CSRF protection

## 📊 Database Schema

### Tabel Utama
- `users` - Data pengguna dan role
- `roles` - Definisi role/peran
- `categories` - Kategori barang
- `items` - Master data barang
- `transactions` - Transaksi masuk/keluar
- `requests` - Permintaan barang
- `maintenance` - Data maintenance

### Relationship
```
users ←→ roles (many-to-one)
items ←→ categories (many-to-one)
transactions ←→ items (many-to-one)
requests ←→ users (many-to-one)
maintenance ←→ items (many-to-one)
```

## 🎨 UI/UX Features

- **Modern Design** - Clean dan professional
- **Dark/Light Theme** - Support tema gelap
- **Interactive Charts** - Visualisasi data menarik
- **Smooth Animation** - Transisi yang halus
- **Loading States** - Feedback visual yang baik

## 🛠️ Development Setup

### Local Development
```bash
# Start XAMPP
# Import database
# Configure database connection
# Run fix_passwords.php
# Access http://localhost/inventory-sekolah
```

### Debugging
```bash
# Enable debug mode
http://localhost/inventory-sekolah/debug_login.php

# Check database structure
http://localhost/inventory-sekolah/fix_passwords.php
```

## 📝 API Endpoints

### Authentication
- `POST /login.php` - User login
- `GET /config/logout.php` - User logout

### Items Management
- `GET /pages/items.php` - List items
- `POST /pages/items.php` - Create/Update/Delete items

### Reports
- `GET /pages/reports.php` - Dashboard reports
- Export functions available

## 🔧 Customization

### Menambah Role Baru
1. Insert ke tabel `roles`
2. Update `config/auth.php`
3. Tambahkan permission di setiap page

### Menambah Kategori Baru
1. Tambah kategori di admin panel
2. Update `item_code_generator.php` untuk kode otomatis

### Custom Theme
1. Modify `assets/css/style.css`
2. Update CSS variables untuk color scheme

## 🐛 Troubleshooting

### Login Issues
```bash
# Cek struktur database
php debug_login.php

# Reset password demo
php fix_passwords.php

# Cek error log
tail -f /xampp/apache/logs/error.log
```

### Database Connection
```php
// Test koneksi di config/database.php
try {
    $db = new Database();
    $connection = $db->connect();
    echo "Koneksi berhasil!";
} catch(Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

## 📈 Performance Tips

- Enable MySQL query cache
- Optimize images untuk upload
- Use CDN untuk assets statis
- Enable gzip compression
- Regular database maintenance

## 🤝 Contributing

1. Fork the project
2. Create feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'Add AmazingFeature'`)
4. Push to branch (`git push origin feature/AmazingFeature`)
5. Open Pull Request

## 📄 License

Distributed under the MIT License. See `LICENSE` for more information.

## 📞 Support

- 📧 Email: support@inventory-sekolah.com
- 📱 WhatsApp: +62-xxx-xxxx-xxxx
- 🌐 Website: https://inventory-sekolah.com
- 📚 Documentation: https://docs.inventory-sekolah.com

## 🎯 Roadmap

### Version 2.0
- [ ] API REST untuk mobile app
- [ ] Barcode/QR Code scanning
- [ ] Advanced reporting dengan AI
- [ ] Integration dengan sistem ERP
- [ ] Multi-branch support

### Version 1.5
- [ ] Email notifications
- [ ] File attachments
- [ ] Advanced search & filters
- [ ] Bulk operations
- [ ] Data import/export

---

**Made with ❤️ for Indonesian Schools**

*Sistem Inventory Sekolah - Modernizing School Asset Management*
