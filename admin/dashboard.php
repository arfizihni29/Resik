<?php
require_once '../config/config.php';
require_once '../config/Database.php';
require_once '../classes/Report.php';
require_once '../assets/js/jenis-sampah.php';

checkLogin();
checkAdmin();

$currentPage = 'dashboard'; // For navbar active state

$database = new Database();
$db = $database->getConnection();
$report = new Report($db);


$stats = $report->getStatistics();


$allReports = $report->getAllReports();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Pelaporan Sampah</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="../favicon.svg">
    <link rel="alternate icon" href="../favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="../favicon.svg">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/vue-components.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f0fdfa 0%, #ccfbf1 100%);
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
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
            z-index: -1;
            animation: rotateBG 20s linear infinite;
        }

        @keyframes rotateBG {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Card Styles */
        .stat-card {
            border: none;
            border-radius: 16px;
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
            height: 100%;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .stat-card-primary { background: linear-gradient(135deg, #ccfbf1 0%, #99f6e4 100%); color: #0f766e; }
        .stat-card-warning { background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); color: #92400e; }
        .stat-card-info { background: linear-gradient(135deg, #e0f2fe 0%, #bae6fd 100%); color: #075985; }
        .stat-card-success { background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%); color: #166534; }

        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            opacity: 0.8;
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 800;
            line-height: 1.2;
        }

        .stat-label {
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.05em;
            opacity: 0.9;
        }

        /* Header Styles */
        h2 {
            color: #111827;
        }

        .text-muted {
            color: #6b7280 !important;
        }

        /* Vue Counter Animation */
        .counter-value {
            display: inline-block;
        }

        /* Additional enhancements */
        .card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
        }

        .card-header {
            background-color: transparent;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1.25rem 1.5rem;
            font-weight: 700;
            color: #374151;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <?php include 'includes/navbar.php'; ?>

    <!-- Content -->
    <div class="container-fluid mt-4 mb-5" id="dashboardApp">
        <!-- Header -->
        <div class="row align-items-center mb-4 fade-in">
            <div class="col-md-8">
                <h2 class="mb-2" style="font-weight: 800; letter-spacing: -0.025em;">
                    <i class="fas fa-chart-line text-teal-600 me-2" style="color: #0d9488;"></i> 
                    Dashboard Admin
                </h2>
                <div style="display: flex; flex-direction: column; gap: 0.35rem;">
                    <p class="text-muted mb-0" style="font-size: 0.95rem;">
                        <i class="fas fa-calendar-alt me-2"></i>
                        <?php 
                            $months_id = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                            $days_id = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
                            $day = $days_id[date('w')];
                            $date = date('d');
                            $month = $months_id[date('n')];
                            $year = date('Y');
                            echo "$day, $date $month $year";
                        ?>
                    </p>
                    <p class="text-muted mb-0" style="font-size: 0.9rem;">
                        <i class="fas fa-clock me-2"></i>
                        <span id="current-time-admin"><?php echo date('H:i:s'); ?></span> WIB
                    </p>
                    <?php if (isset($_SESSION['last_login'])): ?>
                    <p class="text-muted mb-0" style="font-size: 0.85rem; opacity: 0.8;">
                        <i class="fas fa-sign-in-alt me-2"></i>
                        Login terakhir: <?php echo date('d/m/Y H:i:s', strtotime($_SESSION['last_login'])); ?> WIB
                    </p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-4 text-end">
                <button class="btn btn-primary shadow-sm" style="background-color: #0d9488; border-color: #0d9488;" onclick="location.reload()">
                    <i class="fas fa-sync-alt me-1"></i> Refresh Data
                </button>
            </div>
        </div>

        <!-- Statistics Cards - Direct Render -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-3" style="animation-delay: 0.1s;">
                <div class="stat-card stat-card-primary visible">
                    <div class="stat-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div class="stat-value">
                        <animated-counter :target-value="<?php echo $stats['total']; ?>" :duration="2000"></animated-counter>
                    </div>
                    <p class="stat-label">Total Laporan</p>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-3" style="animation-delay: 0.2s;">
                <div class="stat-card stat-card-warning visible">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-value">
                        <animated-counter :target-value="<?php echo $stats['pending']; ?>" :duration="2200"></animated-counter>
                    </div>
                    <p class="stat-label">Pending</p>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-3" style="animation-delay: 0.3s;">
                <div class="stat-card stat-card-info visible">
                    <div class="stat-icon">
                        <i class="fas fa-spinner"></i>
                    </div>
                    <div class="stat-value">
                        <animated-counter :target-value="<?php echo $stats['diproses']; ?>" :duration="2400"></animated-counter>
                    </div>
                    <p class="stat-label">Diproses</p>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-3" style="animation-delay: 0.4s;">
                <div class="stat-card stat-card-success visible">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-value">
                        <animated-counter :target-value="<?php echo $stats['selesai']; ?>" :duration="2600"></animated-counter>
                    </div>
                    <p class="stat-label">Selesai</p>
                </div>
            </div>
        </div>

        <!-- Category Statistics -->
        <div class="row mb-4">
            <div class="col-md-4 mb-3 fade-in" style="animation-delay: 0.5s;">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="mb-3">
                            <i class="fas fa-leaf fa-4x" style="color: #14b8a6;"></i>
                        </div>
                        <div class="h2 fw-bold text-dark">
                            <animated-counter :target-value="<?php echo $stats['organik']; ?>" :duration="2800"></animated-counter>
                        </div>
                        <h6 class="text-muted mt-2 mb-0 fw-semibold">Sampah Organik</h6>
                        <small style="color: #14b8a6;" class="fw-bold">
                            <i class="fas fa-check-circle"></i> Dapat terurai
                        </small>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3 fade-in" style="animation-delay: 0.6s;">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="mb-3">
                            <i class="fas fa-recycle fa-4x" style="color: #3b82f6;"></i>
                        </div>
                        <div class="h2 fw-bold text-dark">
                            <animated-counter :target-value="<?php echo $stats['anorganik']; ?>" :duration="3000"></animated-counter>
                        </div>
                        <h6 class="text-muted mt-2 mb-0 fw-semibold">Sampah Anorganik</h6>
                        <small class="text-primary fw-bold">
                            <i class="fas fa-recycle"></i> Dapat didaur ulang
                        </small>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3 fade-in" style="animation-delay: 0.7s;">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="mb-3">
                            <i class="fas fa-exclamation-triangle fa-4x" style="color: #ef4444;"></i>
                        </div>
                        <div class="h2 fw-bold text-dark">
                            <animated-counter :target-value="<?php echo $stats['b3']; ?>" :duration="3200"></animated-counter>
                        </div>
                        <h6 class="text-muted mt-2 mb-0 fw-semibold">Sampah B3</h6>
                        <small class="text-danger fw-bold">
                            <i class="fas fa-radiation"></i> Berbahaya
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Reports with Vue Data Table -->
        <div class="card fade-in border-0 shadow-sm" style="animation-delay: 0.8s;">
            <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom-0 pt-4 px-4 pb-2">
                <span class="h5 mb-0 fw-bold text-dark">
                    <i class="fas fa-list me-2 text-teal-600" style="color: #0d9488;"></i> Laporan Terbaru
                </span>
                <span class="badge rounded-pill" style="background-color: #e0f2fe; color: #0369a1; font-size: 0.85rem;">
                    {{ allReports.length }} Total
                </span>
            </div>
            <div class="card-body px-4 pb-4">
                <div v-if="allReports.length > 0">
                    <data-table 
                        :data="reportsData" 
                        :columns="reportColumns"
                    >
                        <template #default="{ item }">
                            <td class="fw-bold" @click="goToDetail(item.id)" style="cursor: pointer;">#{{ item.id }}</td>
                            <td @click="goToDetail(item.id)" style="cursor: pointer;">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-circle me-2">
                                        <i class="fas fa-user" :class="{'text-muted': !item.user_nama && item.guest_name}"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold" style="font-size: 0.9rem;">
                                            {{ item.user_nama || item.guest_name || 'Guest User' }}
                                        </div>
                                        <small class="text-muted" v-if="item.username">@{{ item.username }}</small>
                                        <small class="badge bg-light text-secondary border" v-else>@guest</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <img :src="getImageUrl(item.gambar)" 
                                     alt="Sampah" 
                                     class="rounded shadow-sm"
                                     style="width: 70px; height: 70px; object-fit: cover; cursor: pointer; transition: transform 0.3s;"
                                     @click.stop="showImageModal(getImageUrl(item.gambar))"
                                     @mouseover="$event.target.style.transform = 'scale(1.1)'"
                                     @mouseout="$event.target.style.transform = 'scale(1)'"
                                     @error="$event.target.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNzAiIGhlaWdodD0iNzAiIHZpZXdCb3g9IjAgMCA3MCA3MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iNzAiIGhlaWdodD0iNzAiIGZpbGw9IiNGM0Y0RjYiLz48cGF0aCBkPSJNMzIgMzJINDRWNDRIMzJWMzJaTTMyIDUwSDQ0VjYySDMyVjUwWiIgZmlsbD0iIzlDQTNBRiIvPjwvc3ZnPg=='; $event.target.onerror=null;">
                            </td>
                            <td>
                                <span class="badge" :class="'badge-' + item.kategori">
                                    {{ item.kategori.toUpperCase() }}
                                </span>
                                <br v-if="Number(item.is_corrected) === 1">
                                <small v-if="Number(item.is_corrected) === 1" class="text-warning">
                                    <i class="fas fa-edit"></i> Dikoreksi
                                </small>
                            </td>
                            <td @click="goToDetail(item.id)" style="cursor: pointer;">
                                <small class="text-muted">
                                    <i class="fas fa-map-marker-alt text-danger"></i>
                                    {{ item.alamat_lokasi.substring(0, 30) }}...
                                </small>
                            </td>
                            <td @click="goToDetail(item.id)" style="cursor: pointer;">
                                <div class="progress" style="height: 8px; width: 60px;">
                                    <div class="progress-bar bg-success" :style="{width: item.confidence + '%'}"></div>
                                </div>
                                <small class="text-muted">{{ item.confidence }}%</small>
                            </td>
                            <td @click="goToDetail(item.id)" style="cursor: pointer;">
                                <span class="badge" :class="'badge-' + item.status" style="font-size: 0.85rem; padding: 0.4rem 0.8rem;">
                                    {{ item.status === 'pending' ? '⏰ Pending' : 
                                       item.status === 'diproses' ? '🔄 Diproses' : 
                                       item.status === 'ditolak' ? '❌ Ditolak' :
                                       '✅ Selesai' }}
                                </span>
                            </td>
                            <td @click="goToDetail(item.id)" style="cursor: pointer;">
                                <small class="text-muted">
                                    <i class="fas fa-calendar-alt"></i> {{ formatDate(item.created_at) }}
                                </small>
                            </td>
                        </template>
                    </data-table>
                    
                    <!-- Pagination Controls -->
                    <div class="d-flex justify-content-between align-items-center mt-4 mb-3">
                        <div class="text-muted">
                            <small>{{ paginationInfo }}</small>
                        </div>
                        <nav v-if="totalPages > 1">
                            <ul class="pagination pagination-sm mb-0">
                                <li class="page-item" :class="{ disabled: currentPage === 1 }">
                                    <a class="page-link" href="#" @click.prevent="previousPage">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                                <li v-if="currentPage > 2" class="page-item">
                                    <a class="page-link" href="#" @click.prevent="changePage(1)">1</a>
                                </li>
                                <li v-if="currentPage > 3" class="page-item disabled">
                                    <span class="page-link">...</span>
                                </li>
                                <li v-if="currentPage > 1" class="page-item">
                                    <a class="page-link" href="#" @click.prevent="changePage(currentPage - 1)">
                                        {{ currentPage - 1 }}
                                    </a>
                                </li>
                                <li class="page-item active">
                                    <span class="page-link">{{ currentPage }}</span>
                                </li>
                                <li v-if="currentPage < totalPages" class="page-item">
                                    <a class="page-link" href="#" @click.prevent="changePage(currentPage + 1)">
                                        {{ currentPage + 1 }}
                                    </a>
                                </li>
                                <li v-if="currentPage < totalPages - 2" class="page-item disabled">
                                    <span class="page-link">...</span>
                                </li>
                                <li v-if="currentPage < totalPages - 1" class="page-item">
                                    <a class="page-link" href="#" @click.prevent="changePage(totalPages)">
                                        {{ totalPages }}
                                    </a>
                                </li>
                                <li class="page-item" :class="{ disabled: currentPage === totalPages }">
                                    <a class="page-link" href="#" @click.prevent="nextPage">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                    
                    <div class="text-center mt-4">
                        <a href="laporan.php" class="btn btn-primary btn-lg me-2 mb-2 shadow-sm" style="background-color: #0d9488; border-color: #0d9488;">
                            <i class="fas fa-list"></i> Lihat Semua Laporan
                        </a>
                        <a href="analytics.php" class="btn btn-success btn-lg mb-2 shadow-sm">
                            <i class="fas fa-brain"></i> Analytics & Insight
                        </a>
                    </div>
                </div>
                <div v-else class="empty-state text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3 opacity-50"></i>
                    <h5 class="text-muted fw-bold">Belum ada laporan</h5>
                    <p class="text-muted">Data laporan akan muncul di sini</p>
                </div>
            </div>
        </div>
        
        <!-- Toast Notifications -->
        <toast-notification ref="toast"></toast-notification>
    </div>

    <!-- Footer -->
    <div class="footer text-center py-4 text-muted small">
        <p>&copy; 2024 Sistem Pelaporan Sampah Cerdas | Powered by <a href="#" class="text-decoration-none" style="color: #0d9488;">Teachable Machine</a></p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/navbar.js"></script>
    
    <!-- Vue 3 -->
    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
    <script src="../assets/js/vue-components.js"></script>
    
    <!-- Initialize Vue App -->
    <script>
        const { createApp } = Vue;
        
        const app = createApp({
            components: {
                'animated-counter': VueComponents.AnimatedCounter,
                'data-table': VueComponents.DataTable,
                'toast-notification': VueComponents.ToastNotification
            },
            data() {
                return {
                    allReports: <?php echo json_encode($allReports); ?>,
                    reportColumns: [
                        { key: 'id', label: 'ID', sortable: true },
                        { key: 'user_nama', label: 'User', sortable: true },
                        { key: 'gambar', label: 'Gambar', sortable: false },
                        { key: 'kategori', label: 'Kategori', sortable: true },
                        { key: 'alamat_lokasi', label: 'Lokasi', sortable: false },
                        { key: 'confidence', label: 'Confidence', sortable: true },
                        { key: 'status', label: 'Status', sortable: true },
                        { key: 'created_at', label: 'Tanggal', sortable: true }
                    ],
                    showImageModal: false,
                    currentImage: '',
                    currentPage: 1,
                    perPage: 10
                }
            },
            computed: {
                reportsData() {
                    const start = (this.currentPage - 1) * this.perPage;
                    const end = start + this.perPage;
                    return this.allReports.slice(start, end);
                },
                totalPages() {
                    return Math.ceil(this.allReports.length / this.perPage);
                },
                paginationInfo() {
                    const start = (this.currentPage - 1) * this.perPage + 1;
                    const end = Math.min(this.currentPage * this.perPage, this.allReports.length);
                    return `Menampilkan ${start} - ${end} dari ${this.allReports.length} laporan`;
                }
            },
            mounted() {

                window.addEventListener('scroll', this.handleScroll);
                

                setTimeout(() => {
                    this.$refs.toast.addToast({
                        type: 'success',
                        title: 'Dashboard Loaded',
                        message: 'Selamat datang di Dashboard Admin!',
                        duration: 3000
                    });
                }, 500);
            },
            methods: {

                getImageUrl(filename) {
                    if (!filename) return '';
                    const baseUrl = '<?php echo rtrim(UPLOAD_URL, '/'); ?>';
                    const cleanFilename = filename.replace(/^\//, '').replace(/^\.\//, '');
                    let fullUrl = baseUrl + '/' + cleanFilename;
                    fullUrl = fullUrl.replace(/([^:])\/\//g, '$1/');
                    return fullUrl;
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
                showImageModal(imageSrc) {

                    const modal = document.createElement('div');
                    modal.className = 'modal fade';
                    modal.innerHTML = `
                        <div class="modal-dialog modal-lg modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Preview Gambar</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body text-center">
                                    <img src="${imageSrc}" class="img-fluid rounded" alt="Preview">
                                </div>
                            </div>
                        </div>
                    `;
                    document.body.appendChild(modal);
                    const bsModal = new bootstrap.Modal(modal);
                    bsModal.show();
                    modal.addEventListener('hidden.bs.modal', () => {
                        document.body.removeChild(modal);
                    });
                },
                goToDetail(reportId) {
                    window.location.href = 'detail.php?id=' + reportId;
                },
                handleScroll() {
                    const navbar = document.querySelector('.navbar');
                    if (window.scrollY > 50) {
                        navbar.classList.add('scrolled');
                    } else {
                        navbar.classList.remove('scrolled');
                    }
                },
                changePage(page) {
                    if (page >= 1 && page <= this.totalPages) {
                        this.currentPage = page;

                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    }
                },
                previousPage() {
                    if (this.currentPage > 1) {
                        this.currentPage--;
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    }
                },
                nextPage() {
                    if (this.currentPage < this.totalPages) {
                        this.currentPage++;
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    }
                }
            },
            beforeUnmount() {
                window.removeEventListener('scroll', this.handleScroll);
            }
        });
        
        app.mount('#dashboardApp');
        

        function updateClockAdmin() {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            const timeString = `${hours}:${minutes}:${seconds}`;
            
            const clockElement = document.getElementById('current-time-admin');
            if (clockElement) {
                clockElement.textContent = timeString;
            }
        }
        

        setInterval(updateClockAdmin, 1000);

        updateClockAdmin();
    </script>
    
    <!-- Additional Styles for Avatar Circle -->
    <style>
        .avatar-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.9rem;
            font-weight: 600;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        /* Table row hover effect for clickable rows */
        .data-table tbody tr {
            transition: all 0.2s ease;
        }
        
        .data-table tbody tr:hover {
            background-color: #f0fdfa !important;
            box-shadow: 0 4px 12px rgba(20, 184, 166, 0.1);
            transform: translateX(4px);
            z-index: 10;
            position: relative;
        }
        
        .data-table tbody td[style*="cursor: pointer"]:hover {
            background-color: rgba(20, 184, 166, 0.05);
        }
        
        /* Tooltip hint for clickable rows */
        .data-table tbody tr::after {
            content: '👆 Klik untuk lihat detail';
            position: absolute;
            right: 1rem;
            opacity: 0;
            font-size: 0.75rem;
            color: #14b8a6;
            font-weight: 600;
            transition: opacity 0.3s;
            pointer-events: none;
            margin-top: 1rem;
        }
        
        .data-table tbody tr:hover::after {
            opacity: 1;
        }
        
        @media (max-width: 768px) {
            .data-table tbody tr::after {
                display: none;
            }
        }
        
        /* Badge Styles */
        .badge {
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        
        .badge-organik { background-color: #dcfce7 !important; color: #166534 !important; }
        .badge-anorganik { background-color: #dbeafe !important; color: #1e40af !important; }
        .badge-b3 { background-color: #fee2e2 !important; color: #991b1b !important; }
    </style>
</body>
</html>
