<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'classes/Report.php';
require_once 'classes/User.php';




if (isset($_SESSION['register_success'])) {
    $register_success = $_SESSION['register_success'];
    unset($_SESSION['register_success']);
}


if (isLoggedIn()) {
    if (isAdmin()) {
        redirect('admin/dashboard.php');
    } else {
        redirect('user/dashboard.php');
    }
    exit;
}

$login_error = '';
$register_error = '';
$register_success = '';


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    $user = new User($db);


    if (isset($_POST['action']) && $_POST['action'] === 'login') {
        $username = trim($_POST['username']);
        $password = $_POST['password'];

        if (!empty($username) && !empty($password)) {
            if ($user->login($username, $password)) {
                $_SESSION['user_id'] = $user->id;
                $_SESSION['username'] = $user->username;
                $_SESSION['nama'] = $user->nama;
                $_SESSION['role'] = $user->role;
                $_SESSION['last_login'] = date('Y-m-d H:i:s');

                if ($user->role === 'admin') {
                    redirect('admin/dashboard.php');
                } else {
                    redirect('user/dashboard.php');
                }
            } else {
                $login_error = 'Username atau password salah!';
            }
        } else {
            $login_error = 'Semua field harus diisi!';
        }
    }


    elseif (isset($_POST['action']) && $_POST['action'] === 'register') {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $nama = trim($_POST['nama']);
        $nomor_hp = trim($_POST['nomor_hp']);
        $alamat = trim($_POST['alamat']);
        $latitude = $_POST['latitude'];
        $longitude = $_POST['longitude'];

        if (!empty($username) && !empty($password) && !empty($nama) && !empty($nomor_hp) && !empty($alamat)) {
            if ($password === $confirm_password) {
                if (!$user->usernameExists($username)) {
                    $user->username = $username;
                    $user->password = $password;
                    $user->nama = $nama;
                    $user->nomor_hp = $nomor_hp;
                    $user->alamat = $alamat;
                    $user->latitude = $latitude;
                    $user->longitude = $longitude;
                    $user->role = 'user';

                    if ($user->register()) {
                        $register_success = 'Registrasi berhasil! Silakan login.';
                    } else {
                        $register_error = 'Terjadi kesalahan saat registrasi.';
                    }
                } else {
                    $register_error = 'Username sudah digunakan!';
                }
            } else {
                $register_error = 'Password dan konfirmasi password tidak cocok!';
            }
        } else {
            $register_error = 'Semua field harus diisi!';
        }
    }


    elseif (isset($_POST['action']) && $_POST['action'] === 'guest_report') {
        $guest_name = trim($_POST['guest_name']);
        $guest_contact = trim($_POST['guest_contact']);
        $kategori = $_POST['kategori'];
        $deskripsi = trim($_POST['deskripsi']);
        $latitude = $_POST['latitude'];
        $longitude = $_POST['longitude'];
        $alamat = trim($_POST['alamat']);
        

        $gambar = '';
        if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'uploads/';
            if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
            
            $fileExtension = strtolower(pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION));
            $newFileName = uniqid('guest_') . '.' . $fileExtension;
            $uploadFile = $uploadDir . $newFileName;
            
            $allowedTypes = ['jpg', 'jpeg', 'png', 'webp'];
            if (in_array($fileExtension, $allowedTypes)) {
                if (move_uploaded_file($_FILES['gambar']['tmp_name'], $uploadFile)) {
                    $gambar = $newFileName;
                } else {
                    $register_error = 'Gagal mengupload gambar.';
                }
            } else {
                $register_error = 'Format gambar tidak didukung (hanya JPG, JPEG, PNG, WEBP).';
            }
        } else {
            $register_error = 'Gambar wajib diupload!';
        }

        if (empty($register_error)) {
            if (!empty($guest_name) && !empty($guest_contact) && !empty($gambar)) {
                $report = new Report($db);
                $report->user_id = null; // Guest user
                $report->guest_name = $guest_name;
                $report->whatsapp_number = $guest_contact;
                $report->kategori = $kategori;
                $report->jenis_sampah = (!empty($_POST['jenis_sampah']) && trim($_POST['jenis_sampah']) !== '') ? $_POST['jenis_sampah'] : ucfirst($kategori);
                $report->gambar = $gambar;
                $report->deskripsi = $deskripsi;
                $report->lokasi_latitude = $latitude;
                $report->lokasi_longitude = $longitude;
                $report->alamat_lokasi = $alamat;
                $report->status = 'pending';

                $report->confidence = isset($_POST['confidence']) ? floatval($_POST['confidence']) : 0;
                $report->engine_prediction = isset($_POST['kategori']) ? $_POST['kategori'] : '';
                
                if ($report->create()) {

                    if (isset($_POST['ajax']) && $_POST['ajax'] === '1') {

                        ini_set('display_errors', 0);

                        if (ob_get_length()) ob_clean();
                        
                        header('Content-Type: application/json');
                        echo json_encode([
                            'status' => 'success',
                            'message' => 'Laporan Anda berhasil dikirim! Kami akan menghubungi Anda via WhatsApp.'
                        ]);
                        exit();
                    }


                    $_SESSION['register_success'] = 'Laporan Anda berhasil dikirim! Kami akan menghubungi Anda via WhatsApp.';
                    header("Location: " . $_SERVER['PHP_SELF']);
                    exit();
                } else {
                    if (isset($_POST['ajax']) && $_POST['ajax'] === '1') {
                        ini_set('display_errors', 0);
                        if (ob_get_length()) ob_clean();
                        
                        header('Content-Type: application/json');
                        echo json_encode([
                            'status' => 'error',
                            'message' => 'Terjadi kesalahan saat menyimpan laporan.'
                        ]);
                        exit();
                    }
                    $register_error = 'Terjadi kesalahan saat menyimpan laporan.';
                }
            } else {
                $register_error = 'Nama, Kontak, dan Gambar wajib diisi!';
            }
        }
    }
}


$database = new Database();
$db = $database->getConnection();
$report = new Report($db);
$stats = $report->getStatistics();


$recentQuery = "SELECT kategori, jenis_sampah, created_at, alamat_lokasi, lokasi_latitude, lokasi_longitude 
                FROM reports 
                WHERE status != 'ditolak'
                ORDER BY created_at DESC 
                LIMIT 50";
$stmtRecent = $db->prepare($recentQuery);
$stmtRecent->execute();
$recentReports = $stmtRecent->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes, viewport-fit=cover">
    <meta name="theme-color" content="#0d9488">
    <meta name="description" content="Sistem Pelaporan Sampah Desa - Solusi cerdas untuk lingkungan bersih dan sehat">
    
    <!-- Performance Optimization -->
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    
    <title>Lapor Sampah - Solusi Lingkungan Cerdas</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="logo.jpg">
    <link rel="alternate icon" href="favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="favicon.svg">
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    
    <link rel="stylesheet" href="assets/css/landing-premium.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="landing-navbar navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="favicon.svg" alt="Logo" height="30" class="d-inline-block align-text-top me-2">
                <span>Lapor<span class="text-success">Sampah</span></span>
            </a>
            <button class="navbar-toggler border-0 shadow-none p-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="fas fa-bars fs-3 text-dark"></span>
            </button>
            <div class="collapse navbar-collapse mt-3 mt-lg-0" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center gap-2">
                    <li class="nav-item">
                        <a class="nav-link" href="#features">
                            <i class="fas fa-star me-2 d-lg-none"></i>Fitur
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#stats">
                            <i class="fas fa-chart-bar me-2 d-lg-none"></i>Data Laporan
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#map">
                            <i class="fas fa-map-marked-alt me-2 d-lg-none"></i>Peta
                        </a>
                    </li>
                    <li class="nav-item ms-lg-2">
                        <a class="btn-nav-login text-decoration-none" href="#" data-bs-toggle="modal" data-bs-target="#loginModal">
                            <i class="fas fa-sign-in-alt me-2 d-lg-none"></i>Masuk
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="btn-nav-register text-decoration-none" href="#" data-bs-toggle="modal" data-bs-target="#registerModal">Daftar Sekarang</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-blob hero-blob-1"></div>
        <div class="hero-blob hero-blob-2"></div>
        
        <div class="container position-relative">
            <div class="row align-items-center">
                <div class="col-lg-6 hero-content">
                    <div class="hero-badge fade-in-up">
                        <i class="fas fa-sparkles"></i>
                        <span>Teknologi KLASIFIKASI PINTAR</span>
                    </div>
                    <h1 class="hero-title">
                        Jaga Lingkungan <br>
                        <span>Lebih Cerdas & Mudah</span>
                    </h1>
                    <p class="hero-subtitle">
                        Platform pelaporan sampah berbasis sistem cerdas pertama yang menghubungkan masyarakat dengan solusi pengelolaan sampah yang efektif. Deteksi otomatis, lokasi presisi, penanganan cepat.
                    </p>
                    <div class="hero-buttons">

                        <a href="#" class="btn-hero btn-hero-cta text-decoration-none" data-bs-toggle="modal" data-bs-target="#quickReportModal">
                            <i class="fas fa-bolt me-2"></i> Lapor Langsung
                        </a>
                    </div>
                </div>
                <div class="col-lg-6 mt-5 mt-lg-0 text-center">
                    <div class="hero-image-wrapper">
                        <!-- Simulated 3D Elements -->


                        <!-- Main Illustration -->
                        <img src="assets/img/hero-illustration.svg" alt="" class="img-fluid position-relative" style="z-index: 1; max-height: 400px; filter: drop-shadow(0 20px 40px rgba(13,148,136,0.15));" onerror="this.src=''">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section (New Requirement) -->
    <div class="container" id="stats">
        <div class="stats-container">
            <div class="row">
                <div class="col-md-3 col-6">
                    <div class="stat-item">
                        <div class="stat-icon total">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <div class="stat-number"><?php echo number_format($stats['total']); ?></div>
                        <div class="stat-label">Total Laporan Masuk</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-item">
                        <div class="stat-icon organik">
                            <i class="fas fa-leaf"></i>
                        </div>
                        <div class="stat-number"><?php echo number_format($stats['organik']); ?></div>
                        <div class="stat-label">Sampah Organik</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-item">
                        <div class="stat-icon anorganik">
                            <i class="fas fa-recycle"></i>
                        </div>
                        <div class="stat-number"><?php echo number_format($stats['anorganik']); ?></div>
                        <div class="stat-label">Sampah Anorganik</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-item">
                        <div class="stat-icon b3">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="stat-number"><?php echo number_format($stats['b3']); ?></div>
                        <div class="stat-label">Sampah B3</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Features Section -->
    <section class="section-padding" id="features">
        <div class="container">
            <div class="section-header">
                <span class="section-tag">FITUR UNGGULAN</span>
                <h2 class="section-title">Mengapa Memilih Kami?</h2>
                <p class="section-desc">Kami menggabungkan teknologi modern dengan partisipasi masyarakat untuk menciptakan dampak nyata.</p>
            </div>
            
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon-wrapper">
                            <i class="fas fa-robot"></i>
                        </div>
                        <h3 class="feature-title">AI Recognition</h3>
                        <p class="feature-text">
                            Tidak perlu bingung memilah sampah. Upload fotonya, dan AI kami akan mendeteksi jenis sampah (Organik/Anorganik/B3) secara otomatis.
                        </p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon-wrapper">
                            <i class="fas fa-map-marked-alt"></i>
                        </div>
                        <h3 class="feature-title">Geo-Tagging Akurat</h3>
                        <p class="feature-text">
                            Setiap laporan dilengkapi koordinat GPS presisi, memudahkan tim kebersihan menemukan lokasi tumpukan sampah dengan cepat.
                        </p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon-wrapper">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h3 class="feature-title">Real-time Tracking</h3>
                        <p class="feature-text">
                            Pantau status laporan Anda secara transparan. Dapatkan notifikasi saat laporan diverifikasi, diproses, hingga selesai ditangani.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Map Section -->
    <section class="section-padding map-section" id="map">
        <div class="container">
            <div class="row align-items-center mb-5">
                <div class="col-lg-6">
                    <span class="section-tag">PETA PERSEBARAN</span>
                    <h2 class="section-title">Pantau Kondisi Lingkungan</h2>
                    <p class="section-desc mb-0">Lihat titik-titik pelaporan sampah di sekitar Anda.</p>
                </div>
                <div class="col-lg-6 text-lg-end mt-3 mt-lg-0">
                    <a href="register.php" class="btn btn-outline-dark rounded-pill px-4 fw-bold">
                        Lihat Peta Lengkap <i class="fas fa-arrow-right ms-2"></i>
                    </a>
                </div>
            </div>
            
            <div class="map-frame">
                <div id="main-map"></div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer text-white">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-5 mb-lg-0">
                    <a href="#" class="footer-brand text-decoration-none text-white">
                        <img src="favicon.svg" alt="Logo" height="30" class="d-inline-block align-text-top me-2"> LaporSampah
                    </a>
                    <p class="footer-text text-white-50">
                        Membangun kesadaran lingkungan melalui teknologi. Bersama kita wujudkan Indonesia bebas sampah.
                    </p>
                    <div>
                        <a href="#" class="social-link text-white"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="social-link text-white"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="social-link text-white"><i class="fab fa-facebook-f"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-6 mb-4">
                    <h5 class="footer-title text-white">Platform</h5>
                    <ul class="footer-links">
                        <li><a href="#" class="text-white-50 text-decoration-none hover-white">Beranda</a></li>
                        <li><a href="#features" class="text-white-50 text-decoration-none hover-white">Fitur</a></li>
                        <li><a href="#stats" class="text-white-50 text-decoration-none hover-white">Statistik</a></li>
                        <li><a href="#map" class="text-white-50 text-decoration-none hover-white">Peta</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-6 mb-4">
                    <h5 class="footer-title text-white">Dukungan</h5>
                    <ul class="footer-links">
                        <li><a href="#" class="text-white-50 text-decoration-none hover-white">Pusat Bantuan</a></li>
                        <li><a href="#" class="text-white-50 text-decoration-none hover-white">Syarat & Ketentuan</a></li>
                        <li><a href="#" class="text-white-50 text-decoration-none hover-white">Kebijakan Privasi</a></li>
                        <li><a href="#" class="text-white-50 text-decoration-none hover-white">Kontak</a></li>
                    </ul>
                </div>
                <div class="col-lg-4">
                    <h5 class="footer-title text-white">Hubungi Kami</h5>
                    <ul class="footer-links text-white-50">
                        <li><i class="fas fa-envelope me-2"></i> hello@laporsampah.id</li>
                        <li><i class="fas fa-phone me-2"></i> +62 812 3456 7890</li>
                        <li><i class="fas fa-map-marker-alt me-2"></i> Jakarta Selatan, Indonesia</li>
                    </ul>
                </div>
            </div>
            <div class="border-top border-secondary border-opacity-25 mt-5 pt-4 text-center">
                <small class="text-white-50">&copy; 2024 Lapor Sampah. All rights reserved.</small>
            </div>
        </div>
    </footer>

    <!-- Login Modal (Redesigned) -->
    <div class="modal fade" id="loginModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <!-- Decorative Blobs -->
                <div class="modal-decoration-blob blob-1"></div>
                <div class="modal-decoration-blob blob-2"></div>

                <div class="modal-header border-0 p-4 pb-0">
                    <h5 class="modal-title fw-bold">Masuk ke Akun Anda</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 p-md-5 pt-2 position-relative z-1">
                    <div class="text-center mb-4">
                        <div class="d-inline-flex align-items-center justify-content-center bg-teal-50 rounded-circle text-primary mb-3 shadow-sm" style="width: 70px; height: 70px;">
                            <i class="fas fa-user-circle fs-2"></i>
                        </div>
                        <h4 class="fw-bold mb-1">Selamat Datang Kembali</h4>
                        <p class="text-muted small">Masuk untuk mengelola laporan sampah Anda</p>
                    </div>

                    <?php if ($login_error): ?>
                        <div class="alert alert-danger border-0 bg-red-50 text-danger py-2 rounded-3 small mb-4">
                            <i class="fas fa-exclamation-circle me-1"></i> <?php echo $login_error; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <input type="hidden" name="action" value="login">
                        
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" name="username" id="loginUsername" placeholder="Username" required>
                            <label for="loginUsername">Username</label>
                        </div>
                        
                        <div class="form-floating mb-4">
                            <input type="password" class="form-control" name="password" id="loginPassword" placeholder="Password" required>
                            <label for="loginPassword">Password</label>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 rounded-3 py-3 mb-3">
                            Masuk Sekarang <i class="fas fa-arrow-right ms-2"></i>
                        </button>
                        
                        <div class="divider-text">atau masuk dengan</div>
                        
                        <a href="auth/google_login.php" class="btn btn-google w-100 rounded-3 py-2.5 d-flex align-items-center justify-content-center gap-2">
                            <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" alt="Google" width="20">
                            Masuk dengan Google
                        </a>
                    </form>
                </div>
                <div class="modal-footer border-0 justify-content-center bg-gray-50 p-3">
                    <p class="mb-0 text-muted small">Belum punya akun? <a href="#" data-bs-toggle="modal" data-bs-target="#registerModal" class="text-primary fw-bold text-decoration-none">Daftar sekarang</a></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Register Modal (Redesigned) -->
    <div class="modal fade" id="registerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <!-- Decorative Blobs -->
                <div class="modal-decoration-blob blob-1"></div>
                <div class="modal-decoration-blob blob-2" style="bottom: 0; left: 0;"></div>

                <div class="modal-header border-0 p-4 pb-0">
                    <h5 class="modal-title fw-bold">Buat Akun Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 p-md-5 pt-2 position-relative z-1">
                    <div class="text-center mb-4">
                        <div class="d-inline-flex align-items-center justify-content-center bg-teal-50 rounded-circle text-primary mb-3 shadow-sm" style="width: 70px; height: 70px;">
                            <i class="fas fa-user-plus fs-2"></i>
                        </div>
                        <h4 class="fw-bold mb-1">Daftar Sekarang</h4>
                        <p class="text-muted small">Bergabunglah dengan komunitas peduli lingkungan</p>
                    </div>

                    <?php if ($register_error): ?>
                        <div class="alert alert-danger border-0 bg-red-50 text-danger py-2 rounded-3 small mb-4"><?php echo $register_error; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($register_success): ?>
                        <div class="alert alert-success border-0 bg-green-50 text-success py-2 rounded-3 small mb-4"><?php echo $register_success; ?></div>
                    <?php endif; ?>

                    <form method="POST" action="" id="registerForm">
                        <input type="hidden" name="action" value="register">
                        
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" name="username" id="regUsername" placeholder="Username" required>
                                    <label for="regUsername">Username</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" name="nama" id="regNama" placeholder="Nama Lengkap" required>
                                    <label for="regNama">Nama Lengkap</label>
                                </div>
                            </div>
                        </div>

                        <div class="form-floating mb-3">
                            <input type="tel" class="form-control" name="nomor_hp" id="regHp" placeholder="No. HP" pattern="[0-9+\-\s()]+" required>
                            <label for="regHp">Nomor HP / WhatsApp</label>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="password" class="form-control" name="password" id="regPass" placeholder="Password" required>
                                    <label for="regPass">Password</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="password" class="form-control" name="confirm_password" id="regConfirmPass" placeholder="Konfirmasi" required>
                                    <label for="regConfirmPass">Konfirmasi Password</label>
                                </div>
                            </div>
                        </div>

                        <div class="form-floating mb-4">
                            <textarea class="form-control" name="alamat" id="regAlamat" placeholder="Alamat" style="height: 100px" required></textarea>
                            <label for="regAlamat">Alamat Lengkap</label>
                        </div>
                        
                        <!-- Map Decor Card -->
                        <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden position-relative">
                            <div class="card-body p-0">
                                <div class="position-absolute top-0 start-0 w-100 p-3 d-flex justify-content-between align-items-center" style="z-index: 400; background: linear-gradient(to bottom, rgba(255,255,255,0.9), transparent); pointer-events: none;">
                                    <label class="fw-bold small text-muted text-uppercase ls-1" style="pointer-events: auto;">Lokasi Peta (Opsional)</label>
                                    <button type="button" class="btn btn-sm btn-white border shadow-sm rounded-pill btn-location-picker fw-bold text-primary" onclick="getRegisterLocation()" style="pointer-events: auto;">
                                        <i class="fas fa-location-arrow me-1"></i> Lokasi Saya
                                    </button>
                                </div>
                                <div id="register-map" style="height: 250px; width: 100%;"></div>
                                <input type="hidden" name="latitude" id="reg_latitude">
                                <input type="hidden" name="longitude" id="reg_longitude">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 rounded-3 py-3 mb-3">
                            <i class="fas fa-check-circle me-2"></i>Daftar Sekarang
                        </button>
                        
                        <div class="divider-text">atau daftar dengan</div>
                        
                        <a href="auth/google_login.php" class="btn btn-google w-100 rounded-3 py-2.5 d-flex align-items-center justify-content-center gap-2">
                            <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" alt="Google" width="20">
                            Daftar dengan Google
                        </a>
                    </form>
                </div>
                <div class="modal-footer border-0 justify-content-center bg-gray-50 p-3">
                    <p class="mb-0 text-muted small">Sudah punya akun? <a href="#" data-bs-toggle="modal" data-bs-target="#loginModal" class="text-primary fw-bold text-decoration-none">Login sekarang</a></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Report Modal (Premium AI Version) -->
    <div class="modal fade" id="quickReportModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content overflow-hidden border-0">
                <div class="modal-header border-0 pb-0 pt-4 px-4 align-items-center">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-teal-50 text-success rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                            <i class="fas fa-bolt fs-4"></i>
                        </div>
                        <div>
                            <h4 class="modal-title fw-bold text-dark mb-1">Lapor Cepat AI</h4>
                            <p class="text-muted small mb-0 fw-medium">Deteksi otomatis dengan teknologi kecerdasan buatan</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close bg-light rounded-circle p-2" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 p-lg-5 pt-4">
                    <!-- Alerts -->
                    <div id="quickReportAlerts">
                        <?php if($register_error): ?>
                            <div class="alert alert-danger border-0 rounded-3 small mb-3">
                                <i class="fas fa-exclamation-circle me-2"></i> <?php echo $register_error; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if($register_success): ?>
                            <div class="alert alert-success border-0 rounded-3 small mb-3">
                                <i class="fas fa-check-circle me-2"></i> <?php echo $register_success; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <form action="" method="POST" enctype="multipart/form-data" id="quickReportForm">
                        <input type="hidden" name="action" value="guest_report">
                        
                        <div class="row g-5">
                            <!-- Left Column: Upload & Status -->
                            <div class="col-lg-5">
                                <div class="mb-4 position-relative">
                                    <span class="badge bg-primary rounded-pill mb-2 px-3 py-2">Langkah 1</span>
                                    <h6 class="fw-bold mb-3">Upload Foto Sampah</h6>
                                    <div class="upload-zone text-center p-5 position-relative hover-lift overflow-hidden rounded-4" id="uploadZone" style="min-height: 400px; display: flex; align-items: center; justify-content: center;">
                                        <input type="file" name="gambar" id="guestImageInput" class="d-none" accept="image/*" required>
                                        
                                        <!-- Placeholder -->
                                        <div class="upload-content py-4">
                                            <div class="bg-white rounded-circle shadow-sm d-inline-flex p-4 mb-3">
                                                <i class="fas fa-camera text-primary display-6"></i>
                                            </div>
                                            <p class="fw-bold mb-1 fs-5">Tarik foto ke sini</p>
                                            <small class="text-muted">atau klik untuk mengambil foto</small>
                                        </div>
                                        
                                        <!-- Preview Image -->
                                        <img id="imagePreview" src="#" alt="Preview" class="w-100 h-100 position-absolute top-0 start-0 shadow-sm" style="display: none; object-fit: cover; z-index: 10;">
                                        
                                        <!-- Scanning Effect -->
                                        <div class="scanning-line" id="scanningLine"></div>
                                        <div class="ai-pulse" id="aiPulse"></div>
                                    </div>
                                </div>
                                
                                <!-- AI Status Card (Left) -->
                                <div id="aiResult" class="d-none fade-in-up">
                                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden position-relative">
                                        <!-- Result Content -->
                                        <div id="aiResultContent" class="position-relative z-1">
                                            <div class="card-body p-4 text-center">
                                                <div id="aiIconWrapper" class="mb-3 d-inline-block"></div>
                                                <h5 class="fw-bold mb-1" id="aiMessageResult">-</h5>
                                                <span class="badge rounded-pill px-3 py-2 mt-2" id="aiConfidenceResult">0%</span>
                                            </div>
                                        </div>
                                        
                                        <!-- Scanning State -->
                                        <div id="aiScanningState" class="d-none">
                                            <div class="card-body p-4 text-center">
                                                <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;"></div>
                                                <h6 class="fw-bold mb-1 animate-pulse">Sedang Menganalisa...</h6>
                                                <p class="text-muted small mb-0">Mohon tunggu sebentar</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Column: Details -->
                            <div class="col-lg-7">
                                <span class="badge bg-secondary rounded-pill mb-2 px-3 py-2">Langkah 2</span>
                                <h6 class="fw-bold mb-4">Lengkapi Data Laporan</h6>

                                <!-- Personal Data -->
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" name="guest_name" id="guestName" placeholder="Nama Lengkap" required>
                                            <label for="guestName">Nama Lengkap</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="tel" class="form-control" name="guest_contact" id="guestContact" placeholder="No. WhatsApp" required>
                                            <label for="guestContact">No. WhatsApp</label>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Location -->
                                <div class="mb-4">
                                    <label class="form-label fw-bold small text-muted mb-2">LOKASI KEJADIAN</label>
                                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-3">
                                        <div class="position-relative" style="height: 200px;">
                                            <div id="guest-map" class="w-100 h-100"></div>
                                            <button type="button" class="btn btn-primary position-absolute bottom-0 end-0 m-3 rounded-pill shadow-lg border-2 border-white btn-location-picker-guest fw-bold hover-lift" onclick="getGuestLocation()" style="z-index: 999; backdrop-filter: blur(4px);">
                                                <i class="fas fa-location-arrow me-2"></i> Lokasi Saya
                                            </button>
                                        </div>
                                    </div>
                                    <div class="form-floating">
                                        <textarea class="form-control" name="alamat" id="guest_alamat" placeholder="Detail Lokasi" style="height: 100px" required></textarea>
                                        <label for="guest_alamat">Detail Alamat / Patokan</label>
                                    </div>
                                    <input type="hidden" name="latitude" id="guest_latitude">
                                    <input type="hidden" name="longitude" id="guest_longitude">
                                </div>

                                <!-- Hidden AI Inputs -->
                                <input type="hidden" name="kategori" id="guestKategori">
                                    <div class="form-floating mb-3">
                                        <input type="text" class="form-control" name="jenis_sampah" id="guestJenisSampah" placeholder="Jenis Sampah" required>
                                        <label for="guestJenisSampah">Jenis Sampah (Contoh: Botol Plastik)</label>
                                    </div>
                                <input type="hidden" name="deskripsi" id="guestDeskripsi">
                                <input type="hidden" name="confidence" id="guestConfidenceInput">

                                <!-- AI Detailed Result (Right) -->
                                <div id="aiDetails" class="card bg-teal-50 border-0 mb-4 rounded-4 d-none">
                                    <div class="card-body p-4">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="bg-white p-2 rounded-circle shadow-sm me-3 text-primary">
                                                <i class="fas fa-clipboard-check fs-5"></i>
                                            </div>
                                            <div>
                                                <small class="text-uppercase text-muted fw-bold" style="font-size: 0.7rem;">HASIL ANALISIS</small>
                                                <h6 class="mb-0 fw-bold">Rincian Deteksi</h6>
                                            </div>
                                            <span class="badge bg-success ms-auto rounded-pill px-3"><i class="fas fa-check me-1"></i> Verified AI</span>
                                        </div>
                                        
                                        <div class="row g-3">
                                            <div class="col-6">
                                                <div class="bg-white p-3 rounded-3 text-center h-100 border border-success border-opacity-10">
                                                    <small class="text-muted d-block mb-1" style="font-size: 0.7rem;">KATEGORI</small>
                                                    <span class="fw-bold text-dark d-block" id="displayKategori">-</span>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="bg-white p-3 rounded-3 text-center h-100">
                                                    <small class="text-muted d-block mb-1" style="font-size: 0.7rem;">AKURASI</small>
                                                    <span class="fw-bold text-dark d-block" id="displayConfidenceBadge">-</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary w-100 py-3 rounded-3 fw-bold shadow-lg hover-lift" id="btnSubmitGuest">
                                    <i class="fas fa-paper-plane me-2"></i> KIRIM LAPORAN SEKARANG
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <script>

        var registerMap, registerMarker;
        var guestMap, guestMarker;


        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.landing-navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });


        document.addEventListener('DOMContentLoaded', function() {
            var map = L.map('main-map', {
                scrollWheelZoom: false,
                zoomControl: false
            }).setView([-6.2088, 106.8456], 12); // Default Jakarta

            L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
                subdomains: 'abcd',
                maxZoom: 19
            }).addTo(map);

            var markers = [];
            
            <?php foreach ($recentReports as $r): 
                if ($r['lokasi_latitude'] && $r['lokasi_longitude']):
                    $lat = $r['lokasi_latitude'];
                    $lng = $r['lokasi_longitude'];
                    
                    $color = '#16a34a'; 
                    if ($r['kategori'] == 'anorganik') $color = '#2563eb';
                    if ($r['kategori'] == 'b3') $color = '#dc2626';
            ?>
                var marker = L.circleMarker([<?php echo $lat; ?>, <?php echo $lng; ?>], {
                    radius: 8,
                    fillColor: '<?php echo $color; ?>',
                    color: '#fff',
                    weight: 2,
                    opacity: 1,
                    fillOpacity: 1
                }).bindPopup("<b><?php echo ucfirst($r['kategori']); ?></b><br><?php echo htmlspecialchars(substr($r['alamat_lokasi'], 0, 30)); ?>...");
                
                marker.addTo(map);
                markers.push(marker);
            <?php endif; endforeach; ?>


            if (markers.length > 0) {
                var group = new L.featureGroup(markers);
                map.fitBounds(group.getBounds().pad(0.1));
            }
            





            document.getElementById('registerModal').addEventListener('shown.bs.modal', function () {
                if (!registerMap) {
                    registerMap = L.map('register-map').setView([-6.2088, 106.8456], 13);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '© OpenStreetMap contributors'
                    }).addTo(registerMap);

                    registerMap.on('click', function(e) {
                        setRegisterMarker(e.latlng.lat, e.latlng.lng);
                    });
                }
                setTimeout(function() {
                    registerMap.invalidateSize();
                }, 10);
            });
            



            document.getElementById('quickReportModal').addEventListener('shown.bs.modal', function () {
                if (!guestMap) {
                    guestMap = L.map('guest-map').setView([-6.2088, 106.8456], 13);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '© OpenStreetMap contributors'
                    }).addTo(guestMap);

                    guestMap.on('click', function(e) {
                        setGuestMarker(e.latlng.lat, e.latlng.lng);
                    });
                }
                setTimeout(function() {
                    guestMap.invalidateSize();
                }, 10);
            });
        });

        function setRegisterMarker(lat, lng) {
            if (registerMarker) {
                registerMap.removeLayer(registerMarker);
            }
            registerMarker = L.marker([lat, lng], {draggable: true}).addTo(registerMap);
            

            document.getElementById('reg_latitude').value = lat;
            document.getElementById('reg_longitude').value = lng;
            

            registerMarker.on('dragend', function(e) {
                var position = registerMarker.getLatLng();
                document.getElementById('reg_latitude').value = position.lat;
                document.getElementById('reg_longitude').value = position.lng;
            });
            
            registerMap.setView([lat, lng], 16);
        }

        function getRegisterLocation() {
            if (navigator.geolocation) {
                const btn = document.querySelector('.btn-location-picker');
                const originalText = btn.innerHTML;
                
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
                btn.disabled = true;
                
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        setRegisterMarker(lat, lng);
                        
                        btn.innerHTML = '<i class="fas fa-check"></i> Found';
                        btn.classList.remove('btn-outline-primary');
                        btn.classList.add('btn-success');
                        setTimeout(() => {
                            btn.innerHTML = originalText;
                            btn.disabled = false;
                            btn.classList.remove('btn-success');
                            btn.classList.add('btn-outline-primary');
                        }, 2000);
                    },
                    function(error) {
                        console.error("Error getting location: ", error);
                        btn.innerHTML = '<i class="fas fa-exclamation-circle"></i> Error';
                        setTimeout(() => {
                            btn.innerHTML = originalText;
                            btn.disabled = false;
                        }, 2000);
                        alert("Gagal mengambil lokasi. Pastikan GPS aktif.");
                    }
                );
            } else {
                alert("Geolocation tidak didukung oleh browser ini.");
            }
        }

        

        function setGuestMarker(lat, lng) {
            if (guestMarker) {
                guestMap.removeLayer(guestMarker);
            }
            guestMarker = L.marker([lat, lng], {draggable: true}).addTo(guestMap);
            
            document.getElementById('guest_latitude').value = lat;
            document.getElementById('guest_longitude').value = lng;
            
            guestMarker.on('dragend', function(e) {
                var position = guestMarker.getLatLng();
                document.getElementById('guest_latitude').value = position.lat;
                document.getElementById('guest_longitude').value = position.lng;
                

                fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${position.lat}&lon=${position.lng}`)
                    .then(response => response.json())
                    .then(data => {
                        if(data.display_name) {
                             document.getElementById('guest_alamat').value = data.display_name;
                        }
                    });
            });
            
            guestMap.setView([lat, lng], 16);
        }

        function getGuestLocation() {
            if (navigator.geolocation) {
                const btn = document.querySelector('.btn-location-picker-guest');
                const originalText = btn.innerHTML;
                const addressInput = document.getElementById('guest_alamat');
                
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Akurasi GPS...';
                btn.disabled = true;


                let bestAccuracy = Infinity;
                let bestPos = null;
                let attempt = 0;
                
                const watchId = navigator.geolocation.watchPosition(
                    function(position) {
                        attempt++;
                        const acc = position.coords.accuracy;
                        

                        if (acc < bestAccuracy) {
                            bestAccuracy = acc;
                            bestPos = position;
                        }



                        if (acc < 30 || attempt >= 3) {
                            navigator.geolocation.clearWatch(watchId);
                            finishLocation(bestPos || position);
                        }
                    },
                    function(error) {
                        navigator.geolocation.clearWatch(watchId);
                        alert("Gagal mengambil lokasi. Pastikan GPS aktif.");
                        btn.innerHTML = originalText;
                        btn.disabled = false;
                    },
                    { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
                );


                setTimeout(() => {
                    navigator.geolocation.clearWatch(watchId);
                    if (bestPos) {
                        finishLocation(bestPos);
                    } else if (attempt === 0) {

                        btn.innerHTML = originalText;
                        btn.disabled = false;
                    }
                }, 5000);

                function finishLocation(pos) {
                    const lat = pos.coords.latitude;
                    const lng = pos.coords.longitude;
                    const accuracy = pos.coords.accuracy;

                    setGuestMarker(lat, lng);
                    

                    if(guestMap) {
                         guestMap.eachLayer((layer) => {
                             if(layer instanceof L.Circle) {
                                 guestMap.removeLayer(layer);
                             }
                         });
                         L.circle([lat, lng], {radius: accuracy, color: '#14b8a6', fillColor: '#2dd4bf', fillOpacity: 0.2}).addTo(guestMap);
                         guestMap.setView([lat, lng], 18);
                    }


                    fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
                        .then(res => res.json())
                        .then(data => {
                            if(data.display_name) addressInput.value = data.display_name;
                            btn.innerHTML = '<i class="fas fa-check"></i> Ketemu! (' + Math.round(accuracy) + 'm)';
                            setTimeout(() => { btn.innerHTML = originalText; btn.disabled = false; }, 3000);
                        })
                        .catch(err => {
                            btn.innerHTML = originalText;
                            btn.disabled = false;
                        });
                }
            } else {
                alert("Browser tidak support GPS.");
            }
        }


        <?php if ($login_error): ?>
        var loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
        loginModal.show();
        <?php endif; ?>

        <?php if ($register_error || $register_success): ?>
            <?php if (isset($_POST['action']) && $_POST['action'] === 'guest_report'): ?>
                <?php if ($register_success): ?>

                    Swal.fire({
                        title: 'Laporan Terkirim!',
                        text: '<?php echo $register_success; ?>',
                        imageUrl: 'favicon.svg',
                        imageWidth: 80,
                        imageHeight: 80,
                        imageAlt: 'Logo',
                        icon: 'success',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#0d9488',
                        timer: 5000,
                        timerProgressBar: true
                    });
                <?php else: ?>

                    var quickReportModal = new bootstrap.Modal(document.getElementById('quickReportModal'));
                    quickReportModal.show();
                <?php endif; ?>
            <?php else: ?>
                var registerModal = new bootstrap.Modal(document.getElementById('registerModal'));
                registerModal.show();
            <?php endif; ?>
        <?php endif; ?>


        const uploadZone = document.getElementById('uploadZone');
        const fileInput = document.getElementById('guestImageInput');
        const imagePreview = document.getElementById('imagePreview');
        const scanningLine = document.getElementById('scanningLine');
        const aiPulse = document.getElementById('aiPulse');
        const aiResult = document.getElementById('aiResult');
        const aiMessage = document.getElementById('aiMessage');
        const aiConfidence = document.getElementById('aiConfidence');
        const kategoriSelect = document.getElementById('guestKategori');
        const deskripsiArea = document.getElementById('guestDeskripsi');

        if(uploadZone) {
            uploadZone.addEventListener('click', () => fileInput.click());

            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                uploadZone.addEventListener(eventName, preventDefaults, false);
            });

            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }

            ['dragenter', 'dragover'].forEach(eventName => {
                uploadZone.addEventListener(eventName, () => uploadZone.classList.add('dragover'), false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                uploadZone.addEventListener(eventName, () => uploadZone.classList.remove('dragover'), false);
            });

            uploadZone.addEventListener('drop', handleDrop, false);
            fileInput.addEventListener('change', handleFiles, false);
        }

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            handleFiles({ target: { files: files } });
        }

        function handleFiles(e) {
            const files = e.target.files || e.dataTransfer.files;
            if (files.length > 0) {
                const file = files[0];
                

                if (e.type === 'drop') {
                    fileInput.files = files;
                }

                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {

                        imagePreview.src = e.target.result;
                        imagePreview.style.display = 'block';
                        uploadZone.classList.add('has-image');
                        

                        startAIAnalysis(e.target.result);
                    }
                    reader.readAsDataURL(file);
                }
            }
        }

        async function startAIAnalysis(base64Image) {

            scanningLine.style.display = 'block';
            aiPulse.style.display = 'block';
            

            const aiResult = document.getElementById('aiResult');
            const aiScanning = document.getElementById('aiScanningState');
            const aiContent = document.getElementById('aiResultContent');
            
            aiResult.classList.remove('d-none');
            
            if(aiScanning) aiScanning.classList.remove('d-none');
            if(aiContent) aiContent.classList.add('d-none');
            document.getElementById('aiDetails').classList.add('d-none');
            

            const img = new Image();
            img.src = base64Image;
            
            img.onload = async function() {
                try {


                    const predictions = await predictImage(img);
                    

                    scanningLine.style.display = 'none';
                    aiPulse.style.display = 'none';
                    
                    if (predictions && predictions.length > 0) {
                        const bestPred = predictions[0];
                        

                        const analysisData = {
                            category: bestPred.className,
                            confidence: bestPred.probability,
                            objects: bestPred.objects || [],
                            reason: bestPred.reason || '',
                            item_name: '' // classifier.js might not return item_name parsing logic, but we handle missing item_name in updateFormWithAI
                        };
                        
                        updateFormWithAI(analysisData);
                        
                    } else {
                        throw new Error("Gagal mendeteksi sampah.");
                    }
                    
                } catch (error) {
                    console.error('AI Error:', error);
                    scanningLine.style.display = 'none';
                    aiPulse.style.display = 'none';
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Menganalisis',
                        text: error.message || 'Terjadi kesalahan pada AI.'
                    });
                }
            };
        }

        function getBadgeClass(category) {
            const cat = category.toLowerCase();
            if(cat.includes('organik')) return 'bg-success';
            if(cat.includes('b3') || cat.includes('berbahaya')) return 'bg-danger';
            return 'bg-primary';
        }

        function capitalizeFirstLetter(string) {
             return string.charAt(0).toUpperCase() + string.slice(1);
        }

        function updateFormWithAI(analysis) {

             const data = analysis; // Fix: Alias for legacy 'data' references
            let categoryValue = 'anorganik';
            const aiCat = analysis.category ? analysis.category.toLowerCase() : 'anorganik';
            

            if (aiCat === 'organik' || (aiCat.includes('organik') && !aiCat.includes('anorganik'))) {
                 categoryValue = 'organik';
            } else if (aiCat.includes('b3') || aiCat.includes('berbahaya')) {
                 categoryValue = 'b3';
            } else {
                 categoryValue = 'anorganik';
            }
            


            document.getElementById('guestKategori').value = categoryValue;
            

            const confVal = analysis.confidence ? Math.round(analysis.confidence * 100) : 0;
            document.getElementById('guestConfidenceInput').value = confVal;
            

            let specificItem = analysis.item_name || (analysis.objects && analysis.objects.length > 0 ? analysis.objects[0] : capitalizeFirstLetter(categoryValue));
            document.getElementById('guestJenisSampah').value = specificItem;
            

            let desc = `[Deteksi AI] `;
            if(specificItem) {
                 desc += `Item: ${specificItem}. `;
            }
            if(analysis.objects && Array.isArray(analysis.objects)) {
                 desc += `Objek lain: ${analysis.objects.join(', ')}. `;
            }
            
            document.getElementById('guestDeskripsi').value = desc;


            document.getElementById('displayKategori').innerHTML = `<span class="${categoryValue === 'b3' ? 'text-danger' : (categoryValue === 'organik' ? 'text-success' : 'text-primary')} fw-bold">${capitalizeFirstLetter(categoryValue)}</span>`;

            

            const confPercent = Math.round(analysis.confidence * 100);
            const badge = document.getElementById('displayConfidenceBadge');
            badge.textContent = confPercent + "%";
            badge.className = `badge ${getBadgeClass(analysis.category)} text-white`;
            


            const aiResult = document.getElementById('aiResult');
            const aiScanning = document.getElementById('aiScanningState');
            const aiContent = document.getElementById('aiResultContent');
            const aiConfidenceResult = document.getElementById('aiConfidenceResult');
            const aiMessageResult = document.getElementById('aiMessageResult');
            const aiIconWrapper = document.getElementById('aiIconWrapper');
            

            if(aiScanning) aiScanning.classList.add('d-none');
            if(aiContent) aiContent.classList.remove('d-none');
            
            aiResult.classList.remove('d-none');

            

            let iconHtml = '<i class="fas fa-cube text-primary display-4"></i>';
            if(categoryValue === 'organik') iconHtml = '<i class="fas fa-leaf text-success display-4"></i>';
            if(categoryValue === 'b3') iconHtml = '<i class="fas fa-radiation text-danger display-4"></i>';
            
            if(aiIconWrapper) aiIconWrapper.innerHTML = iconHtml;
            
            aiConfidenceResult.textContent = confPercent + "%";
            aiConfidenceResult.className = `badge rounded-pill px-3 py-2 mt-2 ${categoryValue === 'b3' ? 'bg-danger' : (categoryValue === 'organik' ? 'bg-success' : 'bg-primary')}`;
            
            aiMessageResult.textContent = `${capitalizeFirstLetter(categoryValue)} Terdeteksi`;
            aiMessageResult.className = `fw-bold mb-1 ${categoryValue === 'b3' ? 'text-danger' : (categoryValue === 'organik' ? 'text-success' : 'text-primary')}`;
            

            document.getElementById('aiDetails').classList.remove('d-none');
        }
    </script>
    <script>



        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('quickReportForm');
            if (form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();


                    const guestName = document.getElementById('guestName').value;
                    const guestContact = document.getElementById('guestContact').value;
                    const guestImage = document.getElementById('guestImageInput').files[0];

                    if (!guestName || !guestContact || !guestImage) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Data Belum Lengkap',
                            text: 'Mohon lengkapi nama, nomor WhatsApp, dan foto sampah.',
                            confirmButtonColor: '#0d9488'
                        });
                        return;
                    }


                    Swal.fire({
                        title: 'Mengirim Laporan...',
                        html: 'Mohon tunggu sebentar, kami sedang memproses data Anda.',
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });


                    const formData = new FormData(this);
                    formData.append('ajax', '1'); // Flag for backend


                    fetch('index.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }

                        return response.text().then(text => {
                            try {
                                return JSON.parse(text);
                            } catch (e) {
                                console.error('Server response:', text); // Log raw for debugging
                                throw new Error('Invalid JSON response from server');
                            }
                        });
                    })
                    .then(data => {
                        if (data.status === 'success') {

                            Swal.fire({
                                title: 'Laporan Terkirim!',
                                text: data.message,
                                imageUrl: 'favicon.svg',
                                imageWidth: 80,
                                imageHeight: 80,
                                imageAlt: 'Logo',
                                icon: 'success',
                                confirmButtonText: 'OK',
                                confirmButtonColor: '#0d9488',
                                timer: 5000,
                                timerProgressBar: true
                            }).then((result) => {

                                form.reset();

                                document.getElementById('imagePreview').style.display = 'none';
                                document.getElementById('uploadZone').querySelector('.upload-content').style.display = 'block';

                                document.getElementById('aiResult').classList.add('d-none');
                                document.getElementById('aiDetails').classList.add('d-none');
                                

                                const modalEl = document.getElementById('quickReportModal');
                                const modal = bootstrap.Modal.getInstance(modalEl);
                                if (modal) modal.hide();
                                

                                location.reload(); // Optional: reload to show new pin on map immediately? 
                           

                            });
                        } else {
                            throw new Error(data.message || 'Terjadi kesalahan unknown.');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal Mengirim',
                            text: error.message || 'Terjadi kesalahan saat mengirim laporan. Silakan coba lagi.',
                            confirmButtonColor: '#ef4444'
                        });
                    });
                });
            }
        });
    </script>
    <!-- AI Classifier Logic -->
    <script src="assets/js/classifier.js"></script>
</body>
</html>
