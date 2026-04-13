<?php
require_once '../config/config.php';
require_once '../config/Database.php';
require_once '../classes/Report.php';
require_once '../classes/Comment.php';
require_once '../assets/js/jenis-sampah.php';

checkLogin();

$database = new Database();
$db = $database->getConnection();
$report = new Report($db);


$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    $_SESSION['error'] = 'ID laporan tidak valid!';
    redirect('dashboard.php');
}

$reportData = $report->getById($id);

if (!$reportData || $reportData['user_id'] != $_SESSION['user_id']) {
    $_SESSION['error'] = 'Laporan tidak ditemukan atau bukan milik Anda!';
    redirect('dashboard.php');
}


$comment = new Comment($db);
$reportComments = $comment->getByReportId($id);
$commentCount = count($reportComments);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Laporan - Pelaporan Sampah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="../assets/css/style.css">
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
                        <a class="nav-link" href="profile.php">
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
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-8">
                <div class="card fade-in">
                    <div class="card-header">
                        <i class="fas fa-file-alt"></i> Detail Laporan #<?php echo $reportData['id']; ?>
                    </div>
                    <div class="card-body">
                        <!-- Image -->
                        <div class="text-center mb-4">
                            <div class="position-relative d-inline-block">
                                <img src="<?php echo getImageUrl($reportData['gambar']); ?>" 
                                     alt="Sampah" 
                                     class="img-fluid rounded zoomable-image" 
                                     style="max-height: 400px; box-shadow: 0 5px 15px rgba(0,0,0,0.2); cursor: pointer;"
                                     onclick="openImageZoom(this.src)"
                                     title="Klik untuk memperbesar gambar"
                                     onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAwIiBoZWlnaHQ9IjQwMCIgdmlld0JveD0iMCAwIDQwMCA0MDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHJlY3Qgd2lkdGg9IjQwMCIgaGVpZ2h0PSI0MDAiIGZpbGw9IiNGM0Y0RjYiLz48cGF0aCBkPSJNMTgwIDE2MEgyMjBWMjAwSDE4MFYxNjBaTTE4MCAyNDBIMjIwVjI4MEgxODBWMjQwWk0xODAgMzIwSDIyMFYzNjBIMTgwVjMyMFoiIGZpbGw9IiM5Q0EzQUYiLz48L3N2Zz4='; this.style.border='2px solid #e5e7eb';">
                                <div class="zoom-hint">
                                    <i class="fas fa-search-plus"></i> Klik untuk zoom
                                </div>
                            </div>
                        </div>

                        <!-- Classification Info -->
                        <div class="classification-result mb-4">
                            <?php if (isset($reportData['is_corrected']) && $reportData['is_corrected']): ?>
                                <!-- Show correction info -->
                                <h5><i class="fas fa-user-edit"></i> Hasil Setelah Koreksi Manual</h5>
                                <div class="alert" style="background-color: #fff3e0; border-left: 4px solid #ff9800;">
                                    <p class="mb-0">
                                        <i class="fas fa-info-circle"></i> 
                                        <strong>Laporan ini telah dikoreksi oleh user</strong>
                                    </p>
                                    <?php if (!empty($reportData['ai_prediction'])): ?>
                                        <small class="text-muted">
                                            Prediksi AI awal: <strong><?php echo strtoupper($reportData['ai_prediction']); ?></strong>
                                        </small>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <h5><i class="fas fa-robot"></i> Hasil Klasifikasi AI</h5>
                            <?php endif; ?>
                            
                            <?php
                            $categoryColor = '#4caf50';
                            $categoryIcon = 'leaf';
                            $categoryName = 'Organik';
                            
                            if ($reportData['kategori'] === 'anorganik') {
                                $categoryColor = '#2196f3';
                                $categoryIcon = 'recycle';
                                $categoryName = 'Anorganik';
                            } elseif ($reportData['kategori'] === 'b3') {
                                $categoryColor = '#f44336';
                                $categoryIcon = 'exclamation-triangle';
                                $categoryName = 'B3 (Bahan Berbahaya Beracun)';
                            }
                            ?>
                            <div class="alert" style="background-color: <?php echo $categoryColor; ?>22; border-left: 4px solid <?php echo $categoryColor; ?>;">
                                <h4 style="color: <?php echo $categoryColor; ?>;">
                                    <i class="fas fa-<?php echo $categoryIcon; ?>"></i> <?php echo $categoryName; ?>
                                </h4>
                                <?php if (!isset($reportData['is_corrected']) || !$reportData['is_corrected']): ?>
                                    <p><strong>Tingkat Keyakinan:</strong> <?php echo $reportData['confidence']; ?>%</p>
                                    <div class="progress" style="height: 25px;">
                                        <div class="progress-bar" role="progressbar" 
                                             style="width: <?php echo $reportData['confidence']; ?>%; background-color: <?php echo $categoryColor; ?>;">
                                            <?php echo $reportData['confidence']; ?>%
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (isset($reportData['is_corrected']) && $reportData['is_corrected'] && !empty($reportData['correction_note'])): ?>
                                    <p class="mt-2 mb-0">
                                        <strong>Catatan Koreksi:</strong><br>
                                        <?php echo nl2br(htmlspecialchars($reportData['correction_note'])); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Jenis Sampah Detail -->
                        <?php if (isset($reportData['jenis_sampah'])): ?>
                            <?php $jenisInfo = getJenisSampahInfo($reportData['jenis_sampah']); ?>
                            <div class="mb-4">
                                <h6><i class="fas fa-tag"></i> Jenis Sampah</h6>
                                <div class="alert" style="background-color: <?php echo $jenisInfo['color']; ?>22; border-left: 4px solid <?php echo $jenisInfo['color']; ?>;">
                                    <h5 style="color: <?php echo $jenisInfo['color']; ?>; margin-bottom: 0;">
                                        <?php echo $jenisInfo['icon']; ?> <?php echo $jenisInfo['label']; ?>
                                    </h5>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Description -->
                        <?php if ($reportData['deskripsi']): ?>
                            <div class="mb-4">
                                <h6><i class="fas fa-edit"></i> Deskripsi</h6>
                                <p><?php echo nl2br(htmlspecialchars($reportData['deskripsi'])); ?></p>
                            </div>
                        <?php endif; ?>

                        <!-- Tags -->
                        <?php if (!empty($reportData['tags'])): ?>
                            <div class="mb-4">
                                <h6><i class="fas fa-tags"></i> Tags</h6>
                                <div class="tags-display">
                                    <?php 
                                    $tagsArray = explode(',', $reportData['tags']);
                                    foreach ($tagsArray as $tag): 
                                        $tag = trim($tag);
                                        if (!empty($tag)):
                                    ?>
                                        <span class="tag-badge">
                                            <i class="fas fa-hashtag"></i> <?php echo htmlspecialchars($tag); ?>
                                        </span>
                                    <?php 
                                        endif;
                                    endforeach; 
                                    ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Location -->
                        <div class="mb-4">
                            <h6><i class="fas fa-map-marker-alt"></i> Lokasi</h6>
                            <p><?php echo htmlspecialchars($reportData['alamat_lokasi']); ?></p>
                            <div id="detailMap" style="height: 300px; border-radius: 10px;"></div>
                        </div>

                        <!-- Rejection Notice (if rejected) -->
                        <?php if ($reportData['status'] == 'ditolak' && !empty($reportData['rejection_reason'])): ?>
                        <div class="alert alert-danger mb-4" style="border-left: 4px solid #ef4444; background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);">
                            <h5 class="mb-3" style="color: #991b1b;">
                                <i class="fas fa-ban"></i> Laporan Ditolak
                            </h5>
                            <p class="mb-2" style="color: #7f1d1d; font-weight: 600;">
                                Alasan Penolakan:
                            </p>
                            <p class="mb-0" style="color: #991b1b; line-height: 1.8;">
                                <?php echo nl2br(htmlspecialchars($reportData['rejection_reason'])); ?>
                            </p>
                            <hr style="margin: 1rem 0; border-color: rgba(153, 27, 27, 0.3);">
                            <small style="color: #7f1d1d;">
                                <i class="fas fa-info-circle"></i> 
                                Jika Anda merasa penolakan ini tidak sesuai, silakan hubungi administrator untuk klarifikasi.
                            </small>
                        </div>
                        <?php endif; ?>

                        <!-- Action Buttons -->
                        <div class="d-grid gap-2 d-md-flex justify-content-md-start">
                            <a href="dashboard.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Kembali
                            </a>
                            <?php if ($reportData['status'] == 'pending'): ?>
                            <a href="edit.php?id=<?php echo $reportData['id']; ?>" class="btn btn-warning">
                                <i class="fas fa-edit"></i> Edit Laporan
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Komentar dari Admin -->
                <?php if ($commentCount > 0): ?>
                <div class="card fade-in mt-4">
                    <div class="card-header" style="background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%); color: white;">
                        <i class="fas fa-comments"></i> Komentar dari Admin
                        <span class="badge bg-light text-dark ms-2"><?php echo $commentCount; ?></span>
                    </div>
                    <div class="card-body">
                        <div class="comments-list">
                            <?php foreach ($reportComments as $commentItem): ?>
                                <div class="comment-item mb-3 p-3" style="background: linear-gradient(135deg, #f0fdfa 0%, #e0f2fe 100%); border-radius: 12px; border-left: 4px solid #14b8a6; box-shadow: 0 2px 8px rgba(20, 184, 166, 0.15);">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <strong style="color: #0d9488;">
                                                <i class="fas fa-user-shield"></i> 
                                                <?php echo htmlspecialchars($commentItem['admin_nama']); ?>
                                            </strong>
                                            <small class="text-muted ms-2">
                                                <i class="fas fa-clock"></i> 
                                                <?php echo date('d M Y, H:i', strtotime($commentItem['created_at'])); ?>
                                            </small>
                                        </div>
                                    </div>
                                    <p class="mb-0" style="color: #1f2937; line-height: 1.7; font-size: 0.95rem;">
                                        <?php echo nl2br(htmlspecialchars($commentItem['comment'])); ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="col-lg-4">
                <!-- Status Card -->
                <div class="card mb-3 fade-in">
                    <div class="card-header">
                        <i class="fas fa-info-circle"></i> Informasi Laporan
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <tr>
                                <td><strong>Status:</strong></td>
                                <td>
                                    <span class="badge badge-<?php echo $reportData['status']; ?>">
                                        <?php 
                                        $statusLabels = [
                                            'pending' => '⏰ Pending',
                                            'diproses' => '🔄 Diproses',
                                            'selesai' => '✅ Selesai',
                                            'ditolak' => '❌ Ditolak'
                                        ];
                                        echo $statusLabels[$reportData['status']] ?? ucfirst($reportData['status']); 
                                        ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Kategori:</strong></td>
                                <td>
                                    <span class="badge badge-<?php echo $reportData['kategori']; ?>">
                                        <?php echo strtoupper($reportData['kategori']); ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Tanggal:</strong></td>
                                <td><?php echo date('d F Y, H:i', strtotime($reportData['created_at'])); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Koordinat:</strong></td>
                                <td>
                                    <small>
                                        <?php echo number_format($reportData['lokasi_latitude'], 6); ?>,<br>
                                        <?php echo number_format($reportData['lokasi_longitude'], 6); ?>
                                    </small>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Status Timeline -->
                <div class="card fade-in">
                    <div class="card-header">
                        <i class="fas fa-tasks"></i> Status Pemrosesan
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled">
                            <li class="mb-3">
                                <i class="fas fa-check-circle <?php echo in_array($reportData['status'], ['pending', 'diproses', 'selesai']) ? 'text-success' : ($reportData['status'] == 'ditolak' ? 'text-danger' : 'text-muted'); ?>"></i>
                                <strong>Laporan Diterima</strong>
                            </li>
                            <?php if ($reportData['status'] == 'ditolak'): ?>
                            <li class="mb-3">
                                <i class="fas fa-ban text-danger"></i>
                                <strong style="color: #ef4444;">Ditolak</strong>
                                <br><small class="text-muted">Laporan tidak sesuai</small>
                            </li>
                            <?php else: ?>
                            <li class="mb-3">
                                <i class="fas fa-<?php echo $reportData['status'] == 'diproses' || $reportData['status'] == 'selesai' ? 'check-circle text-success' : 'circle text-muted'; ?>"></i>
                                <strong>Sedang Diproses</strong>
                            </li>
                            <li class="mb-3">
                                <i class="fas fa-<?php echo $reportData['status'] == 'selesai' ? 'check-circle text-success' : 'circle text-muted'; ?>"></i>
                                <strong>Selesai</strong>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>&copy; 2024 Aplikasi Pelaporan Sampah dengan AI | Powered by Teachable Machine</p>
    </div>

    <!-- Image Zoom Modal -->
    <div class="modal fade" id="imageZoomModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content" style="background: rgba(0, 0, 0, 0.95);">
                <div class="modal-header border-0" style="position: absolute; top: 0; right: 0; z-index: 2;">
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body d-flex align-items-center justify-content-center p-0">
                    <div class="zoom-container">
                        <img id="zoomedImage" src="" alt="Zoomed Image" class="zoom-image">
                        <div class="zoom-controls">
                            <button class="zoom-btn" onclick="zoomIn()"><i class="fas fa-plus"></i></button>
                            <button class="zoom-btn" onclick="zoomOut()"><i class="fas fa-minus"></i></button>
                            <button class="zoom-btn" onclick="resetZoom()"><i class="fas fa-sync"></i></button>
                            <button class="zoom-btn" onclick="downloadImage()"><i class="fas fa-download"></i></button>
                        </div>
                        <div class="zoom-hint-text">
                            <small>
                                <i class="fas fa-mouse"></i> Scroll untuk zoom | 
                                <i class="fas fa-hand-paper"></i> Drag untuk pindahkan | 
                                <i class="fas fa-times"></i> ESC untuk tutup
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="../assets/js/navbar.js"></script>
    <script>

        const lat = <?php echo $reportData['lokasi_latitude']; ?>;
        const lng = <?php echo $reportData['lokasi_longitude']; ?>;
        
        const detailMap = L.map('detailMap').setView([lat, lng], 15);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(detailMap);
        
        L.marker([lat, lng]).addTo(detailMap)
            .bindPopup('Lokasi Sampah')
            .openPopup();
        

        let scale = 1;
        let isDragging = false;
        let startX, startY, translateX = 0, translateY = 0;
        
        function openImageZoom(src) {
            document.getElementById('zoomedImage').src = src;
            const modal = new bootstrap.Modal(document.getElementById('imageZoomModal'));
            modal.show();
            resetZoom();
        }
        
        function zoomIn() {
            scale += 0.2;
            if (scale > 5) scale = 5;
            applyTransform();
        }
        
        function zoomOut() {
            scale -= 0.2;
            if (scale < 0.5) scale = 0.5;
            applyTransform();
        }
        
        function resetZoom() {
            scale = 1;
            translateX = 0;
            translateY = 0;
            applyTransform();
        }
        
        function applyTransform() {
            const img = document.getElementById('zoomedImage');
            img.style.transform = `translate(${translateX}px, ${translateY}px) scale(${scale})`;
        }
        
        function downloadImage() {
            const img = document.getElementById('zoomedImage');
            const link = document.createElement('a');
            link.href = img.src;
            link.download = 'laporan_sampah_' + Date.now() + '.jpg';
            link.click();
        }
        

        document.getElementById('imageZoomModal').addEventListener('wheel', (e) => {
            e.preventDefault();
            if (e.deltaY < 0) {
                zoomIn();
            } else {
                zoomOut();
            }
        });
        

        const zoomedImage = document.getElementById('zoomedImage');
        
        zoomedImage.addEventListener('mousedown', (e) => {
            if (scale > 1) {
                isDragging = true;
                startX = e.clientX - translateX;
                startY = e.clientY - translateY;
                zoomedImage.style.cursor = 'grabbing';
            }
        });
        
        document.addEventListener('mousemove', (e) => {
            if (isDragging) {
                translateX = e.clientX - startX;
                translateY = e.clientY - startY;
                applyTransform();
            }
        });
        
        document.addEventListener('mouseup', () => {
            isDragging = false;
            zoomedImage.style.cursor = scale > 1 ? 'grab' : 'default';
        });
        

        let initialDistance = 0;
        let initialScale = 1;
        
        zoomedImage.addEventListener('touchstart', (e) => {
            if (e.touches.length === 2) {
                initialDistance = getDistance(e.touches[0], e.touches[1]);
                initialScale = scale;
            } else if (e.touches.length === 1 && scale > 1) {
                isDragging = true;
                startX = e.touches[0].clientX - translateX;
                startY = e.touches[0].clientY - translateY;
            }
        });
        
        zoomedImage.addEventListener('touchmove', (e) => {
            if (e.touches.length === 2) {
                e.preventDefault();
                const currentDistance = getDistance(e.touches[0], e.touches[1]);
                scale = initialScale * (currentDistance / initialDistance);
                if (scale < 0.5) scale = 0.5;
                if (scale > 5) scale = 5;
                applyTransform();
            } else if (e.touches.length === 1 && isDragging) {
                e.preventDefault();
                translateX = e.touches[0].clientX - startX;
                translateY = e.touches[0].clientY - startY;
                applyTransform();
            }
        });
        
        zoomedImage.addEventListener('touchend', () => {
            isDragging = false;
        });
        
        function getDistance(touch1, touch2) {
            const dx = touch1.clientX - touch2.clientX;
            const dy = touch1.clientY - touch2.clientY;
            return Math.sqrt(dx * dx + dy * dy);
        }
    </script>
    
    <style>
        .zoom-hint {
            position: absolute;
            bottom: -30px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(20, 184, 166, 0.9);
            color: white;
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            white-space: nowrap;
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: none;
        }
        
        .zoomable-image:hover + .zoom-hint {
            opacity: 1;
        }
        
        .zoom-container {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        
        .zoom-image {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            transition: transform 0.3s ease;
            user-select: none;
        }
        
        .zoom-controls {
            position: absolute;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 0.5rem;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 0.75rem;
            border-radius: 50px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }
        
        .zoom-btn {
            width: 45px;
            height: 45px;
            border: none;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
        }
        
        .zoom-btn:hover {
            background: rgba(20, 184, 166, 0.8);
            transform: scale(1.1);
        }
        
        .zoom-hint-text {
            position: absolute;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 20px;
            font-size: 0.875rem;
        }
        
        @media (max-width: 768px) {
            .zoom-controls {
                bottom: 15px;
                padding: 0.5rem;
            }
            
            .zoom-btn {
                width: 40px;
                height: 40px;
                font-size: 1rem;
            }
            
            .zoom-hint-text {
                font-size: 0.75rem;
                padding: 0.4rem 1rem;
            }
        }
        
        /* Tags Display */
        .tags-display {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        
        .tag-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
            color: white;
            padding: 0.4rem 0.9rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
            box-shadow: 0 2px 4px rgba(20, 184, 166, 0.3);
            transition: all 0.3s ease;
        }
        
        .tag-badge:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(20, 184, 166, 0.4);
        }
        
        .tag-badge i {
            font-size: 0.75rem;
            opacity: 0.9;
        }
    </style>
</body>
</html>

