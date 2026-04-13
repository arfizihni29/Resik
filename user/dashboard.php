<?php
require_once '../config/config.php';
require_once '../config/Database.php';
require_once '../classes/Report.php';
require_once '../classes/Comment.php';

checkLogin();

$database = new Database();
$db = $database->getConnection();
$report = new Report($db);
$comment = new Comment($db);


    function getJenisSampahBadge($jenis, $kategori = '') {
        $jenis = strtolower(trim($jenis));
        $kategori = strtolower(trim($kategori));
        

        $colors = [
            'organik' => 'success',
            'anorganik' => 'primary',
            'b3' => 'danger'
        ];
        
        $color = $colors[$kategori] ?? 'secondary';
        $icon = 'fa-recycle';
        if ($color == 'success') $icon = 'fa-leaf';
        if ($color == 'danger') $icon = 'fa-exclamation-triangle';
        

        $displayText = ucwords($jenis);
        
        return "<span class=\"badge bg-{$color}\"><i class=\"fas {$icon} me-1\"></i>{$displayText}</span>";
    }


$userReports = $report->getByUserId($_SESSION['user_id']);


foreach ($userReports as &$r) {
    $r['comment_count'] = $comment->countByReportId($r['id']);
}


$totalReports = count($userReports);
$pendingReports = 0;
$prosesReports = 0;
$selesaiReports = 0;
$ditolakReports = 0;

foreach ($userReports as $r) {
    if ($r['status'] == 'pending') $pendingReports++;
    if ($r['status'] == 'diproses') $prosesReports++;
    if ($r['status'] == 'selesai') $selesaiReports++;
    if ($r['status'] == 'ditolak') $ditolakReports++;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <meta name="theme-color" content="#14b8a6">
    <title>Dashboard - Pelaporan Sampah</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="../favicon.svg">
    <link rel="alternate icon" href="../favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="../favicon.svg">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/vue-components.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
            --secondary-gradient: linear-gradient(135deg, #f0fdfa 0%, #ccfbf1 100%);
            --card-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            --hover-shadow: 0 20px 40px rgba(20, 184, 166, 0.15);
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f0fdfa 0%, #ccfbf1 100%);
            min-height: 100vh;
            color: #1e293b;
            overflow-x: hidden;
            position: relative;
        }

        /* Ambient Background Animations */
        body::before {
            content: '';
            position: fixed;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(20, 184, 166, 0.05) 0%, transparent 60%),
                        radial-gradient(circle at 80% 20%, rgba(6, 182, 212, 0.05) 0%, transparent 40%);
            z-index: 0;
            animation: rotateBG 20s linear infinite;
        }

        @keyframes rotateBG {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .container {
            position: relative;
            z-index: 1;
        }

        /* Modern Navbar Styling */
        .navbar {
            background: rgba(255, 255, 255, 0.9) !important;
            backdrop-filter: blur(10px);
            padding: 1rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }

        /* Enhanced Hero Section */
        .dashboard-hero {
            background: var(--primary-gradient);
            border-radius: 20px;
            padding: 3rem 2rem;
            margin-bottom: 2.5rem;
            color: white;
            position: relative;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(20, 184, 166, 0.3);
            transition: transform 0.3s ease;
        }

        .dashboard-hero:hover {
            transform: translateY(-5px);
        }

        .dashboard-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='rgba(255,255,255,0.1)' fill-rule='evenodd'/%3E%3C/svg%3E");
        }

        .hero-title {
            font-size: 2.2rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .hero-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            font-weight: 500;
        }

        /* 3D Floating Elements */
        .floating-shape {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(5px);
            border-radius: 50%;
            z-index: 1;
            animation: float 6s ease-in-out infinite;
        }
        
        .shape-1 { width: 100px; height: 100px; top: -20px; right: -20px; animation-delay: 0s; }
        .shape-2 { width: 60px; height: 60px; bottom: 40px; right: 20%; animation-delay: 2s; }
        .shape-3 { width: 40px; height: 40px; top: 40px; left: 40px; animation-delay: 4s; }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }

        /* Enhanced Stats Cards */
        .stat-card-modern {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            height: 100%;
            border: 1px solid rgba(0, 0, 0, 0.05);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.02);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .stat-card-modern:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.08);
            border-color: rgba(20, 184, 166, 0.3);
        }

        .stat-icon-wrapper {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
            position: relative;
            z-index: 2;
        }

        .bg-gradient-teal { background: linear-gradient(135deg, #ccfbf1 0%, #99f6e4 100%); color: #0f766e; }
        .bg-gradient-yellow { background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); color: #b45309; }
        .bg-gradient-blue { background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); color: #1e40af; }
        .bg-gradient-green { background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%); color: #166534; }
        .bg-gradient-red { background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%); color: #991b1b; }

        .stat-value {
            font-size: 2rem;
            font-weight: 800;
            color: #1e293b;
            line-height: 1;
            margin-bottom: 0.25rem;
        }

        .stat-label {
            color: #64748b;
            font-size: 0.9rem;
            font-weight: 600;
        }

        /* Glassmorphism Action Card */
        .action-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 2.5rem;
            text-align: center;
            border: 2px solid rgba(255, 255, 255, 0.9);
            box-shadow: 0 20px 40px rgba(20, 184, 166, 0.1);
            margin-bottom: 2.5rem;
            position: relative;
            transition: all 0.3s ease;
        }

        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 30px 60px rgba(20, 184, 166, 0.2);
        }

        .btn-report-lg {
            background: var(--primary-gradient);
            color: white;
            font-weight: 700;
            padding: 1rem 3rem;
            border-radius: 50px;
            font-size: 1.1rem;
            border: none;
            box-shadow: 0 10px 20px rgba(20, 184, 166, 0.4);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-report-lg:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 30px rgba(20, 184, 166, 0.6);
            color: white;
        }

        /* Modern Table Styling */
        .table-container {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            overflow: hidden;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 800;
            color: #1e293b;
            position: relative;
            padding-left: 1rem;
        }

        .section-title::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 4px;
            height: 24px;
            background: #14b8a6;
            border-radius: 2px;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .dashboard-hero {
                padding: 2rem 1.5rem;
            }
            .hero-title {
                font-size: 1.75rem;
            }
            .stat-card-modern {
                padding: 1rem;
            }
            .action-card {
                padding: 2rem 1.5rem;
            }
        }
        
        .badge {
            padding: 0.5em 1em;
            border-radius: 50px;
            font-weight: 600;
        }

        .badge-warning { background-color: #fef3c7; color: #92400e; }
        .badge-info { background-color: #e0f2fe; color: #075985; }
        .badge-success { background-color: #dcfce7; color: #166534; }
        .badge-danger { background-color: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-transparent">
    <div class="container">
        <a class="navbar-brand text-teal-700 fw-bold" href="dashboard.php">
            <img src="../favicon.svg" alt="Logo" height="30" class="d-inline-block align-text-top me-2"> 
            <span>Pelaporan <span class="text-teal-500">Sampah</span></span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link active text-dark fw-semibold" href="dashboard.php">
                        <i class="fas fa-home text-teal-500"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-dark fw-semibold" href="lapor.php">
                        <i class="fas fa-plus-circle text-teal-500"></i> Lapor Sampah
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-dark fw-semibold" href="profile.php">
                        <i class="fas fa-user-circle text-teal-500"></i> Profile
                    </a>
                </li>
                <li class="nav-item">
                    <span class="nav-link text-dark fw-semibold">
                        <i class="fas fa-user text-teal-500"></i> <?php echo isset($_SESSION['nama']) ? htmlspecialchars($_SESSION['nama']) : 'User'; ?>
                    </span>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-danger fw-semibold" href="../logout.php">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container py-4">
    <!-- Welcome Hero -->
    <div class="dashboard-hero">
        <div class="floating-shape shape-1"></div>
        <div class="floating-shape shape-2"></div>
        <div class="floating-shape shape-3"></div>
        <div class="row align-items-center position-relative" style="z-index: 2;">
            <div class="col-md-8">
                <h1 class="hero-title">Halo, <?php echo htmlspecialchars($_SESSION['nama']); ?>! 👋</h1>
                <p class="hero-subtitle mb-0">Selamat datang di Dashboard Pelaporan Sampah.</p>
                <div class="mt-4 d-flex gap-3 align-items-center">
                    <div class="bg-white/20 backdrop-blur-sm px-4 py-2 rounded-full d-inline-flex align-items-center gap-2">
                        <i class="far fa-calendar-alt"></i>
                        <span><?php echo date('d F Y'); ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-4 text-end d-none d-md-block">
                <!-- Fallback abstract visual if image missing -->
                <div style="font-size: 8rem; color: rgba(255,255,255,0.2);">
                    <i class="fas fa-leaf"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="row g-4 mb-5">
        <div class="col-6 col-md-3">
            <div class="stat-card-modern">
                <div class="stat-icon-wrapper bg-gradient-teal">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <div class="stat-value"><?php echo $totalReports; ?></div>
                <div class="stat-label">Total Laporan</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card-modern">
                <div class="stat-icon-wrapper bg-gradient-yellow">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-value"><?php echo $pendingReports; ?></div>
                <div class="stat-label">Menunggu</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card-modern">
                <div class="stat-icon-wrapper bg-gradient-blue">
                    <i class="fas fa-spinner"></i>
                </div>
                <div class="stat-value"><?php echo $prosesReports; ?></div>
                <div class="stat-label">Diproses</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card-modern">
                <div class="stat-icon-wrapper bg-gradient-green">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-value"><?php echo $selesaiReports; ?></div>
                <div class="stat-label">Selesai</div>
            </div>
        </div>
    </div>

    <!-- Quick Action -->
    <div class="action-card">
        <div class="mb-4">
            <div style="width: 80px; height: 80px; background: #ccfbf1; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto; color: #0f766e; font-size: 2rem;">
                <i class="fas fa-camera"></i>
            </div>
        </div>
        <h3 class="fw-bold mb-3">Temukan Tumpukan Sampah Liar?</h3>
        <p class="text-muted mb-4" style="max-width: 600px; margin: 0 auto;">Bantu kami menjaga lingkungan kampus tetap bersih. Laporkan tumpukan sampah liar yang Anda temukan, dan kami akan segera menindaklanjutinya.</p>
        <a href="lapor.php" class="btn-report-lg text-decoration-none">
            <i class="fas fa-plus-circle"></i> Buat Laporan Baru
        </a>
    </div>

    <!-- Recent Reports -->
    <div class="table-container">
        <div class="section-header">
            <h2 class="section-title">Riwayat Laporan Anda</h2>
            <a href="lapor.php" class="text-teal-600 text-decoration-none fw-bold small">Lihat Semua <i class="fas fa-arrow-right list-inline-item"></i></a>
        </div>

        <?php if (empty($userReports)): ?>
            <div class="text-center py-5">
                <div class="mb-3" style="font-size: 4rem; color: #e2e8f0;">
                    <i class="fas fa-inbox"></i>
                </div>
                <h5 class="text-muted fw-bold">Belum ada laporan</h5>
                <p class="text-muted small">Mulai berkontribusi dengan membuat laporan pertama Anda.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="bg-light">
                        <tr>
                            <th class="border-0 rounded-start ps-4">Foto</th>
                            <th class="border-0">Jenis Sampah</th>
                            <th class="border-0">Lokasi</th>
                            <th class="border-0">Tanggal</th>
                            <th class="border-0">Status</th>
                            <th class="border-0 rounded-end pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 

                        $recentReports = array_slice($userReports, 0, 5);
                        foreach ($recentReports as $r): 
                        ?>
                        <tr>
                            <td class="ps-4">
                                <?php if (!empty($r['gambar'])): ?>
                                <img src="../uploads/<?php echo htmlspecialchars($r['gambar']); ?>" 
                                     class="rounded-3 object-fit-cover shadow-sm" 
                                     width="60" height="60" 
                                     alt="Foto Laporan">
                                <?php else: ?>
                                <div class="rounded-3 d-flex align-items-center justify-content-center bg-light text-muted" style="width: 60px; height: 60px;">
                                    <i class="fas fa-image"></i>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="fw-bold text-dark d-block mb-1">
                                    <?php echo getJenisSampahBadge($r['jenis_sampah'], $r['kategori']); ?>
                                </span>
                                <small class="text-muted">
                                    <i class="fas fa-robot me-1"></i><?php echo isset($r['confidence_score']) ? round($r['confidence_score'] * 100) . '%' : 'N/A'; ?> Akurat
                                </small>
                            </td>
                            <td>
                                <div class="d-flex align-items-center text-muted small">
                                    <i class="fas fa-map-marker-alt me-2 text-danger"></i>
                                    <span class="text-truncate" style="max-width: 150px;">
                                        <?php echo isset($r['lokasi']) ? htmlspecialchars($r['lokasi']) : 'Lokasi tidak tersedia'; ?>
                                        <?php if (isset($r['alamat_lokasi']) && empty($r['lokasi'])) echo htmlspecialchars($r['alamat_lokasi']); ?>
                                    </span>
                                </div>
                            </td>
                            <td class="text-muted small">
                                <?php echo date('d M Y, H:i', strtotime($r['created_at'])); ?>
                            </td>
                            <td>
                                <?php
                                $statusClass = '';
                                $statusIcon = '';
                                switch($r['status']) {
                                    case 'pending': 
                                        $statusClass = 'badge-warning'; 
                                        $statusIcon = 'fa-clock';
                                        break;
                                    case 'diproses': 
                                        $statusClass = 'badge-info'; 
                                        $statusIcon = 'fa-spinner fa-spin';
                                        break;
                                    case 'selesai': 
                                        $statusClass = 'badge-success'; 
                                        $statusIcon = 'fa-check-circle';
                                        break;
                                    case 'ditolak': 
                                        $statusClass = 'badge-danger'; 
                                        $statusIcon = 'fa-times-circle';
                                        break;
                                }
                                ?>
                                <span class="badge <?php echo $statusClass; ?> d-inline-flex align-items-center gap-1">
                                    <i class="fas <?php echo $statusIcon; ?>"></i>
                                    <?php echo ucfirst($r['status']); ?>
                                </span>
                            </td>
                            <td class="pe-4">
                                <a href="detail.php?id=<?php echo $r['id']; ?>" class="btn btn-sm btn-outline-info rounded-pill px-3">
                                    Detail
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
