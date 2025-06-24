<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Inventory Sekolah - Kelola Aset Sekolah dengan Mudah</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .landing-page {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .navbar {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 15px 0;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            transition: all 0.3s ease;
        }
        
        .navbar.scrolled {
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
        }
        
        .navbar .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            color: white;
            font-size: 24px;
            font-weight: bold;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .navbar.scrolled .logo {
            color: #333;
        }
        
        .nav-links {
            display: flex;
            gap: 30px;
            align-items: center;
        }
        
        .nav-links a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .navbar.scrolled .nav-links a {
            color: #333;
        }
        
        .nav-links a:hover {
            color: #ffd700;
        }
        
        .hero {
            padding: 120px 20px 80px;
            text-align: center;
            color: white;
        }
        
        .hero .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .hero h1 {
            font-size: 3.5em;
            margin-bottom: 20px;
            animation: fadeInUp 0.8s ease;
        }
        
        .hero p {
            font-size: 1.2em;
            margin-bottom: 40px;
            opacity: 0.9;
            animation: fadeInUp 0.8s ease 0.2s both;
        }
        
        .hero-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
            animation: fadeInUp 0.8s ease 0.4s both;
        }
        
        .hero-buttons .btn {
            padding: 15px 30px;
            font-size: 16px;
            border-radius: 50px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: white;
            color: #667eea;
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .btn-outline {
            background: transparent;
            color: white;
            border: 2px solid white;
        }
        
        .btn-outline:hover {
            background: white;
            color: #667eea;
        }
        
        .features {
            padding: 80px 20px;
            background: white;
        }
        
        .features .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .section-title {
            text-align: center;
            margin-bottom: 60px;
        }
        
        .section-title h2 {
            font-size: 2.5em;
            color: #333;
            margin-bottom: 15px;
        }
        
        .section-title p {
            font-size: 1.1em;
            color: #666;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 40px;
        }
        
        .feature-card {
            text-align: center;
            padding: 40px 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 50px rgba(0,0,0,0.15);
        }
        
        .feature-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 30px;
        }
        
        .feature-card h3 {
            font-size: 1.5em;
            color: #333;
            margin-bottom: 15px;
        }
        
        .feature-card p {
            color: #666;
            line-height: 1.6;
        }
        
        /* Enhanced Notification Feature with better animation */
        .feature-card.notification-feature {
            position: relative;
            overflow: visible;
            background: linear-gradient(145deg, #fff 0%, #f8f9ff 100%);
            border: 2px solid transparent;
            background-clip: padding-box;
        }
        
        .feature-card.notification-feature::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(45deg, #ff6b35, #f7931e, #667eea, #764ba2);
            border-radius: 17px;
            z-index: -1;
            animation: borderGlow 3s linear infinite;
        }
        
        @keyframes borderGlow {
            0%, 100% { 
                background: linear-gradient(45deg, #ff6b35, #f7931e, #667eea, #764ba2);
            }
            25% { 
                background: linear-gradient(45deg, #f7931e, #667eea, #764ba2, #ff6b35);
            }
            50% { 
                background: linear-gradient(45deg, #667eea, #764ba2, #ff6b35, #f7931e);
            }
            75% { 
                background: linear-gradient(45deg, #764ba2, #ff6b35, #f7931e, #667eea);
            }
        }
        
        .notification-icon {
            position: relative;
            display: inline-block;
        }
        
        .notification-icon::after {
            content: '';
            position: absolute;
            top: 5px;
            right: 5px;
            width: 16px;
            height: 16px;
            background: linear-gradient(45deg, #ff4757, #ff3742);
            border-radius: 50%;
            border: 3px solid white;
            animation: pulseNotification 2s infinite;
            box-shadow: 0 0 10px rgba(255, 71, 87, 0.5);
        }
        
        @keyframes pulseNotification {
            0% { 
                transform: scale(1);
                opacity: 1;
            }
            50% { 
                transform: scale(1.3);
                opacity: 0.7;
            }
            100% { 
                transform: scale(1);
                opacity: 1;
            }
        }
        
        .feature-card.notification-feature:hover {
            transform: translateY(-15px) scale(1.02);
            box-shadow: 0 25px 60px rgba(102, 126, 234, 0.2);
        }
        
        .feature-card.notification-feature .feature-icon {
            background: linear-gradient(135deg, #ff6b35, #f7931e);
            position: relative;
            overflow: visible;
        }
        
        .roles {
            padding: 80px 20px;
            background: #f8f9fa;
        }
        
        .roles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
        }
        
        .role-card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
        }
        
        .role-card:hover {
            transform: translateY(-5px);
        }
        
        .role-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            color: white;
            font-size: 24px;
        }
        
        .role-card h4 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .role-card p {
            color: #666;
            font-size: 14px;
            line-height: 1.5;
        }
        
        .cta {
            padding: 80px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-align: center;
        }
        
        .cta h2 {
            font-size: 2.5em;
            margin-bottom: 20px;
        }
        
        .cta p {
            font-size: 1.1em;
            margin-bottom: 40px;
            opacity: 0.9;
        }
        
        .footer {
            background: #2c3e50;
            color: white;
            padding: 40px 20px;
            text-align: center;
        }
        
        .footer .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
            margin-bottom: 30px;
        }
        
        .footer-section h4 {
            margin-bottom: 15px;
            color: #ecf0f1;
        }
        
        .footer-section p,
        .footer-section a {
            color: #bdc3c7;
            text-decoration: none;
            line-height: 1.6;
        }
        
        .footer-section a:hover {
            color: white;
        }
        
        .footer-bottom {
            border-top: 1px solid #34495e;
            padding-top: 20px;
            color: #bdc3c7;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Mobile Menu Styles */
        .mobile-menu-toggle {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
        }
        
        .navbar.scrolled .mobile-menu-toggle {
            color: #333;
        }
        
        .mobile-menu {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            padding: 20px;
        }
        
        .mobile-menu.active {
            display: block;
            animation: slideDown 0.3s ease;
        }
        
        .mobile-menu a {
            display: block;
            padding: 15px 0;
            color: #333;
            text-decoration: none;
            font-weight: 500;
            border-bottom: 1px solid rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        .mobile-menu a:hover {
            color: #667eea;
            padding-left: 10px;
        }
        
        .mobile-menu a:last-child {
            border-bottom: none;
        }
        
        .mobile-menu .btn {
            display: inline-block;
            margin-top: 10px;
            padding: 12px 24px;
            background: #667eea;
            color: white;
            border-radius: 25px;
            text-align: center;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2.5em;
            }
            
            .hero-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .nav-links {
                display: none;
            }
            
            .mobile-menu-toggle {
                display: block;
            }
            
            .feature-card:hover {
                transform: translateY(-5px);
            }
            
            .navbar .container {
                position: relative;
            }
        }
    </style>
</head>
<body class="landing-page">
    <!-- Navigation -->
    <nav class="navbar" id="navbar">
        <div class="container">
            <a href="#" class="logo">
                <i class="fas fa-school"></i>
                Inventory Sekolah
            </a>
            <div class="nav-links">
                <a href="#features">Fitur</a>
                <a href="#roles">Peran User</a>
                <a href="pages/catalog.php">Katalog</a>
                <a href="login.php" class="btn btn-primary" style="padding: 10px 20px; border-radius: 25px;">
                    <i class="fas fa-sign-in-alt"></i> Login
                </a>
            </div>
            
            <!-- Mobile Menu Toggle -->
            <button class="mobile-menu-toggle" id="mobileMenuToggle">
                <i class="fas fa-bars"></i>
            </button>
            
            <!-- Mobile Menu -->
            <div class="mobile-menu" id="mobileMenu">
                <a href="#features">Fitur</a>
                <a href="#roles">Peran User</a>
                <a href="pages/catalog.php">Katalog</a>
                <a href="login.php" class="btn">
                    <i class="fas fa-sign-in-alt"></i> Login
                </a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <h1><i class="fas fa-boxes"></i> Sistem Inventory Sekolah</h1>
            <p>Solusi modern untuk mengelola aset dan inventori sekolah dengan efisien, transparan, dan terintegrasi</p>
            <div class="hero-buttons">
                <a href="login.php" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt"></i> Masuk Sistem
                </a>
                <a href="pages/catalog.php" class="btn btn-outline">
                    <i class="fas fa-eye"></i> Lihat Katalog
                </a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="features">
        <div class="container">
            <div class="section-title">
                <h2>Fitur Unggulan</h2>
                <p>Sistem yang dirancang khusus untuk memenuhi kebutuhan manajemen inventori sekolah</p>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-boxes"></i>
                    </div>
                    <h3>Manajemen Barang</h3>
                    <p>Kelola data barang dengan mudah, termasuk kategori, stok, lokasi, dan kondisi barang secara real-time</p>
                </div>
                
                <!-- Enhanced Notification Feature -->
                <div class="feature-card notification-feature">
                    <div class="feature-icon">
                        <div class="notification-icon">
                            <i class="fas fa-bell"></i>
                        </div>
                    </div>
                    <h3>Sistem Notifikasi Cerdas</h3>
                    <p>Notifikasi real-time untuk stok menipis, permintaan pending, maintenance terjadwal, dan peringatan penting lainnya</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3>Multi-User System</h3>
                    <p>Sistem dengan 7 peran user berbeda, dari Administrator hingga Auditor dengan hak akses yang sesuai</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <h3>Sistem Permintaan</h3>
                    <p>Workflow permintaan barang yang terstruktur dengan approval system dan tracking status</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <h3>Laporan & Analitik</h3>
                    <p>Dashboard analitik dengan grafik interaktif dan laporan komprehensif untuk pengambilan keputusan</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-exchange-alt"></i>
                    </div>
                    <h3>Tracking Transaksi</h3>
                    <p>Mencatat semua transaksi barang masuk dan keluar dengan history lengkap dan audit trail</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h3>Responsive Design</h3>
                    <p>Interface yang dapat diakses dari desktop, tablet, maupun smartphone dengan performa optimal</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Roles Section -->
    <section class="roles" id="roles">
        <div class="container">
            <div class="section-title">
                <h2>Peran Pengguna Sistem</h2>
                <p>Setiap peran memiliki akses dan tanggung jawab yang berbeda sesuai dengan kebutuhan organisasi</p>
            </div>
            <div class="roles-grid">
                <div class="role-card">
                    <div class="role-icon">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <h4>Administrator Sistem</h4>
                    <p>Kontrol penuh atas sistem, manajemen user, konfigurasi sistem, dan pemecahan masalah teknis</p>
                </div>
                <div class="role-card">
                    <div class="role-icon">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <h4>Manajer Inventori</h4>
                    <p>Bertanggung jawab atas perencanaan, pemantauan stok, dan pengambilan keputusan strategis</p>
                </div>
                <div class="role-card">
                    <div class="role-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <h4>Petugas Pengadaan</h4>
                    <p>Mengelola proses pembelian, mencari pemasok, dan memastikan barang diterima sesuai spesifikasi</p>
                </div>
                <div class="role-card">
                    <div class="role-icon">
                        <i class="fas fa-warehouse"></i>
                    </div>
                    <h4>Petugas Gudang</h4>
                    <p>Mengelola penyimpanan fisik, pencatatan keluar masuk barang, dan inventarisasi</p>
                </div>
                <div class="role-card">
                    <div class="role-icon">
                        <i class="fas fa-user"></i>
                    </div>
                    <h4>Pengguna/Pemohon</h4>
                    <p>Guru dan staf yang mengajukan permintaan barang dan melacak status permintaan</p>
                </div>
                <div class="role-card">
                    <div class="role-icon">
                        <i class="fas fa-tools"></i>
                    </div>
                    <h4>Teknisi Pemeliharaan</h4>
                    <p>Menjadwalkan pemeliharaan aset, mencatat riwayat perbaikan, dan melaporkan kondisi</p>
                </div>
                <div class="role-card">
                    <div class="role-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <h4>Auditor/Verifikator</h4>
                    <p>Memiliki akses read-only untuk audit dan verifikasi data tanpa mengubah informasi</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta">
        <div class="container">
            <h2>Siap Mengoptimalkan Inventori Sekolah Anda?</h2>
            <p>Bergabunglah dengan sistem yang telah dipercaya untuk mengelola aset sekolah secara profesional</p>
            <div class="hero-buttons">
                <a href="login.php" class="btn btn-primary">
                    <i class="fas fa-rocket"></i> Mulai Sekarang
                </a>
                <a href="pages/catalog.php" class="btn btn-outline">
                    <i class="fas fa-eye"></i> Demo Katalog
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h4>Sistem Inventory Sekolah</h4>
                    <p>Solusi terpadu untuk manajemen aset dan inventori sekolah yang modern, efisien, dan user-friendly.</p>
                </div>
                <div class="footer-section">
                    <h4>Fitur Utama</h4>
                    <p><a href="#features">Manajemen Barang</a></p>
                    <p><a href="#features">Multi-User System</a></p>
                    <p><a href="#features">Laporan & Analitik</a></p>
                </div>
                <div class="footer-section">
                    <h4>Akses Cepat</h4>
                    <p><a href="login.php">Login Sistem</a></p>
                    <p><a href="pages/catalog.php">Katalog Barang</a></p>
                    <p><a href="#roles">Peran Pengguna</a></p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 Sistem Inventory Sekolah. Dikembangkan untuk meningkatkan efisiensi pengelolaan aset sekolah.</p>
            </div>
        </div>
    </footer>

    <script>
        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.getElementById('navbar');
            if (window.scrollY > 100) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // Mobile menu toggle
        const mobileMenuToggle = document.getElementById('mobileMenuToggle');
        const mobileMenu = document.getElementById('mobileMenu');
        
        mobileMenuToggle.addEventListener('click', function() {
            mobileMenu.classList.toggle('active');
            
            // Change icon
            const icon = this.querySelector('i');
            if (mobileMenu.classList.contains('active')) {
                icon.className = 'fas fa-times';
            } else {
                icon.className = 'fas fa-bars';
            }
        });
        
        // Close mobile menu when clicking on a link
        mobileMenu.addEventListener('click', function(e) {
            if (e.target.tagName === 'A') {
                mobileMenu.classList.remove('active');
                mobileMenuToggle.querySelector('i').className = 'fas fa-bars';
            }
        });
        
        // Close mobile menu when clicking outside
        document.addEventListener('click', function(e) {
            if (!mobileMenuToggle.contains(e.target) && !mobileMenu.contains(e.target)) {
                mobileMenu.classList.remove('active');
                mobileMenuToggle.querySelector('i').className = 'fas fa-bars';
            }
        });

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Animation on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Observe all feature cards and role cards
        document.querySelectorAll('.feature-card, .role-card').forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(30px)';
            card.style.transition = 'all 0.6s ease';
            observer.observe(card);
        });
    </script>
</body>
</html>
