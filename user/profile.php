<?php
require_once '../config/config.php';
require_once '../config/Database.php';
require_once '../classes/User.php';
require_once '../classes/Report.php';

checkLogin();

$database = new Database();
$db = $database->getConnection();
$user = new User($db);
$report = new Report($db);


$userData = $user->getUserById($_SESSION['user_id']);


$userReports = $report->getByUserId($_SESSION['user_id']);
$totalReports = count($userReports);


$pending = 0;
$diproses = 0;
$selesai = 0;
$organik = 0;
$anorganik = 0;
$b3 = 0;

foreach ($userReports as $r) {
    if ($r['status'] == 'pending') $pending++;
    if ($r['status'] == 'diproses') $diproses++;
    if ($r['status'] == 'selesai') $selesai++;
    if ($r['kategori'] == 'organik') $organik++;
    if ($r['kategori'] == 'anorganik') $anorganik++;
    if ($r['kategori'] == 'b3') $b3++;
}

$success = '';
$error = '';


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $nama = trim($_POST['nama']);
    $alamat = trim($_POST['alamat']);
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];
    
    if (!empty($nama) && !empty($alamat)) {
        $query = "UPDATE users SET nama = :nama, alamat = :alamat, 
                  latitude = :latitude, longitude = :longitude 
                  WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':nama', $nama);
        $stmt->bindParam(':alamat', $alamat);
        $stmt->bindParam(':latitude', $latitude);
        $stmt->bindParam(':longitude', $longitude);
        $stmt->bindParam(':id', $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            $success = 'Profile berhasil diupdate!';
            $_SESSION['nama'] = $nama;
            $userData = $user->getUserById($_SESSION['user_id']);
        } else {
            $error = 'Gagal update profile.';
        }
    } else {
        $error = 'Nama dan alamat tidak boleh kosong!';
    }
}


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (!empty($current_password) && !empty($new_password)) {

        $query = "SELECT password FROM users WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $_SESSION['user_id']);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (password_verify($current_password, $row['password'])) {
            if ($new_password === $confirm_password) {
                $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
                $query = "UPDATE users SET password = :password WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':password', $hashed_password);
                $stmt->bindParam(':id', $_SESSION['user_id']);
                
                if ($stmt->execute()) {
                    $success = 'Password berhasil diubah!';
                } else {
                    $error = 'Gagal mengubah password.';
                }
            } else {
                $error = 'Password baru dan konfirmasi tidak cocok!';
            }
        } else {
            $error = 'Password lama salah!';
        }
    } else {
        $error = 'Semua field password harus diisi!';
    }
}


$joinDate = new DateTime($userData['created_at']);
$now = new DateTime();
$daysSinceJoin = $now->diff($joinDate)->days;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta name="theme-color" content="#14b8a6">
    <title>Profile - Pelaporan Sampah</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="../favicon.svg">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f0fdfa 0%, #ccfbf1 100%);
            min-height: 100vh;
            position: relative;
        }
        
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 50%, rgba(20, 184, 166, 0.08) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(6, 182, 212, 0.08) 0%, transparent 50%);
            pointer-events: none;
            z-index: 0;
        }
        
        .container {
            position: relative;
            z-index: 1;
        }
        
        /* Modern Profile Card */
        .profile-card {
            background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
            border-radius: 24px;
            padding: 2.5rem;
            color: white;
            box-shadow: 0 20px 60px rgba(20, 184, 166, 0.3);
            position: relative;
            overflow: hidden;
        }
        
        .profile-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: float 15s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            50% { transform: translate(-20px, -20px) rotate(180deg); }
        }
        
        .profile-avatar {
            width: 140px;
            height: 140px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(255,255,255,0.2), rgba(255,255,255,0.1));
            border: 5px solid rgba(255, 255, 255, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            margin: 0 auto 1.5rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            position: relative;
            z-index: 1;
        }
        
        .profile-name {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .profile-username {
            font-size: 1.125rem;
            opacity: 0.9;
            font-weight: 500;
        }
        
        .profile-badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            padding: 0.5rem 1.25rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.875rem;
            margin-top: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        /* Stats Cards */
        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 1.75rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border: 1px solid rgba(20, 184, 166, 0.1);
            height: 100%;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(20, 184, 166, 0.15);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            margin-bottom: 1rem;
        }
        
        .stat-value {
            font-size: 2.25rem;
            font-weight: 800;
            line-height: 1;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: #6b7280;
            font-size: 0.9375rem;
            font-weight: 500;
        }
        
        .stat-primary .stat-icon {
            background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
            color: white;
        }
        
        .stat-primary .stat-value {
            color: #14b8a6;
        }
        
        .stat-success .stat-icon {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }
        
        .stat-success .stat-value {
            color: #10b981;
        }
        
        .stat-warning .stat-icon {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
        }
        
        .stat-warning .stat-value {
            color: #f59e0b;
        }
        
        .stat-danger .stat-icon {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }
        
        .stat-danger .stat-value {
            color: #ef4444;
        }
        
        /* Modern Forms */
        .modern-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(20, 184, 166, 0.1);
            margin-bottom: 2rem;
        }
        
        .card-title-modern {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .card-title-modern i {
            color: #14b8a6;
            font-size: 1.75rem;
        }
        
        .form-label {
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
            font-size: 0.9375rem;
        }
        
        .form-control, .form-select {
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 0.75rem 1rem;
            font-size: 0.9375rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #14b8a6;
            box-shadow: 0 0 0 4px rgba(20, 184, 166, 0.1);
        }
        
        .btn-modern {
            padding: 0.875rem 2rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            border: none;
        }
        
        .btn-primary-modern {
            background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(20, 184, 166, 0.3);
        }
        
        .btn-primary-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(20, 184, 166, 0.4);
        }
        
        .btn-secondary-modern {
            background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(107, 114, 128, 0.3);
        }
        
        .btn-secondary-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(107, 114, 128, 0.4);
        }
        
        /* Info Box */
        .info-box {
            background: linear-gradient(135deg, #f0fdfa 0%, #ccfbf1 100%);
            border: 2px solid #14b8a6;
            border-radius: 16px;
            padding: 1.25rem;
            margin-bottom: 1.5rem;
        }
        
        .info-box-title {
            color: #0d9488;
            font-weight: 700;
            font-size: 1.125rem;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .info-item {
            display: flex;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid rgba(20, 184, 166, 0.1);
        }
        
        .info-item:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 600;
            color: #0d9488;
            min-width: 140px;
        }
        
        .info-value {
            color: #1f2937;
            font-weight: 500;
        }
        
        /* Map Container */
        .map-container {
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            margin-top: 1rem;
        }
        
        /* Animations */
        .fade-in {
            animation: fadeIn 0.6s ease-out;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .slide-in {
            animation: slideIn 0.8s ease-out;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .profile-card {
                padding: 2rem 1.5rem;
            }
            
            .profile-avatar {
                width: 100px;
                height: 100px;
                font-size: 3rem;
            }
            
            .profile-name {
                font-size: 1.5rem;
            }
            
            .stat-value {
                font-size: 1.75rem;
            }
            
            .modern-card {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-leaf"></i> Pelaporan Sampah
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="lapor.php">
                            <i class="fas fa-plus-circle"></i> Lapor Sampah
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="profile.php">
                            <i class="fas fa-user-circle"></i> Profile
                        </a>
                    </li>
                    <li class="nav-item">
                        <span class="nav-link">
                            <i class="fas fa-user"></i> <?php echo isset($_SESSION['nama']) ? $_SESSION['nama'] : 'User'; ?>
                        </span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../logout.php">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Content -->
    <div class="container mt-4 mb-5">
            <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show slide-in" style="border-radius: 16px; border: 2px solid #10b981; background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);">
                <i class="fas fa-check-circle"></i> <strong><?php echo $success; ?></strong>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show slide-in" style="border-radius: 16px; border: 2px solid #ef4444; background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);">
                <i class="fas fa-exclamation-circle"></i> <strong><?php echo $error; ?></strong>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row">
            <!-- Left Column - Profile Card & Stats -->
            <div class="col-lg-4 mb-4">
                <!-- Profile Card -->
                <div class="profile-card fade-in mb-4">
                    <div class="profile-avatar" style="background: linear-gradient(135deg, rgba(255,255,255,0.2), rgba(255,255,255,0.1)); border: 5px solid rgba(255, 255, 255, 0.3); overflow: hidden;">
                        <?php if (!empty($userData['google_picture'])): ?>
                            <img src="<?php echo htmlspecialchars($userData['google_picture']); ?>" 
                                 alt="<?php echo htmlspecialchars($userData['nama']); ?>" 
                                 style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;"
                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <div style="display: none; width: 100%; height: 100%; align-items: center; justify-content: center; font-size: 4rem; color: white;">
                                <i class="fas fa-user-circle"></i>
                            </div>
                        <?php else: ?>
                            <i class="fas fa-user-circle"></i>
                        <?php endif; ?>
                    </div>
                    <div class="text-center">
                        <h1 class="profile-name"><?php echo htmlspecialchars($userData['nama']); ?></h1>
                        <p class="profile-username">@<?php echo htmlspecialchars($userData['username']); ?></p>
                        <?php if (!empty($userData['google_id'])): ?>
                            <span class="profile-badge" style="margin-right: 0.5rem;">
                                <i class="fab fa-google"></i> Google Account
                            </span>
                        <?php endif; ?>
                        <span class="profile-badge">
                            <i class="fas fa-shield-alt"></i> <?php echo strtoupper($userData['role']); ?>
                        </span>
                    </div>
                        </div>

                <!-- Account Info -->
                <div class="modern-card fade-in">
                    <div class="info-box-title">
                        <i class="fas fa-info-circle"></i> Informasi Akun
                    </div>
                    <div class="info-item">
                        <span class="info-label"><i class="fas fa-calendar-plus"></i> Bergabung</span>
                        <span class="info-value"><?php echo date('d F Y', strtotime($userData['created_at'])); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label"><i class="fas fa-clock"></i> Aktif Sejak</span>
                        <span class="info-value"><?php echo $daysSinceJoin; ?> hari</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label"><i class="fas fa-file-alt"></i> Total Laporan</span>
                        <span class="info-value"><strong><?php echo $totalReports; ?></strong> laporan</span>
                    </div>
                                    </div>
                                </div>

            <!-- Right Column - Forms & Stats -->
            <div class="col-lg-8">
                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 col-6 mb-3">
                        <div class="stat-card stat-primary fade-in" style="animation-delay: 0.1s;">
                            <div class="stat-icon">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <div class="stat-value"><?php echo $totalReports; ?></div>
                            <div class="stat-label">Total Laporan</div>
                                    </div>
                                </div>
                    <div class="col-md-3 col-6 mb-3">
                        <div class="stat-card stat-warning fade-in" style="animation-delay: 0.2s;">
                            <div class="stat-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stat-value"><?php echo $pending; ?></div>
                            <div class="stat-label">Pending</div>
                                    </div>
                                </div>
                    <div class="col-md-3 col-6 mb-3">
                        <div class="stat-card stat-primary fade-in" style="animation-delay: 0.3s;">
                            <div class="stat-icon">
                                <i class="fas fa-spinner"></i>
                            </div>
                            <div class="stat-value"><?php echo $diproses; ?></div>
                            <div class="stat-label">Diproses</div>
                                    </div>
                                </div>
                    <div class="col-md-3 col-6 mb-3">
                        <div class="stat-card stat-success fade-in" style="animation-delay: 0.4s;">
                            <div class="stat-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stat-value"><?php echo $selesai; ?></div>
                            <div class="stat-label">Selesai</div>
                        </div>
                    </div>
                </div>

                <!-- Category Stats -->
                <div class="row mb-4">
                    <div class="col-md-4 col-4 mb-3">
                        <div class="stat-card stat-success fade-in" style="animation-delay: 0.5s;">
                            <div class="stat-icon">
                                <i class="fas fa-leaf"></i>
                            </div>
                            <div class="stat-value"><?php echo $organik; ?></div>
                            <div class="stat-label">Organik</div>
                        </div>
                    </div>
                    <div class="col-md-4 col-4 mb-3">
                        <div class="stat-card stat-primary fade-in" style="animation-delay: 0.6s;">
                            <div class="stat-icon">
                                <i class="fas fa-recycle"></i>
                            </div>
                            <div class="stat-value"><?php echo $anorganik; ?></div>
                            <div class="stat-label">Anorganik</div>
                        </div>
                    </div>
                    <div class="col-md-4 col-4 mb-3">
                        <div class="stat-card stat-danger fade-in" style="animation-delay: 0.7s;">
                            <div class="stat-icon">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <div class="stat-value"><?php echo $b3; ?></div>
                            <div class="stat-label">B3</div>
                        </div>
                    </div>
                </div>

                <!-- Edit Profile Form -->
                <div class="modern-card slide-in">
                    <h2 class="card-title-modern">
                        <i class="fas fa-user-edit"></i> Edit Profile
                    </h2>
                            <form method="POST" name="update_profile">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="username" class="form-label">
                                            <i class="fas fa-user"></i> Username
                                        </label>
                                        <input type="text" class="form-control" id="username" 
                                               value="<?php echo htmlspecialchars($userData['username']); ?>" 
                                               disabled>
                                        <small class="text-muted">Username tidak dapat diubah</small>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="nama" class="form-label">
                                            <i class="fas fa-id-card"></i> Nama Lengkap <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="nama" name="nama" 
                                       value="<?php echo htmlspecialchars($userData['nama']); ?>" 
                                       required placeholder="Masukkan nama lengkap">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="alamat" class="form-label">
                                <i class="fas fa-map-marker-alt"></i> Alamat <span class="text-danger">*</span>
                                    </label>
                            <textarea class="form-control" id="alamat" name="alamat" rows="3" 
                                      required placeholder="Masukkan alamat lengkap"><?php echo htmlspecialchars($userData['alamat']); ?></textarea>
                                </div>

                        <?php 

                        $hasLocation = !empty($userData['latitude']) && !empty($userData['longitude']) && 
                                      $userData['latitude'] != '0' && $userData['longitude'] != '0';
                        

                        $isGoogleUserWithoutLocation = !empty($userData['google_id']) && !$hasLocation;
                        ?>
                        
                        <?php if ($isGoogleUserWithoutLocation): ?>
                        <div class="alert alert-warning fade-in" style="border-radius: 16px; border-left: 4px solid #f59e0b; background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);">
                            <div class="d-flex align-items-start gap-2">
                                <i class="fas fa-exclamation-triangle text-warning" style="font-size: 1.5rem; margin-top: 2px;"></i>
                                <div>
                                    <strong style="color: #92400e;">Lokasi Belum Diatur</strong>
                                    <p class="mb-2" style="color: #78350f;">
                                        Anda registrasi dengan akun Google, namun lokasi belum diatur. 
                                        <strong>Silakan pilih lokasi di peta di bawah ini</strong> untuk melengkapi profil Anda.
                                    </p>
                                    <small class="text-muted">
                                        <i class="fas fa-info-circle"></i> Lokasi diperlukan untuk melaporkan sampah dengan tepat.
                                    </small>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="info-box">
                            <div class="info-box-title">
                                <i class="fas fa-map"></i> Pilih Lokasi di Peta
                                <?php if (!$hasLocation): ?>
                                    <span class="badge bg-danger ms-2">Wajib</span>
                                <?php endif; ?>
                            </div>
                            <small class="text-muted mb-2 d-block">
                                <i class="fas fa-info-circle"></i> 
                                <?php if ($hasLocation): ?>
                                    Klik pada peta untuk mengubah lokasi rumah Anda
                                <?php else: ?>
                                    <strong>Klik pada peta atau gunakan tombol "Gunakan Lokasi Saya"</strong> untuk mengatur lokasi rumah Anda
                                <?php endif; ?>
                            </small>
                            <div class="mb-3">
                                <button type="button" class="btn btn-primary btn-sm w-100" onclick="getCurrentLocation()">
                                    <i class="fas fa-crosshairs"></i> Gunakan Lokasi Saya (GPS)
                                </button>
                            </div>
                            <div class="map-container">
                                <div id="profileMap" style="height: 300px;"></div>
                            </div>
                            <?php if (!$hasLocation): ?>
                            <div class="alert alert-info mt-2 mb-0" style="border-radius: 8px;">
                                <small>
                                    <i class="fas fa-exclamation-circle"></i> 
                                    <strong>Lokasi wajib diisi!</strong> Pilih lokasi di peta atau gunakan GPS untuk melanjutkan.
                                </small>
                            </div>
                            <?php endif; ?>
                        </div>

                        <input type="hidden" id="latitude" name="latitude" 
                               value="<?php echo (!empty($userData['latitude']) && $userData['latitude'] != '0') ? $userData['latitude'] : '3.5952'; ?>" 
                               required>
                        <input type="hidden" id="longitude" name="longitude" 
                               value="<?php echo (!empty($userData['longitude']) && $userData['longitude'] != '0') ? $userData['longitude'] : '98.6722'; ?>" 
                               required>

                        <div class="d-grid gap-2">
                            <button type="submit" name="update_profile" class="btn btn-modern btn-primary-modern" id="saveProfileBtn">
                                <i class="fas fa-save"></i> Simpan Perubahan
                            </button>
                        </div>
                        
                        <script>

                            document.querySelector('form[name="update_profile"]').addEventListener('submit', function(e) {
                                const lat = parseFloat(document.getElementById('latitude').value);
                                const lng = parseFloat(document.getElementById('longitude').value);
                                const alamat = document.getElementById('alamat').value.trim();
                                

                                const isDefaultAlamat = alamat === 'Lokasi belum diatur - Silakan lengkapi di halaman profile';
                                


                                const isDefaultLocation = (lat === 3.5952 && lng === 98.6722) && isDefaultAlamat;
                                
                                if (isDefaultLocation) {
                                    e.preventDefault();
                                    alert('⚠️ Silakan pilih lokasi di peta atau gunakan tombol "Gunakan Lokasi Saya" sebelum menyimpan profil!');
                                    document.getElementById('profileMap').scrollIntoView({ behavior: 'smooth', block: 'center' });
                                    return false;
                                }
                                

                                if (isNaN(lat) || isNaN(lng) || lat === 0 || lng === 0) {
                                    e.preventDefault();
                                    alert('⚠️ Lokasi tidak valid! Silakan pilih lokasi di peta.');
                                    return false;
                                }
                            });
                        </script>
                    </form>
                    </div>

                    <!-- Change Password Form (Only for non-Google users) -->
                    <?php if (empty($userData['google_id'])): ?>
                <div class="modern-card slide-in" style="animation-delay: 0.2s;">
                    <h2 class="card-title-modern">
                        <i class="fas fa-lock"></i> Ubah Password
                    </h2>
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">
                                <i class="fas fa-key"></i> Password Lama <span class="text-danger">*</span>
                                    </label>
                                    <input type="password" class="form-control" id="current_password" 
                                   name="current_password" required 
                                   placeholder="Masukkan password lama">
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="new_password" class="form-label">
                                            <i class="fas fa-lock"></i> Password Baru <span class="text-danger">*</span>
                                        </label>
                                        <input type="password" class="form-control" id="new_password" 
                                       name="new_password" required 
                                       placeholder="Masukkan password baru">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="confirm_password" class="form-label">
                                            <i class="fas fa-lock"></i> Konfirmasi Password <span class="text-danger">*</span>
                                        </label>
                                        <input type="password" class="form-control" id="confirm_password" 
                                       name="confirm_password" required 
                                       placeholder="Konfirmasi password baru">
                                    </div>
                                </div>
                        <div class="info-box">
                            <small>
                                <i class="fas fa-info-circle"></i> 
                                <strong>Tips keamanan:</strong> Gunakan kombinasi huruf besar, huruf kecil, angka, dan simbol untuk password yang kuat.
                            </small>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" name="change_password" class="btn btn-modern btn-secondary-modern">
                                <i class="fas fa-shield-alt"></i> Ubah Password
                                </button>
                        </div>
                    </form>
                </div>
                    <?php else: ?>
                <!-- Google users don't have passwords -->
                <div class="modern-card slide-in" style="animation-delay: 0.2s;">
                    <h2 class="card-title-modern">
                        <i class="fas fa-lock"></i> Ubah Password
                    </h2>
                    <div class="alert alert-info" style="border-radius: 16px; border-left: 4px solid #06b6d4; background: linear-gradient(135deg, #cffafe 0%, #a5f3fc 100%);">
                        <div class="d-flex align-items-start gap-2">
                            <i class="fab fa-google text-primary" style="font-size: 1.5rem; margin-top: 2px;"></i>
                            <div>
                                <strong style="color: #0e7490;">Akun Google</strong>
                                <p class="mb-0" style="color: #155e75;">
                                    Anda menggunakan akun Google untuk login, sehingga tidak perlu mengatur password di aplikasi ini.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                    <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>&copy; 2024 Sistem Pelaporan Sampah Desa | Powered by Teachable Machine</p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="../assets/js/navbar.js"></script>
    
    <script>


        const userLat = <?php echo (!empty($userData['latitude']) && $userData['latitude'] != '0') ? $userData['latitude'] : '3.5952'; ?>;
        const userLng = <?php echo (!empty($userData['longitude']) && $userData['longitude'] != '0') ? $userData['longitude'] : '98.6722'; ?>;
        const hasLocation = <?php echo (!empty($userData['latitude']) && !empty($userData['longitude']) && $userData['latitude'] != '0' && $userData['longitude'] != '0') ? 'true' : 'false'; ?>;
        

        const initialZoom = hasLocation ? 15 : 12;
        const profileMap = L.map('profileMap').setView([userLat, userLng], initialZoom);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(profileMap);
        
        let marker = L.marker([userLat, userLng], {
            draggable: true
        }).addTo(profileMap);


        async function updateAddress(lat, lng) {
            try {
                const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`);
                const data = await response.json();
                if (data && data.display_name) {
                    const alamatField = document.getElementById('alamat');

                    if (alamatField.value === 'Lokasi belum diatur - Silakan lengkapi di halaman profile' || alamatField.value === '') {
                        alamatField.value = data.display_name;
                    }
                }
            } catch (error) {
                console.error("Error fetching address:", error);
            }
        }
        

        marker.on('dragend', function(e) {
            const pos = e.target.getLatLng();
            document.getElementById('latitude').value = pos.lat;
            document.getElementById('longitude').value = pos.lng;
            updateAddress(pos.lat, pos.lng);
        });
        

        profileMap.on('click', function(e) {
            const { lat, lng } = e.latlng;
            marker.setLatLng([lat, lng]);
            document.getElementById('latitude').value = lat;
            document.getElementById('longitude').value = lng;
            
            updateAddress(lat, lng);
            

            profileMap.setView([lat, lng], 16);
        });
        

        function getCurrentLocation() {
            if (!navigator.geolocation) {
                alert('GPS tidak didukung di browser Anda. Silakan pilih lokasi secara manual di peta.');
                return;
            }
            


            const btn = document.querySelector('button[onclick="getCurrentLocation()"]');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mendapatkan lokasi...';
            btn.disabled = true;
            
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    

                    profileMap.setView([lat, lng], 17);
                    marker.setLatLng([lat, lng]);
                    

                    document.getElementById('latitude').value = lat;
                    document.getElementById('longitude').value = lng;
                    

                    updateAddress(lat, lng);
                    

                    btn.innerHTML = '<i class="fas fa-check"></i> Lokasi ditemukan!';
                    btn.classList.remove('btn-primary');
                    btn.classList.add('btn-success');
                    
                    setTimeout(() => {
                        btn.innerHTML = originalText;
                        btn.classList.remove('btn-success');
                        btn.classList.add('btn-primary');
                        btn.disabled = false;
                    }, 2000);
                },
                function(error) {
                    console.error(error); // Log detailed error
                    let msg = 'Gagal mendapatkan lokasi GPS.';
                    if(error.code === 1) msg += ' Izin lokasi ditolak.';
                    else if(error.code === 2) msg += ' Posisi tidak tersedia.';
                    else if(error.code === 3) msg += ' Waktu habis.';
                    
                    alert(msg + ' Silakan pilih secara manual di peta.');
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                }
            );
        }
        

        document.addEventListener('DOMContentLoaded', function() {
            const statValues = document.querySelectorAll('.stat-value');
            
            statValues.forEach(stat => {
                const target = parseInt(stat.textContent);
                let current = 0;
                const increment = target / 30;
                const duration = 1000;
                const stepTime = duration / 30;
                
                const timer = setInterval(() => {
                    current += increment;
                    if (current >= target) {
                        stat.textContent = target;
                        clearInterval(timer);
                } else {
                        stat.textContent = Math.floor(current);
                }
                }, stepTime);
        });
        });
    </script>
</body>
</html>
