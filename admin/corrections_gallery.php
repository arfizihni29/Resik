<?php
require_once '../config/config.php';
require_once '../config/Database.php';
require_once '../classes/CorrectionManager.php';

checkLogin();
checkAdmin();

$currentPage = 'gallery'; // For navbar active state

$correctionManager = new CorrectionManager();
$stats = $correctionManager->getStatistics();
$totalCorrections = $correctionManager->getTotalCorrections();


if (isset($_GET['delete']) && isset($_GET['from']) && isset($_GET['to'])) {
    $imagePath = '../' . $_GET['delete'];
    if (file_exists($imagePath)) {
        if (unlink($imagePath)) {
            $_SESSION['success'] = 'Gambar berhasil dihapus!';
        } else {
            $_SESSION['error'] = 'Gagal menghapus gambar.';
        }
    } else {
        $_SESSION['error'] = 'File tidak ditemukan.';
    }
    header('Location: corrections_gallery.php?from=' . $_GET['from'] . '&to=' . $_GET['to']);
    exit;
}


$filterFrom = isset($_GET['from']) ? $_GET['from'] : 'all';
$filterTo = isset($_GET['to']) ? $_GET['to'] : 'all';


$images = [];
if ($filterFrom !== 'all' && $filterTo !== 'all') {
    $images = $correctionManager->getCorrectionImages($filterFrom, $filterTo);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Galeri Gambar Koreksi - Admin</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="../favicon.svg">
    <link rel="alternate icon" href="../favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="../favicon.svg">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .gallery-item {
            position: relative;
            overflow: hidden;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s;
            cursor: pointer;
        }
        .gallery-item:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        }
        .gallery-item img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            cursor: pointer;
        }
        .gallery-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.95), rgba(0,0,0,0.7));
            color: white;
            padding: 10px;
            font-size: 11px;
        }
        .gallery-overlay > div:first-child,
        .gallery-overlay > div:nth-child(2) {
            margin-bottom: 5px;
        }
        .gallery-overlay .btn {
            white-space: nowrap;
            font-size: 10px;
            padding: 3px 6px;
        }
        .gallery-overlay .btn i {
            font-size: 9px;
        }
        .correction-flow {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 10px 0;
        }
        .correction-badge {
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <?php include 'includes/navbar.php'; ?>

    <!-- Content -->
    <div class="container-fluid mt-4 mb-5">
        <div class="fade-in">
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
            
            <h2 class="mb-4" style="color: var(--primary-green);">
                <i class="fas fa-images"></i> Galeri Gambar Koreksi AI
            </h2>

            <!-- Info Alert -->
            <div class="alert" style="background-color: #e8f5e9; border-left: 4px solid #4caf50;">
                <h5 style="color: #2e7d32; margin-bottom: 10px;">
                    <i class="fas fa-info-circle"></i> Tentang Galeri Ini
                </h5>
                <p class="mb-0">
                    Galeri ini berisi <strong><?php echo $totalCorrections; ?> gambar</strong> yang telah dikoreksi oleh user karena AI salah prediksi.
                    Gambar-gambar ini sudah tersimpan di folder <code>uploads/corrections/</code> dan siap digunakan untuk <strong>retrain AI</strong>.
                </p>
            </div>

            <!-- Statistics Matrix -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-bar"></i> Matriks Koreksi AI
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered text-center">
                            <thead>
                                <tr>
                                    <th rowspan="2" class="align-middle">AI Prediksi</th>
                                    <th colspan="3">User Koreksi Ke</th>
                                    <th rowspan="2" class="align-middle">Total</th>
                                </tr>
                                <tr>
                                    <th>🍃 Organik</th>
                                    <th>♻️ Anorganik</th>
                                    <th>⚠️ B3</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>🍃 Organik</strong></td>
                                    <td class="bg-light">-</td>
                                    <td>
                                        <a href="?from=organik&to=anorganik" class="btn btn-sm btn-outline-primary">
                                            <?php echo $stats['from_organik_to_anorganik']; ?> gambar
                                        </a>
                                    </td>
                                    <td>
                                        <a href="?from=organik&to=b3" class="btn btn-sm btn-outline-danger">
                                            <?php echo $stats['from_organik_to_b3']; ?> gambar
                                        </a>
                                    </td>
                                    <td><strong><?php echo $stats['from_organik_to_anorganik'] + $stats['from_organik_to_b3']; ?></strong></td>
                                </tr>
                                <tr>
                                    <td><strong>♻️ Anorganik</strong></td>
                                    <td>
                                        <a href="?from=anorganik&to=organik" class="btn btn-sm btn-outline-success">
                                            <?php echo $stats['from_anorganik_to_organik']; ?> gambar
                                        </a>
                                    </td>
                                    <td class="bg-light">-</td>
                                    <td>
                                        <a href="?from=anorganik&to=b3" class="btn btn-sm btn-outline-danger">
                                            <?php echo $stats['from_anorganik_to_b3']; ?> gambar
                                        </a>
                                    </td>
                                    <td><strong><?php echo $stats['from_anorganik_to_organik'] + $stats['from_anorganik_to_b3']; ?></strong></td>
                                </tr>
                                <tr>
                                    <td><strong>⚠️ B3</strong></td>
                                    <td>
                                        <a href="?from=b3&to=organik" class="btn btn-sm btn-outline-success">
                                            <?php echo $stats['from_b3_to_organik']; ?> gambar
                                        </a>
                                    </td>
                                    <td>
                                        <a href="?from=b3&to=anorganik" class="btn btn-sm btn-outline-primary">
                                            <?php echo $stats['from_b3_to_anorganik']; ?> gambar
                                        </a>
                                    </td>
                                    <td class="bg-light">-</td>
                                    <td><strong><?php echo $stats['from_b3_to_organik'] + $stats['from_b3_to_anorganik']; ?></strong></td>
                                </tr>
                                <tr class="table-success">
                                    <td colspan="4"><strong>TOTAL GAMBAR KOREKSI</strong></td>
                                    <td><strong style="font-size: 1.2rem;"><?php echo $totalCorrections; ?></strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Gallery Display -->
            <?php if ($filterFrom !== 'all' && $filterTo !== 'all'): ?>
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-images"></i> Gambar Koreksi: 
                                <span class="badge badge-<?php echo $filterFrom; ?>" style="font-size: 1rem;">
                                    AI: <?php echo strtoupper($filterFrom); ?>
                                </span>
                                <i class="fas fa-arrow-right mx-2"></i>
                                <span class="badge badge-<?php echo $filterTo; ?>" style="font-size: 1rem;">
                                    USER: <?php echo strtoupper($filterTo); ?>
                                </span>
                            </div>
                            <a href="corrections_gallery.php" class="btn btn-sm btn-secondary">
                                <i class="fas fa-times"></i> Clear Filter
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (count($images) > 0): ?>
                            <!-- Action Buttons -->
                            <div class="mb-3">
                                <button class="btn btn-success" onclick="downloadAllImages()">
                                    <i class="fas fa-download"></i> Download Semua (<?php echo count($images); ?> gambar)
                                </button>
                                <a href="../<?php echo $correctionManager->getCorrectionPath($filterFrom, $filterTo); ?>" 
                                   class="btn btn-info" target="_blank">
                                    <i class="fas fa-folder-open"></i> Buka Folder
                                </a>
                                <span class="text-muted ms-3">
                                    <i class="fas fa-info-circle"></i> Total size: 
                                    <?php 
                                    $totalSize = array_sum(array_column($images, 'size'));
                                    echo $totalSize > 1048576 ? round($totalSize/1048576, 2) . ' MB' : round($totalSize/1024, 2) . ' KB';
                                    ?>
                                </span>
                            </div>

                            <!-- Gallery Grid -->
                            <div class="row g-3">
                                <?php foreach ($images as $img): ?>
                                    <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                                        <div class="gallery-item">
                                            <img src="../<?php echo $img['url']; ?>" alt="<?php echo $img['filename']; ?>" loading="lazy" onclick="showImageModal('<?php echo htmlspecialchars($img['url']); ?>', '<?php echo htmlspecialchars($img['filename']); ?>')">
                                            <div class="gallery-overlay">
                                                <div><i class="fas fa-file"></i> <?php echo substr($img['filename'], 0, 15); ?>...</div>
                                                <div><i class="fas fa-clock"></i> <?php echo date('d/m/Y H:i', $img['modified']); ?></div>
                                                <div class="mt-2 d-flex gap-1">
                                                    <button onclick="showImageModal('<?php echo htmlspecialchars($img['url']); ?>', '<?php echo htmlspecialchars($img['filename']); ?>')" class="btn btn-sm btn-info btn-block" style="flex: 1; font-size: 0.7rem; padding: 2px 5px;">
                                                        <i class="fas fa-eye"></i> Detail
                                                    </button>
                                                    <a href="../<?php echo $img['url']; ?>" download="<?php echo htmlspecialchars($img['filename']); ?>" class="btn btn-sm btn-success btn-block" style="flex: 1; font-size: 0.7rem; padding: 2px 5px;">
                                                        <i class="fas fa-download"></i> Download
                                                    </a>
                                                    <button onclick="deleteImage('<?php echo htmlspecialchars($img['url']); ?>', '<?php echo htmlspecialchars($img['filename']); ?>', '<?php echo $filterFrom; ?>', '<?php echo $filterTo; ?>')" class="btn btn-sm btn-danger btn-block" style="flex: 1; font-size: 0.7rem; padding: 2px 5px;">
                                                        <i class="fas fa-trash"></i> Hapus
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-folder-open fa-4x text-muted mb-3"></i>
                                <h5 class="text-muted">Belum ada gambar koreksi untuk kategori ini</h5>
                                <p class="text-muted">Pilih kategori lain dari matriks di atas</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-hand-pointer fa-4x text-primary mb-3"></i>
                        <h5>Pilih Kategori Koreksi</h5>
                        <p class="text-muted">Klik salah satu tombol di matriks di atas untuk melihat gambar</p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- How to Use -->
            <div class="card mt-4">
                <div class="card-header" style="background: #2196f3; color: white;">
                    <i class="fas fa-question-circle"></i> Cara Menggunakan Gambar untuk Retrain AI
                </div>
                <div class="card-body">
                    <ol>
                        <li><strong>Download Gambar:</strong> Klik "Download Semua" untuk kategori yang ingin diperbaiki</li>
                        <li><strong>Buka Teachable Machine:</strong> Kunjungi <a href="https://teachablemachine.withgoogle.com/" target="_blank">teachablemachine.withgoogle.com</a></li>
                        <li><strong>Upload Gambar:</strong> Tambahkan gambar ke class yang BENAR (bukan yang AI prediksi)</li>
                        <li><strong>Retrain Model:</strong> Train ulang model dengan dataset yang sudah diperbaiki</li>
                        <li><strong>Deploy:</strong> Export model dan replace API yang lama</li>
                        <li><strong>Test:</strong> Test akurasi model yang baru</li>
                    </ol>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> <strong>Penting:</strong> 
                        Review manual setiap gambar sebelum digunakan untuk training. Pastikan koreksi user benar-benar akurat.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Image Preview Modal -->
    <div class="modal fade" id="imageModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="imageModalTitle">Preview Gambar</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="modalImage" src="" class="img-fluid" alt="Preview" style="max-height: 70vh;">
                </div>
                <div class="modal-footer">
                    <a id="downloadImageBtn" href="" download class="btn btn-success">
                        <i class="fas fa-download"></i> Download Gambar
                    </a>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>&copy; 2024 Aplikasi Pelaporan Sampah dengan AI | Powered by Teachable Machine</p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/navbar.js"></script>
    <script>
        function showImageModal(imageUrl, filename) {
            document.getElementById('modalImage').src = '../' + imageUrl;
            document.getElementById('imageModalTitle').textContent = filename;
            document.getElementById('downloadImageBtn').href = '../' + imageUrl;
            document.getElementById('downloadImageBtn').download = filename;
            
            const modal = new bootstrap.Modal(document.getElementById('imageModal'));
            modal.show();
        }

        function downloadAllImages() {
            alert('Fitur download massal: Silakan buka folder langsung dan copy semua gambar.\n\nAtau gunakan command:\nWindows: xcopy\nLinux/Mac: cp -r');

        }

        function deleteImage(imageUrl, filename, filterFrom, filterTo) {
            if (confirm(`Apakah Anda yakin ingin menghapus gambar "${filename}"?\n\nGambar akan dihapus permanen dari server.`)) {
                window.location.href = `corrections_gallery.php?delete=${encodeURIComponent(imageUrl)}&from=${filterFrom}&to=${filterTo}`;
            }
        }
    </script>
</body>
</html>

