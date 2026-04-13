<?php
require_once '../config/config.php';
require_once '../config/Database.php';
require_once '../classes/Report.php';
require_once '../assets/js/jenis-sampah.php';

checkLogin();
checkAdmin();

$currentPage = 'laporan'; // For navbar active state

$database = new Database();
$db = $database->getConnection();
$report = new Report($db);


if (isset($_POST['update_status'])) {
    $reportId = $_POST['report_id'];
    $newStatus = $_POST['status'];
    if ($report->updateStatus($reportId, $newStatus)) {
        $_SESSION['success'] = 'Status berhasil diupdate!';
        header('Location: laporan.php' . (isset($_GET['filter']) ? '?filter='.$_GET['filter'] : ''));
        exit;
    } else {
        $_SESSION['error'] = 'Gagal mengupdate status.';
    }
}


if (isset($_POST['update_report'])) {
    $reportId = $_POST['report_id'];
    $existingReport = $report->getById($reportId);
    
    if ($existingReport) {
        $report->kategori = $_POST['kategori'];
        $report->jenis_sampah = $_POST['jenis_sampah'];
        $report->deskripsi = $_POST['deskripsi'];
        $report->lokasi_latitude = $_POST['lokasi_latitude'];
        $report->lokasi_longitude = $_POST['lokasi_longitude'];
        $report->alamat_lokasi = $_POST['alamat_lokasi'];
        $report->confidence = $_POST['confidence'];
        $report->ai_prediction = $_POST['ai_prediction'];
        $report->is_corrected = isset($_POST['is_corrected']) ? 1 : 0;
        $report->correction_note = $_POST['correction_note'];
        $report->gambar = $existingReport['gambar']; // Keep existing image
        
        if ($report->update($reportId)) {
            $_SESSION['success'] = 'Laporan berhasil diupdate!';
            header('Location: laporan.php' . (isset($_GET['filter']) ? '?filter='.$_GET['filter'] : ''));
            exit;
        } else {
            $_SESSION['error'] = 'Gagal mengupdate laporan.';
        }
    }
}


if (isset($_GET['delete'])) {
    $deleteId = filter_input(INPUT_GET, 'delete', FILTER_VALIDATE_INT);
    if ($deleteId && $report->delete($deleteId)) {
        $_SESSION['success'] = 'Laporan berhasil dihapus!';
        header('Location: laporan.php' . (isset($_GET['filter']) ? '?filter='.$_GET['filter'] : ''));
        exit;
    } else {
        $_SESSION['error'] = 'Gagal menghapus laporan.';
    }
}


if (isset($_POST['bulk_delete_ids'])) {
    $ids = explode(',', $_POST['bulk_delete_ids']);
    $successCount = 0;
    foreach ($ids as $id) {
        if ($report->delete(trim($id))) {
            $successCount++;
        }
    }
    
    if ($successCount > 0) {
        $_SESSION['success'] = $successCount . ' laporan berhasil dihapus!';
    } else {
        $_SESSION['error'] = 'Gagal menghapus laporan yang dipilih.';
    }
    header('Location: laporan.php' . (isset($_GET['filter']) ? '?filter='.$_GET['filter'] : ''));
    exit;
}


$allReportsData = $report->getAllReports();



foreach ($allReportsData as &$reportData) {

    if (!isset($reportData['is_corrected']) || $reportData['is_corrected'] === null) {
        $reportData['is_corrected'] = 0;
    } else {

        $isCorrected = (int)$reportData['is_corrected'];
        



        if ($isCorrected === 1) {
            $hasCorrectionNote = !empty($reportData['correction_note']);
            $isDifferentFromAI = !empty($reportData['ai_prediction']) && 
                                 $reportData['kategori'] !== $reportData['ai_prediction'];
            

            if (!$hasCorrectionNote && !$isDifferentFromAI) {
                $isCorrected = 0;
            }
        }
        
        $reportData['is_corrected'] = $isCorrected;
    }
}
unset($reportData); // Break reference


$totalReports = count($allReportsData);
$pendingCount = count(array_filter($allReportsData, fn($r) => $r['status'] == 'pending'));
$diprosesCount = count(array_filter($allReportsData, fn($r) => $r['status'] == 'diproses'));
$selesaiCount = count(array_filter($allReportsData, fn($r) => $r['status'] == 'selesai'));
$ditolakCount = count(array_filter($allReportsData, fn($r) => $r['status'] == 'ditolak'));
$organikCount = count(array_filter($allReportsData, fn($r) => $r['kategori'] == 'organik'));
$anorganikCount = count(array_filter($allReportsData, fn($r) => $r['kategori'] == 'anorganik'));
$b3Count = count(array_filter($allReportsData, fn($r) => $r['kategori'] == 'b3'));


$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <meta name="theme-color" content="#14b8a6">
    <title>Kelola Laporan - Admin</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="../favicon.svg">
    <link rel="alternate icon" href="../favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="../favicon.svg">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/vue-components.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        /* Mobile Report Card */
        .report-card-mobile {
            background: white;
            border-radius: 16px;
            padding: 16px;
            margin-bottom: 16px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            position: relative;
        }
        
        .report-card-mobile:active {
            transform: scale(0.98);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }
        
        .report-image-mobile {
            width: 100%;
            height: 200px;
            border-radius: 12px;
            object-fit: cover;
            margin-bottom: 12px;
        }
        
        .report-header-mobile {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }
        
        .report-id-mobile {
            font-size: 0.8125rem;
            font-weight: 700;
            color: #6b7280;
        }
        
        .report-kategori-badge-mobile {
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
        }
        
        .report-info-mobile {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 12px;
        }
        
        .report-info-row-mobile {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.875rem;
            color: #6b7280;
        }
        
        .report-info-row-mobile i {
            width: 20px;
            color: #14b8a6;
        }
        
        .report-actions-mobile {
            display: flex;
            gap: 8px;
            margin-top: 12px;
        }
        
        .report-btn-mobile {
            flex: 1;
            padding: 10px;
            border-radius: 10px;
            border: none;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        
        .report-btn-mobile:active {
            transform: scale(0.95);
        }
        
        .filter-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 20px;
            border: 2px solid #e5e7eb;
            background: white;
            color: #6b7280;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin: 4px;
        }
        
        .filter-chip:active,
        .filter-chip.active {
            border-color: #14b8a6;
            background: #14b8a6;
            color: white;
            transform: scale(0.95);
        }
        
        /* Table row clickable style */
        .table tbody tr {
            transition: all 0.2s ease;
        }
        .table tbody tr:hover {
            background-color: #f8f9fa !important;
            transform: scale(1.01);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .search-box-mobile {
            position: relative;
            margin-bottom: 16px;
        }
        
        .search-box-mobile input {
            width: 100%;
            padding: 14px 48px 14px 48px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .search-box-mobile input:focus {
            border-color: #14b8a6;
            outline: none;
            box-shadow: 0 0 0 3px rgba(20, 184, 166, 0.1);
        }
        
        .search-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
        }
        
        .clear-icon {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            cursor: pointer;
            background: none;
            border: none;
            font-size: 1.2rem;
        }
        
        @media (min-width: 768px) {
            .report-card-mobile {
                display: none;
            }
        }
        
        @media (max-width: 767px) {
            .desktop-table {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <?php include 'includes/navbar.php'; ?>

    <!-- Content -->
    <div class="container-fluid mt-4 mb-5" id="laporanApp">
        <div class="fade-in">
            <!-- Header -->
            <div class="row align-items-center mb-4">
                <div class="col-md-8">
                    <h2 class="mb-2" style="color: #14b8a6; font-weight: 700; font-size: clamp(1.5rem, 5vw, 1.875rem);">
                <i class="fas fa-list"></i> Kelola Laporan
            </h2>
                    <p class="text-muted mb-0 d-none d-md-block">
                        <i class="fas fa-database me-2"></i>Manajemen laporan sampah dari user
                    </p>
                </div>
                <div class="col-md-4 text-end d-none d-md-block">
                    <button class="btn btn-primary" onclick="location.reload()">
                        <i class="fas fa-sync-alt me-1"></i> Refresh
                    </button>
                </div>
            </div>

            <!-- Statistics Cards (Mobile & Desktop) -->
            <div class="row mb-4">
                <div class="col-6 col-md-4 col-lg-2 mb-3">
                    <div class="stat-card mobile-card" @click="setFilter('all')" style="cursor: pointer;">
                        <div class="stat-icon"><i class="fas fa-file-alt"></i></div>
                        <animated-counter :target-value="<?php echo $totalReports; ?>" :duration="1500"></animated-counter>
                        <p class="stat-label">Total</p>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-lg-2 mb-3">
                    <div class="stat-card stat-card-warning mobile-card" @click="setFilter('pending')" style="cursor: pointer;">
                        <div class="stat-icon"><i class="fas fa-clock"></i></div>
                        <animated-counter :target-value="<?php echo $pendingCount; ?>" :duration="1600"></animated-counter>
                        <p class="stat-label">Pending</p>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-lg-2 mb-3">
                    <div class="stat-card stat-card-info mobile-card" @click="setFilter('diproses')" style="cursor: pointer;">
                        <div class="stat-icon"><i class="fas fa-spinner"></i></div>
                        <animated-counter :target-value="<?php echo $diprosesCount; ?>" :duration="1700"></animated-counter>
                        <p class="stat-label">Diproses</p>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-lg-2 mb-3">
                    <div class="stat-card stat-card-success mobile-card" @click="setFilter('selesai')" style="cursor: pointer;">
                        <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                        <animated-counter :target-value="<?php echo $selesaiCount; ?>" :duration="1800"></animated-counter>
                        <p class="stat-label">Selesai</p>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-lg-2 mb-3">
                    <div class="stat-card mobile-card" @click="setFilter('ditolak')" style="cursor: pointer; background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
                        <div class="stat-icon"><i class="fas fa-ban" style="color: white;"></i></div>
                        <animated-counter :target-value="<?php echo $ditolakCount; ?>" :duration="1900"></animated-counter>
                        <p class="stat-label" style="color: white;">Ditolak</p>
                        <div class="stat-card-glow" style="background: rgba(239, 68, 68, 0.3);"></div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-lg-2 mb-3">
                    <div class="stat-card stat-card-success mobile-card" @click="setFilter('organik')" style="cursor: pointer;">
                        <div class="stat-icon"><i class="fas fa-leaf"></i></div>
                        <animated-counter :target-value="<?php echo $organikCount; ?>" :duration="2000"></animated-counter>
                        <p class="stat-label">Organik</p>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-lg-2 mb-3">
                    <div class="stat-card stat-card-info mobile-card" @click="setFilter('anorganik')" style="cursor: pointer;">
                        <div class="stat-icon"><i class="fas fa-recycle"></i></div>
                        <animated-counter :target-value="<?php echo $anorganikCount; ?>" :duration="2000"></animated-counter>
                        <p class="stat-label">Anorganik</p>
                    </div>
                </div>
            </div>

            <!-- Search & Filter -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-filter"></i> Filter & Pencarian
                </div>
                <div class="card-body">
                        <!-- Search Box -->
                    <div class="search-box-mobile">
                        <i class="fas fa-search search-icon"></i>
                        <input 
                            type="text" 
                            v-model="searchQuery"
                            @input="filterReports"
                            placeholder="Cari ID, nama, username, atau lokasi..."
                        >
                        <button v-if="searchQuery" @click="clearSearch" class="clear-icon">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                    
                    <!-- Filter Chips -->
                    <div class="d-flex flex-wrap mb-3">
                        <div 
                            class="filter-chip" 
                            :class="{ active: activeFilter === 'all' }"
                            @click="setFilter('all')"
                        >
                            <i class="fas fa-th"></i> Semua
                        </div>
                        <div 
                            class="filter-chip" 
                            :class="{ active: activeFilter === 'pending' }"
                            @click="setFilter('pending')"
                        >
                            <i class="fas fa-clock"></i> Pending
                        </div>
                        <div 
                            class="filter-chip" 
                            :class="{ active: activeFilter === 'diproses' }"
                            @click="setFilter('diproses')"
                        >
                            <i class="fas fa-spinner"></i> Diproses
                        </div>
                        <div 
                            class="filter-chip" 
                            :class="{ active: activeFilter === 'selesai' }"
                            @click="setFilter('selesai')"
                        >
                            <i class="fas fa-check-circle"></i> Selesai
                        </div>
                        <div 
                            class="filter-chip" 
                            :class="{ active: activeFilter === 'ditolak' }"
                            @click="setFilter('ditolak')"
                            style="border-color: #ef4444;"
                        >
                            <i class="fas fa-ban"></i> Ditolak
                        </div>
                        <div 
                            class="filter-chip" 
                            :class="{ active: activeFilter === 'organik' }"
                            @click="setFilter('organik')"
                        >
                            <i class="fas fa-leaf"></i> Organik
                        </div>
                        <div 
                            class="filter-chip" 
                            :class="{ active: activeFilter === 'anorganik' }"
                            @click="setFilter('anorganik')"
                        >
                            <i class="fas fa-recycle"></i> Anorganik
                        </div>
                        <div 
                            class="filter-chip" 
                            :class="{ active: activeFilter === 'b3' }"
                            @click="setFilter('b3')"
                        >
                            <i class="fas fa-radiation"></i> B3
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">
                            <i class="fas fa-info-circle"></i> Menampilkan {{ filteredReports.length }} dari {{ reportsData.length }} laporan
                        </small>
                        <button v-if="activeFilter !== 'all' || searchQuery" @click="resetFilters" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-redo"></i> Reset Filter
                        </button>
                    </div>
                </div>
            </div>

            <!-- Mobile Card View -->
            <div class="d-md-none">
                <div v-if="filteredReports.length > 0">
                    <div v-for="report in filteredReports" :key="report.id" class="report-card-mobile">
                        <div class="report-header-mobile">
                            <div class="report-id-mobile">
                                <i class="fas fa-hashtag"></i> {{ report.id }}
                            </div>
                            <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 0.25rem;">
                                <span class="badge" :class="'badge-' + report.kategori" style="padding: 6px 12px; font-size: 0.75rem;">
                                    {{ report.kategori.toUpperCase() }}
                                </span>
                                <!-- Only show "Dikoreksi" label if is_corrected is actually 1 (strict check) -->
                                <span v-if="Number(report.is_corrected) === 1" style="font-size: 0.65rem; color: rgb(245, 158, 11); font-weight: 600;">
                                    <i class="fas fa-edit"></i> Dikoreksi
                                </span>
                            </div>
                        </div>
                        
                        <img 
                            :src="getImageUrl(report.gambar)" 
                            alt="Sampah" 
                            class="report-image-mobile"
                            @error="$event.target.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgdmlld0JveD0iMCAwIDEwMCAxMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHJlY3Qgd2lkdGg9IjEwMCIgaGVpZ2h0PSIxMDAiIGZpbGw9IiNGM0Y0RjYiLz48cGF0aCBkPSJNNDAgNDBINjBWNjBINDBWNDBaTTQwIDcwSDYwVjkwSDQwVjcwWiIgZmlsbD0iIzlDQTNBRiIvPjwvc3ZnPg=='; $event.target.onerror=null;"
                            @click="showImageModal(getImageUrl(report.gambar))"
                        >
                        
                        <div class="report-info-mobile">
                            <div class="report-info-row-mobile">
                                <i class="fas fa-user"></i>
                                <span><strong>{{ report.user_nama }}</strong> (@{{ report.username }})</span>
                            </div>
                            <div class="report-info-row-mobile">
                                <i class="fas fa-map-marker-alt"></i>
                                <span>{{ report.alamat_lokasi.substring(0, 40) }}...</span>
                            </div>
                            <div class="report-info-row-mobile">
                                <i class="fas fa-calendar-alt"></i>
                                <span>{{ formatDate(report.created_at) }}</span>
                            </div>
                            <div class="report-info-row-mobile">
                                <i class="fas fa-brain"></i>
                                <span>AI Confidence: {{ report.confidence }}%</span>
                            </div>
                            <div class="report-info-row-mobile">
                                <i class="fas fa-info-circle"></i>
                                <span class="badge" :class="'badge-' + report.status" style="font-size: 0.813rem;">
                                    {{ report.status === 'pending' ? '⏰ Pending' : 
                                       report.status === 'diproses' ? '🔄 Diproses' : 
                                       report.status === 'ditolak' ? '❌ Ditolak' :
                                       '✅ Selesai' }}
                                </span>
                            </div>
                        </div>
                        
                        <div class="report-actions-mobile">
                            <a :href="'detail.php?id=' + report.id" class="report-btn-mobile" style="background: #14b8a6; color: white;">
                                <i class="fas fa-eye"></i> Detail
                            </a>
                            <button @click="showEditModal(report)" class="report-btn-mobile" style="background: #f59e0b; color: white;">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button @click="showStatusModal(report)" class="report-btn-mobile" style="background: #3b82f6; color: white;">
                                <i class="fas fa-tasks"></i> Status
                            </button>
                            <button @click="confirmDelete(report.id)" class="report-btn-mobile" style="background: #ef4444; color: white;">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div v-else class="text-center py-5">
                    <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                    <h5 class="text-muted">Tidak ada laporan</h5>
                    <p class="text-muted">Coba ubah filter atau kata kunci pencarian</p>
                </div>
            </div>

            <!-- Desktop Table View -->
            <div class="card desktop-table">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>
                        <i class="fas fa-table"></i> Daftar Laporan
                    </span>
                    <div>
                        <button v-if="selectedReports.length > 0" @click="confirmBulkDelete" class="btn btn-danger btn-sm me-2 fade-in">
                            <i class="fas fa-trash"></i> Hapus ({{ selectedReports.length }})
                        </button>
                        <span class="badge bg-primary">{{ filteredReports.length }} laporan</span>
                    </div>
                </div>
                <div class="card-body">
                    <div v-if="filteredReports.length > 0" class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th width="40" class="text-center">
                                        <input type="checkbox" class="form-check-input" v-model="selectAll" @change="toggleSelectAll">
                                    </th>
                                    <th class="text-center">Gambar</th>
                                    <th>User</th>
                                    <th class="text-center">Kategori</th>
                                    <th>Jenis Sampah</th>
                                    <th>Lokasi</th>
                                    <th class="text-center">Confidence</th>
                                    <th class="text-center">Status</th>
                                    <th>Tanggal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="report in paginatedReports" :key="report.id" @click="goToDetail(report.id)" style="cursor: pointer;" :class="{'table-active': selectedReports.includes(report.id)}">
                                    <td class="text-center" @click.stop>
                                        <input type="checkbox" class="form-check-input" :value="report.id" v-model="selectedReports">
                                    </td>
                                    <td class="text-center">
                                        <img 
                                            :src="getImageUrl(report.gambar)" 
                                            alt="Sampah" 
                                            class="img-thumbnail" 
                                            style="width: 60px; height: 60px; object-fit: cover;" 
                                            @error="$event.target.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIGZpbGw9IiNGM0Y0RjYiLz48cGF0aCBkPSJNMjggMjJIMzJWMjZIMjhWMjJaTTI4IDMwSDMyVjM0SDI4VjMwWk0yOCAzOEgzMlY0MkgyOFYzOFoiIGZpbGw9IiM5Q0EzQUYiLz48L3N2Zz4='; $event.target.onerror=null;"
                                            @click.stop
                                            @click="showImageModal(getImageUrl(report.gambar))"
                                        >
                                    </td>
                                    <td>
                                        <div v-if="report.user_nama">
                                            <strong>{{ report.user_nama }}</strong><br>
                                            <small class="text-muted">@{{ report.username }}</small>
                                        </div>
                                        <div v-else>
                                            <strong>{{ report.guest_name || 'Guest User' }}</strong><br>
                                            <small class="text-muted"><i class="fab fa-whatsapp"></i> {{ report.whatsapp_number }}</small>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge" :class="'badge-' + report.kategori">
                                            {{ report.kategori === 'organik' ? 'ORGANIK' : 
                                               report.kategori === 'anorganik' ? 'ANORGANIK' : 'B3' }}
                                        </span>
                                        <br v-if="Number(report.is_corrected) === 1">
                                        <small v-if="Number(report.is_corrected) === 1" class="text-warning">
                                            <i class="fas fa-edit"></i> Dikoreksi
                                        </small>
                                    </td>
                                    <td>{{ getDisplayJenis(report) }}</td>
                                    <td>
                                        <small>{{ report.alamat_lokasi.substring(0, 40) }}{{ report.alamat_lokasi.length > 40 ? '...' : '' }}</small>
                                    </td>
                                    <td class="text-center">{{ report.confidence }}%</td>
                                    <td class="text-center">
                                        <span class="badge" :class="'badge-' + report.status">
                                            {{ report.status === 'pending' ? 'PENDING' : 
                                               report.status === 'diproses' ? 'DIPROSES' : 
                                               report.status === 'ditolak' ? 'DITOLAK' : 'SELESAI' }}
                                        </span>
                                    </td>
                                    <td>
                                        <small>{{ formatDateShort(report.created_at) }}</small><br>
                                        <small class="text-muted">{{ formatTimeShort(report.created_at) }}</small>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        
                        <!-- Simple Pagination -->
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <small class="text-muted">
                                Menampilkan {{ (currentPage - 1) * itemsPerPage + 1 }}-{{ Math.min(currentPage * itemsPerPage, filteredReports.length) }} dari {{ filteredReports.length }} laporan
                            </small>
                            <nav aria-label="Page navigation" v-if="totalPages > 1">
                                <ul class="pagination pagination-sm mb-0">
                                    <li class="page-item" :class="{ disabled: currentPage === 1 }">
                                        <button class="page-link" @click="prevPage" aria-label="Previous">
                                            <span aria-hidden="true">&laquo;</span>
                                        </button>
                                    </li>
                                    <li class="page-item disabled">
                                        <span class="page-link">Halaman {{ currentPage }} dari {{ totalPages }}</span>
                                    </li>
                                    <li class="page-item" :class="{ disabled: currentPage === totalPages }">
                                        <button class="page-link" @click="nextPage" aria-label="Next">
                                            <span aria-hidden="true">&raquo;</span>
                                        </button>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                        </div>
                    <div v-else class="text-center py-5">
                            <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                        <h5 class="text-muted">Tidak ada laporan</h5>
                        <p class="text-muted">Coba ubah filter atau kata kunci pencarian</p>
                        </div>
                </div>
            </div>
            
            <!-- Pagination handled by Modern Table Component -->
            
            <!-- Toast Notifications -->
            <toast-notification ref="toast"></toast-notification>
        </div>
    </div>

    <!-- Image Modal -->
    <div class="modal fade" id="imageModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Preview Gambar</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="modalImage" src="" class="img-fluid rounded" alt="Preview">
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Report Modal -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title">
                        <i class="fas fa-edit"></i> Edit Laporan
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editForm">
                    <div class="modal-body">
                        <input type="hidden" name="report_id" id="editReportId">
                        <input type="hidden" name="update_report" value="1">
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> Edit laporan untuk memastikan data sampah akurat
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                <i class="fas fa-layer-group"></i> Kategori Sampah
                            </label>
                            <select name="kategori" id="editKategori" class="form-select" required>
                                <option value="organik">🌿 Organik</option>
                                <option value="anorganik">♻️ Anorganik</option>
                                <option value="b3">☢️ B3 (Bahan Berbahaya dan Beracun)</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                <i class="fas fa-tags"></i> Jenis Sampah
                            </label>
                            <select name="jenis_sampah" id="editJenisSampah" class="form-select" required>
                                <option value="plastik">Plastik</option>
                                <option value="daun">Daun</option>
                                <option value="kertas">Kertas</option>
                                <option value="elektronik">Elektronik</option>
                                <option value="logam">Logam</option>
                                <option value="baterai">Baterai</option>
                                <option value="kain">Kain</option>
                                <option value="kaca">Kaca</option>
                                <option value="kayu">Kayu</option>
                                <option value="makanan">Makanan</option>
                                <option value="lainnya">Lainnya</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                <i class="fas fa-comment"></i> Deskripsi
                            </label>
                            <textarea name="deskripsi" id="editDeskripsi" class="form-control" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                <i class="fas fa-map-marker-alt"></i> Alamat Lokasi
                            </label>
                            <textarea name="alamat_lokasi" id="editAlamatLokasi" class="form-control" rows="2" required></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">
                                    <i class="fas fa-map-pin"></i> Latitude
                                </label>
                                <input type="number" step="any" name="lokasi_latitude" id="editLatitude" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">
                                    <i class="fas fa-map-pin"></i> Longitude
                                </label>
                                <input type="number" step="any" name="lokasi_longitude" id="editLongitude" class="form-control" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">
                                    <i class="fas fa-brain"></i> AI Prediction
                                </label>
                                <select name="ai_prediction" id="editAIPrediction" class="form-select">
                                    <option value="">-</option>
                                    <option value="organik">Organik</option>
                                    <option value="anorganik">Anorganik</option>
                                    <option value="b3">B3</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">
                                    <i class="fas fa-percentage"></i> Confidence (%)
                                </label>
                                <input type="number" step="0.01" name="confidence" id="editConfidence" class="form-control" min="0" max="100">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="is_corrected" id="editIsCorrected" value="1">
                                <label class="form-check-label fw-bold" for="editIsCorrected">
                                    <i class="fas fa-check-circle"></i> Laporan ini sudah dikoreksi manual
                                </label>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                <i class="fas fa-sticky-note"></i> Catatan Koreksi
                            </label>
                            <textarea name="correction_note" id="editCorrectionNote" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i> Batal
                        </button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-save"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Status Update Modal -->
    <div class="modal fade" id="statusModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-tasks"></i> Update Status Laporan
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="statusForm">
                    <div class="modal-body">
                        <input type="hidden" name="report_id" id="statusReportId">
                        <input type="hidden" name="update_status" value="1">
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Laporan #<span id="statusReportNumber"></span></label>
                            <p class="text-muted" id="statusReportUser"></p>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                <i class="fas fa-tasks"></i> Pilih Status Baru
                            </label>
                            <select name="status" id="statusSelect" class="form-select form-select-lg" required>
                                <option value="pending">⏰ Pending - Belum Diproses</option>
                                <option value="diproses">🔄 Diproses - Sedang Ditangani</option>
                                <option value="selesai">✅ Selesai - Sudah Selesai</option>
                                <option value="ditolak">❌ Ditolak - Tidak Sesuai</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i> Batal
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>&copy; 2024 Aplikasi Pelaporan Sampah dengan AI | Powered by Teachable Machine</p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/navbar.js"></script>
    
    <!-- Vue 3 -->
    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
    <script src="../assets/js/vue-components.js"></script>
    
    <!-- Initialize Vue App -->
    <script>

        console.log('🔍 DEBUG Image URLs:');
        console.log('BASE_URL:', '<?php echo BASE_URL; ?>');
        console.log('UPLOAD_URL:', '<?php echo UPLOAD_URL; ?>');
        <?php if (!empty($allReportsData) && !empty($allReportsData[0]['gambar'])): ?>
        console.log('Sample Image from DB:', '<?php echo htmlspecialchars($allReportsData[0]['gambar'], ENT_QUOTES); ?>');
        console.log('Sample Full URL:', '<?php echo !empty($allReportsData[0]['gambar']) ? getImageUrl($allReportsData[0]['gambar']) : ''; ?>');
        <?php endif; ?>
        
        const { createApp } = Vue;
        
        const app = createApp({
            components: {
                'animated-counter': VueComponents.AnimatedCounter,
                'toast-notification': VueComponents.ToastNotification
            },
            data() {
                return {
                    reportsData: <?php echo json_encode(array_values($allReportsData)); ?>,
                    searchQuery: '',
                    activeFilter: '<?php echo $filter; ?>',
                    sortKey: 'created_at',
                    sortOrder: 'desc',
                    selectedReports: [],
                    selectAll: false,
                    currentPage: 1,
                    itemsPerPage: 10
                }
            },
            computed: {
                filteredReports() {
                    let filtered = this.reportsData;
                    

                    if (this.searchQuery) {
                        const query = this.searchQuery.toLowerCase();
                        filtered = filtered.filter(report => {
                            return String(report.id).includes(query) ||
                                   report.user_nama.toLowerCase().includes(query) ||
                                   report.username.toLowerCase().includes(query) ||
                                   report.alamat_lokasi.toLowerCase().includes(query);
                        });
                    }
                    

                    if (this.activeFilter !== 'all') {
                        filtered = filtered.filter(report => {
                            if (['organik', 'anorganik', 'b3'].includes(this.activeFilter)) {
                                return report.kategori === this.activeFilter;
            } else {
                                return report.status === this.activeFilter;
                            }
                        });
                    }
                    
                    return filtered;
                },
                sortedReports() {
                    return [...this.filteredReports].sort((a, b) => {
                        let aVal = a[this.sortKey];
                        let bVal = b[this.sortKey];
                        
                        if (typeof aVal === 'string') {
                            aVal = aVal.toLowerCase();
                            bVal = bVal.toLowerCase();
                        }
                        
                        if (this.sortOrder === 'asc') {
                            return aVal > bVal ? 1 : -1;
                        } else {
                            return aVal < bVal ? 1 : -1;
                        }
                    });
                },
                paginatedReports() {
                    const start = (this.currentPage - 1) * this.itemsPerPage;
                    const end = start + this.itemsPerPage;
                    return this.sortedReports.slice(start, end);
                },
                totalPages() {
                    return Math.ceil(this.filteredReports.length / this.itemsPerPage);
                }
            },
            mounted() {

                console.log('📊 Total Reports Loaded:', this.reportsData.length);
                console.log('🔍 Active Filter:', this.activeFilter);
                console.log('📋 Filtered Reports:', this.filteredReports.length);
                

                <?php if (isset($_SESSION['success'])): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: '<?php echo $_SESSION['success']; unset($_SESSION['success']); ?>',
                    showConfirmButton: false,
                    timer: 2000,
                    timerProgressBar: true
                });
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal!',
                    text: '<?php echo $_SESSION['error']; unset($_SESSION['error']); ?>',
                    showConfirmButton: true,
                    confirmButtonColor: '#ef4444'
                });
                <?php endif; ?>
            },
            methods: {

                getImageUrl(filename) {
                    if (!filename) {
                        console.warn('⚠️ getImageUrl: filename is empty');
                        return '';
                    }

                    const baseUrl = '<?php 
                        $uploadUrl = rtrim(UPLOAD_URL, '/');

                        if (substr($uploadUrl, -1) !== '/') {
                            $uploadUrl .= '/';
                        }
                        echo $uploadUrl; 
                    ?>';
                    const cleanFilename = filename.replace(/^\//, '').replace(/^\.\//, ''); // Hapus leading slash atau ./

                    let fullUrl = baseUrl + cleanFilename;

                    fullUrl = fullUrl.replace(/([^:])\/\//g, '$1/');
                    

                    if (this.reportsData && this.reportsData.length > 0 && filename === this.reportsData[0].gambar) {
                        console.log('🔍 getImageUrl Debug:');
                        console.log('  - filename:', filename);
                        console.log('  - baseUrl:', baseUrl);
                        console.log('  - fullUrl:', fullUrl);
                    }
                    
                    return fullUrl;
                },
                
                

                setFilter(filter) {
                    this.activeFilter = filter;
                    this.searchQuery = '';
                },
                resetFilters() {
                    this.activeFilter = 'all';
                    this.searchQuery = '';
                },
                clearSearch() {
                    this.searchQuery = '';
                    this.currentPage = 1;
                },
                filterReports() {
                    this.currentPage = 1;
                },
                nextPage() {
                    if (this.currentPage < this.totalPages) {
                        this.currentPage++;
                    }
                },
                prevPage() {
                    if (this.currentPage > 1) {
                        this.currentPage--;
                    }
                },
                

                sortBy(key) {
                    if (this.sortKey === key) {
                        this.sortOrder = this.sortOrder === 'asc' ? 'desc' : 'asc';
                    } else {
                        this.sortKey = key;
                        this.sortOrder = 'asc';
                    }
                },
                

                formatDate(dateString) {
                    const date = new Date(dateString);
                    return date.toLocaleDateString('id-ID', {
                        day: '2-digit',
                        month: '2-digit',
                        year: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                },
                formatDateShort(dateString) {
                    const date = new Date(dateString);
                    return date.toLocaleDateString('id-ID', {
                        day: '2-digit',
                        month: 'short',
                        year: 'numeric'
                    });
                },
                formatTimeShort(dateString) {
                    const date = new Date(dateString);
                    return date.toLocaleTimeString('id-ID', {
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                },
                

                showImageModal(imageSrc) {
                    document.getElementById('modalImage').src = imageSrc;
                    const modal = new bootstrap.Modal(document.getElementById('imageModal'));
                    modal.show();
                },
                showEditModal(report) {
                    document.getElementById('editReportId').value = report.id;
                    document.getElementById('editKategori').value = report.kategori;
                    document.getElementById('editJenisSampah').value = report.jenis_sampah;
                    document.getElementById('editDeskripsi').value = report.deskripsi || '';
                    document.getElementById('editAlamatLokasi').value = report.alamat_lokasi;
                    document.getElementById('editLatitude').value = report.lokasi_latitude;
                    document.getElementById('editLongitude').value = report.lokasi_longitude;
                    document.getElementById('editAIPrediction').value = report.ai_prediction || '';
                    document.getElementById('editConfidence').value = report.confidence || '';
                    document.getElementById('editIsCorrected').checked = report.is_corrected == 1;
                    document.getElementById('editCorrectionNote').value = report.correction_note || '';
                    const modal = new bootstrap.Modal(document.getElementById('editModal'));
                    modal.show();
                },
                showStatusModal(report) {
                    document.getElementById('statusReportId').value = report.id;
                    document.getElementById('statusReportNumber').textContent = report.id;
                    document.getElementById('statusReportUser').textContent = report.user_nama + ' (@' + report.username + ')';
                    document.getElementById('statusSelect').value = report.status;
                    const modal = new bootstrap.Modal(document.getElementById('statusModal'));
                    modal.show();
                },
                confirmDelete(id) {
                    if (confirm('Apakah Anda yakin ingin menghapus laporan ini?')) {
                        window.location.href = '?delete=' + id + (this.activeFilter !== 'all' ? '&filter=' + this.activeFilter : '');
                    }
                },
                goToDetail(reportId) {
                    window.location.href = 'detail.php?id=' + reportId;
                },
                toggleSelectAll() {
                    if (this.selectAll) {
                        this.selectedReports = this.filteredReports.map(r => r.id);
                    } else {
                        this.selectedReports = [];
                    }
                },
                confirmBulkDelete() {
                    if (this.selectedReports.length === 0) return;
                    
                    Swal.fire({
                        title: 'Hapus ' + this.selectedReports.length + ' Laporan?',
                        text: "Data yang dihapus tidak dapat dikembalikan!",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#ef4444',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Ya, Hapus!',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {

                            const form = document.createElement('form');
                             form.method = 'POST';
                            form.style.display = 'none';
                            
                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = 'bulk_delete_ids';
                            input.value = this.selectedReports.join(',');
                            
                            form.appendChild(input);
                            document.body.appendChild(form);
                            form.submit();
                        }
                    });
                },
                getDisplayJenis(report) {

                    const genericTerms = ['organik', 'anorganik', 'b3', 'sampah', 'lainnya', 'sampah organik', 'sampah anorganik', 'sampah b3'];
                    if (report.jenis_sampah && !genericTerms.includes(report.jenis_sampah.toLowerCase())) {
                        return report.jenis_sampah;
                    }



                    if (report.deskripsi) {
                        const itemMatch = report.deskripsi.match(/Item:\s*([^.]+)/);
                        if (itemMatch && itemMatch[1]) {
                            return itemMatch[1].trim();
                        }
                    }


                    return report.jenis_sampah || report.kategori.toUpperCase();
                }
            },
            watch: {

                selectedReports(newVal) {
                    if (newVal.length === 0) {
                        this.selectAll = false;
                    } else if (newVal.length === this.filteredReports.length && this.filteredReports.length > 0) {
                        this.selectAll = true;
                    } else {
                        this.selectAll = false;
                    }
                }
            }
        });
        
        app.mount('#laporanApp');
    </script>
</body>
</html>
