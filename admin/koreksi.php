<?php
require_once '../config/config.php';
require_once '../config/Database.php';
require_once '../classes/Report.php';
require_once '../assets/js/jenis-sampah.php';

checkLogin();
checkAdmin();

$currentPage = 'koreksi'; 

$database = new Database();
$db = $database->getConnection();
$report = new Report($db);


$filterKategori = isset($_GET['kategori']) ? $_GET['kategori'] : 'all';


$query = "SELECT r.*, u.nama as user_nama, u.username 
          FROM reports r 
          LEFT JOIN users u ON r.user_id = u.id 
          WHERE r.is_corrected = 1";

if ($filterKategori != 'all') {
    $query .= " AND r.ai_prediction = :kategori";
}

$query .= " ORDER BY r.created_at DESC";

$stmt = $db->prepare($query);
if ($filterKategori != 'all') {
    $stmt->bindParam(':kategori', $filterKategori);
}
$stmt->execute();
$correctedReports = $stmt->fetchAll(PDO::FETCH_ASSOC);


$totalCorrected = count($correctedReports);


$aiOrganik = 0;
$aiAnorganik = 0;
$aiB3 = 0;


$accurateCount = 0;

foreach ($correctedReports as $r) {
    if ($r['ai_prediction'] == 'organik') $aiOrganik++;
    if ($r['ai_prediction'] == 'anorganik') $aiAnorganik++;
    if ($r['ai_prediction'] == 'b3') $aiB3++;
    

    if ($r['ai_prediction'] == $r['kategori']) {
        $accurateCount++;
    }
}

$errorRate = $totalCorrected > 0 ? round((($totalCorrected - $accurateCount) / $totalCorrected) * 100, 2) : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Terkoreksi - Admin</title>
    
    
    <link rel="icon" type="image/svg+xml" href="../favicon.svg">
    <link rel="alternate icon" href="../favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="../favicon.svg">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    
    <?php include 'includes/navbar.php'; ?>

    
    <div class="container-fluid mt-4 mb-5">
        <div class="fade-in">
            <h2 class="mb-4" style="color: var(--primary-green);">
                <i class="fas fa-edit"></i> Laporan yang Dikoreksi User
            </h2>

            
            <div class="alert" style="background-color: #fff3e0; border-left: 4px solid #ff9800;">
                <h5 style="color: #f57c00; margin-bottom: 10px;">
                    <i class="fas fa-info-circle"></i> Tentang Halaman Ini
                </h5>
                <p class="mb-0">
                    Halaman ini menampilkan laporan yang <strong>dikoreksi manual oleh user</strong> karena hasil AI tidak sesuai.
                    Data ini sangat berharga untuk <strong>meningkatkan akurasi AI</strong> di masa depan.
                </p>
            </div>

            
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="stat-card">
                        <i class="fas fa-edit" style="color: #ff9800;"></i>
                        <h3><?php echo $totalCorrected; ?></h3>
                        <p>Total Koreksi</p>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-card">
                        <i class="fas fa-exclamation-triangle" style="color: #f44336;"></i>
                        <h3><?php echo $errorRate; ?>%</h3>
                        <p>Error Rate AI</p>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-card">
                        <i class="fas fa-chart-line" style="color: #2196f3;"></i>
                        <h3><?php echo $accurateCount; ?></h3>
                        <p>AI Akurat</p>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-card">
                        <i class="fas fa-times-circle" style="color: #f44336;"></i>
                        <h3><?php echo $totalCorrected - $accurateCount; ?></h3>
                        <p>AI Salah</p>
                    </div>
                </div>
            </div>

            
            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="fas fa-leaf fa-3x text-success mb-3"></i>
                            <h3><?php echo $aiOrganik; ?></h3>
                            <h6>AI Prediksi: Organik</h6>
                            <small class="text-muted">Yang dikoreksi user</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="fas fa-recycle fa-3x text-primary mb-3"></i>
                            <h3><?php echo $aiAnorganik; ?></h3>
                            <h6>AI Prediksi: Anorganik</h6>
                            <small class="text-muted">Yang dikoreksi user</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
                            <h3><?php echo $aiB3; ?></h3>
                            <h6>AI Prediksi: B3</h6>
                            <small class="text-muted">Yang dikoreksi user</small>
                        </div>
                    </div>
                </div>
            </div>

            
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-3">
                            <h6><i class="fas fa-filter"></i> Filter by AI Prediction:</h6>
                        </div>
                        <div class="col-md-9">
                            <div class="btn-group w-100" role="group">
                                <a href="?kategori=all" class="btn btn-sm <?php echo $filterKategori == 'all' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                    Semua (<?php echo $totalCorrected; ?>)
                                </a>
                                <a href="?kategori=organik" class="btn btn-sm <?php echo $filterKategori == 'organik' ? 'btn-success' : 'btn-outline-success'; ?>">
                                    <i class="fas fa-leaf"></i> Organik (<?php echo $aiOrganik; ?>)
                                </a>
                                <a href="?kategori=anorganik" class="btn btn-sm <?php echo $filterKategori == 'anorganik' ? 'btn-info' : 'btn-outline-info'; ?>">
                                    <i class="fas fa-recycle"></i> Anorganik (<?php echo $aiAnorganik; ?>)
                                </a>
                                <a href="?kategori=b3" class="btn btn-sm <?php echo $filterKategori == 'b3' ? 'btn-danger' : 'btn-outline-danger'; ?>">
                                    <i class="fas fa-exclamation-triangle"></i> B3 (<?php echo $aiB3; ?>)
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-table"></i> Daftar Laporan Terkoreksi (<?php echo count($correctedReports); ?> laporan)
                </div>
                <div class="card-body">
                    <?php if (count($correctedReports) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>User</th>
                                        <th>Gambar</th>
                                        <th>AI Prediksi</th>
                                        <th>→</th>
                                        <th>User Koreksi</th>
                                        <th>Jenis Sampah</th>
                                        <th>Confidence</th>
                                        <th>Catatan Koreksi</th>
                                        <th>Tanggal</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($correctedReports as $r): ?>
                                        <tr>
                                            <td>#<?php echo $r['id']; ?></td>
                                            <td>
                                                <small>
                                                    <i class="fas fa-user"></i> <?php echo htmlspecialchars($r['user_nama']); ?>
                                                    <br><span class="text-muted">@<?php echo htmlspecialchars($r['username']); ?></span>
                                                </small>
                                            </td>
                                            <td>
                                                <img src="<?php echo getImageUrl($r['gambar']); ?>" 
                                                     alt="Sampah" 
                                                     class="img-thumbnail" 
                                                     style="width: 70px; height: 70px; object-fit: cover; cursor: pointer;"
                                                     onclick="showImageModal('<?php echo getImageUrl($r['gambar']); ?>')"
                                                     onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNzAiIGhlaWdodD0iNzAiIHZpZXdCb3g9IjAgMCA3MCA3MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iNzAiIGhlaWdodD0iNzAiIGZpbGw9IiNGM0Y0RjYiLz48cGF0aCBkPSJNMzIgMzJINDRWNDRIMzJWMzJaTTMyIDUwSDQ0VjYySDMyVjUwWiIgZmlsbD0iIzlDQTNBRiIvPjwvc3ZnPg=='; this.style.border='1px solid #e5e7eb';">
                                            </td>
                                            <td>
                                                <span class="badge badge-<?php echo $r['ai_prediction']; ?>" style="font-size: 0.9rem;">
                                                    <i class="fas fa-robot"></i> <?php echo strtoupper($r['ai_prediction']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <i class="fas fa-arrow-right text-warning" style="font-size: 1.2rem;"></i>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?php echo $r['kategori']; ?>" style="font-size: 0.9rem;">
                                                    <i class="fas fa-user-edit"></i> <?php echo strtoupper($r['kategori']); ?>
                                                </span>
                                                <?php if ($r['ai_prediction'] != $r['kategori']): ?>
                                                    <br><small class="text-danger"><i class="fas fa-times"></i> Tidak Match</small>
                                                <?php else: ?>
                                                    <br><small class="text-success"><i class="fas fa-check"></i> Match</small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php 
                                                if (isset($r['jenis_sampah'])):
                                                    $jenisInfo = getJenisSampahInfo($r['jenis_sampah']); 
                                                ?>
                                                    <small style="color: <?php echo $jenisInfo['color']; ?>;">
                                                        <?php echo $jenisInfo['icon']; ?> <?php echo $jenisInfo['label']; ?>
                                                    </small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary"><?php echo $r['confidence']; ?>%</span>
                                            </td>
                                            <td>
                                                <?php if (!empty($r['correction_note'])): ?>
                                                    <small class="text-muted">
                                                        <i class="fas fa-comment"></i>
                                                        <?php echo substr(htmlspecialchars($r['correction_note']), 0, 30); ?>...
                                                    </small>
                                                    <br>
                                                    <button class="btn btn-sm btn-outline-info mt-1" 
                                                            onclick="showNoteModal('<?php echo htmlspecialchars(addslashes($r['correction_note'])); ?>')">
                                                        <i class="fas fa-eye"></i> Lihat
                                                    </button>
                                                <?php else: ?>
                                                    <small class="text-muted">-</small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($r['created_at'])); ?></td>
                                            <td>
                                                <a href="detail.php?id=<?php echo $r['id']; ?>" 
                                                   class="btn btn-sm btn-success">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                            <h5 class="text-muted">Tidak ada laporan yang dikoreksi</h5>
                            <p class="text-muted">
                                <?php if ($filterKategori != 'all'): ?>
                                    Tidak ada koreksi untuk kategori AI: <strong><?php echo strtoupper($filterKategori); ?></strong>
                                <?php else: ?>
                                    Semua prediksi AI akurat atau belum ada koreksi dari user
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            
            <?php if ($totalCorrected > 0): ?>
                <div class="card mt-4">
                    <div class="card-header" style="background: var(--light-green); color: white;">
                        <i class="fas fa-chart-bar"></i> Analisis Koreksi AI
                    </div>
                    <div class="card-body">
                        <h6>Kesimpulan Akurasi AI:</h6>
                        <div class="alert alert-<?php echo $errorRate < 20 ? 'success' : ($errorRate < 40 ? 'warning' : 'danger'); ?>">
                            <p class="mb-2">
                                <strong>Total Laporan Dikoreksi:</strong> <?php echo $totalCorrected; ?> dari semua laporan
                            </p>
                            <p class="mb-2">
                                <strong>Error Rate:</strong> <?php echo $errorRate; ?>% 
                                <?php if ($errorRate < 20): ?>
                                    <span class="badge bg-success">Sangat Baik</span>
                                <?php elseif ($errorRate < 40): ?>
                                    <span class="badge bg-warning">Perlu Peningkatan</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Butuh Training Ulang</span>
                                <?php endif; ?>
                            </p>
                        </div>
                        
                        <h6 class="mt-3">Rekomendasi:</h6>
                        <ul>
                            <?php if ($errorRate > 30): ?>
                                <li>🔴 <strong>Urgent:</strong> AI perlu di-retrain dengan data koreksi ini</li>
                            <?php elseif ($errorRate > 15): ?>
                                <li>🟡 <strong>Perhatian:</strong> Monitor terus dan kumpulkan lebih banyak data koreksi</li>
                            <?php else: ?>
                                <li>🟢 <strong>Baik:</strong> AI sudah cukup akurat, lanjutkan monitoring</li>
                            <?php endif; ?>
                            <li>📊 Gunakan data koreksi untuk meningkatkan dataset training</li>
                            <li>🎯 Fokus pada kategori dengan koreksi terbanyak</li>
                            <li>💡 Pertimbangkan feedback dari catatan koreksi user</li>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    
    <div class="modal fade" id="imageModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Preview Gambar</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="modalImage" src="" class="img-fluid" alt="Preview">
                </div>
            </div>
        </div>
    </div>

    
    <div class="modal fade" id="noteModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header" style="background: var(--light-green); color: white;">
                    <h5 class="modal-title">
                        <i class="fas fa-comment"></i> Catatan Koreksi User
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p id="noteContent" style="white-space: pre-wrap;"></p>
                </div>
            </div>
        </div>
    </div>

    
    <div class="footer">
        <p>&copy; 2024 Aplikasi Pelaporan Sampah dengan AI | Powered by Teachable Machine</p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/navbar.js"></script>
    <script>
        function showImageModal(imageSrc) {
            document.getElementById('modalImage').src = imageSrc;
            const modal = new bootstrap.Modal(document.getElementById('imageModal'));
            modal.show();
        }

        function showNoteModal(note) {
            document.getElementById('noteContent').textContent = note;
            const modal = new bootstrap.Modal(document.getElementById('noteModal'));
            modal.show();
        }
    </script>
</body>
</html>

