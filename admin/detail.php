<?php
require_once '../config/config.php';
require_once '../config/Database.php';
require_once '../classes/Report.php';
require_once '../classes/Comment.php';
require_once '../assets/js/jenis-sampah.php';

checkLogin();
checkAdmin();

$currentPage = 'detail'; 

$database = new Database();
$db = $database->getConnection();
$report = new Report($db);


if (isset($_GET['delete'])) {
    $deleteId = filter_input(INPUT_GET, 'delete', FILTER_VALIDATE_INT);
    if ($deleteId && $report->delete($deleteId)) {
        $_SESSION['success'] = 'Laporan berhasil dihapus!';
        redirect('admin/laporan.php');
    } else {
        $_SESSION['error'] = 'Gagal menghapus laporan.';
        redirect('admin/laporan.php');
    }
}


$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    $_SESSION['error'] = 'ID laporan tidak valid!';
    redirect('admin/laporan.php');
}

$reportData = $report->getById($id);

if (!$reportData) {
    $_SESSION['error'] = 'Laporan tidak ditemukan!';
    redirect('admin/laporan.php');
}


if (isset($_POST['update_status'])) {
    $newStatus = $_POST['status'];
    if ($report->updateStatus($id, $newStatus)) {
        $success = 'Status berhasil diupdate!';
        $reportData['status'] = $newStatus;

        $reportData = $report->getById($id);
    } else {
        $error = 'Gagal mengupdate status.';
    }
}


if (isset($_POST['reject_report'])) {
    $rejection_reason = trim($_POST['rejection_reason'] ?? '');
    if (empty($rejection_reason)) {
        $error = 'Harap masukkan alasan penolakan!';
    } else {
        if ($report->rejectReport($id, $rejection_reason)) {
            $success = 'Laporan berhasil ditolak!';

            $reportData = $report->getById($id);
        } else {
            $error = 'Gagal menolak laporan.';
        }
    }
}


if (isset($_POST['admin_correct_report'])) {
    $admin_correction = $_POST['admin_correction'];
    $admin_feedback = trim($_POST['admin_feedback'] ?? '');
    
    if (empty($admin_feedback)) {
        $error = 'Harap masukkan catatan koreksi untuk user!';
    } else {
        if ($report->updateAdminCorrection($id, $admin_correction, $admin_feedback)) {
            $success = 'Koreksi berhasil disimpan! Kategori laporan telah diperbarui.';

            $reportData = $report->getById($id);
        } else {
            $error = 'Gagal menyimpan koreksi admin.';
        }
    }
}


$comment = new Comment($db);


if (isset($_POST['add_comment'])) {
    $commentText = trim($_POST['comment_text']);
    if (!empty($commentText)) {
        $comment->report_id = $id;
        $comment->admin_id = $_SESSION['user_id'];
        $comment->comment = $commentText;
        
        if ($comment->create()) {
            $success = 'Komentar berhasil ditambahkan!';
        } else {
            $error = 'Gagal menambahkan komentar.';
        }
    } else {
        $error = 'Komentar tidak boleh kosong!';
    }
}


if (isset($_GET['delete_comment'])) {
    $commentId = filter_input(INPUT_GET, 'delete_comment', FILTER_VALIDATE_INT);
    if ($commentId) {
        $comment->admin_id = $_SESSION['user_id'];
        if ($comment->delete($commentId)) {
            $success = 'Komentar berhasil dihapus!';
        } else {
            $error = 'Gagal menghapus komentar atau Anda tidak memiliki akses.';
        }
    }
}


$reportComments = $comment->getByReportId($id);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Laporan - Admin</title>
    
    
    <link rel="icon" type="image/svg+xml" href="../favicon.svg">
    <link rel="alternate icon" href="../favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="../favicon.svg">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="../assets/css/style.css">
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    
    <?php include 'includes/navbar.php'; ?>

    
    <div class="container mt-4 mb-5">
        <?php if (isset($success)): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
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
                        
                        <div class="text-center mb-4">
                            <img src="<?php echo getImageUrl($reportData['gambar']); ?>" 
                                 alt="Sampah" 
                                 class="img-fluid rounded" 
                                 style="max-height: 400px; box-shadow: 0 5px 15px rgba(0,0,0,0.2);"
                                 onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAwIiBoZWlnaHQ9IjQwMCIgdmlld0JveD0iMCAwIDQwMCA0MDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHJlY3Qgd2lkdGg9IjQwMCIgaGVpZ2h0PSI0MDAiIGZpbGw9IiNGM0Y0RjYiLz48cGF0aCBkPSJNMTgwIDE2MEgyMjBWMjAwSDE4MFYxNjBaTTE4MCAyNDBIMjIwVjI4MEgxODBWMjQwWk0xODAgMzIwSDIyMFYzNjBIMTgwVjMyMFoiIGZpbGw9IiM5Q0EzQUYiLz48L3N2Zz4='; this.style.border='2px solid #e5e7eb';">
                        </div>

                        
                        <div class="classification-result mb-4">
                            <?php if (!empty($reportData['admin_correction'])): ?>
                                
                                <h5><i class="fas fa-check-double"></i> Hasil Koreksi Admin</h5>
                                <div class="alert" style="background-color: #d1fae5; border-left: 4px solid #10b981;">
                                    <p class="mb-0">
                                        <i class="fas fa-user-shield"></i> 
                                        <strong>Laporan ini telah dikoreksi oleh Admin.</strong>
                                    </p>
                                    <small class="text-muted">
                                        Kategori saat ini valid sesuai kebijakan admin.
                                    </small>
                                    <?php if (!empty($reportData['admin_feedback'])): ?>
                                        <hr>
                                        <p class="mb-0">
                                            <strong>Catatan Admin:</strong><br>
                                            <em>"<?php echo nl2br(htmlspecialchars($reportData['admin_feedback'])); ?>"</em>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            <?php elseif (isset($reportData['is_corrected']) && $reportData['is_corrected']): ?>
                                
                                <h5><i class="fas fa-user-edit"></i> Hasil Setelah Koreksi Manual oleh User</h5>
                                <div class="alert" style="background-color: #fff3e0; border-left: 4px solid #ff9800;">
                                    <p class="mb-0">
                                        <i class="fas fa-info-circle"></i> 
                                        <strong>Laporan ini telah dikoreksi oleh user (<?php echo htmlspecialchars($reportData['user_nama']); ?>)</strong>
                                    </p>
                                    <?php if (!empty($reportData['ai_prediction'])): ?>
                                        <small class="text-muted">
                                            Prediksi AI awal: <strong><?php echo strtoupper($reportData['ai_prediction']); ?></strong> 
                                            → Dikoreksi menjadi: <strong><?php echo strtoupper($reportData['kategori']); ?></strong>
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
                                        <strong>Catatan Koreksi dari User:</strong><br>
                                        <em>"<?php echo nl2br(htmlspecialchars($reportData['correction_note'])); ?>"</em>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>

                        
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
                        
                        
                        <?php if (isset($reportData['confidence'])): ?>
                            <div class="mb-4">
                                <h6><i class="fas fa-chart-bar"></i> Keyakinan AI</h6>
                                <div class="d-flex align-items-center">
                                    <div class="progress flex-grow-1 me-3" style="height: 10px;">
                                        <div class="progress-bar <?php echo $reportData['confidence'] > 80 ? 'bg-success' : ($reportData['confidence'] > 50 ? 'bg-warning' : 'bg-danger'); ?>" 
                                             role="progressbar" 
                                             style="width: <?php echo $reportData['confidence']; ?>%">
                                        </div>
                                    </div>
                                    <span class="fw-bold"><?php echo $reportData['confidence']; ?>%</span>
                                </div>
                            </div>
                        <?php endif; ?>

                        
                        <div class="mb-4">
                            <h6><i class="fas fa-user"></i> Pelapor</h6>
                            <p>
                                <strong>Nama:</strong> <?php echo htmlspecialchars($reportData['user_nama'] ?? $reportData['guest_name'] ?? 'Guest'); ?><br>
                                <?php if (!empty($reportData['username'])): ?>
                                <strong>Username:</strong> @<?php echo htmlspecialchars($reportData['username']); ?><br>
                                <?php else: ?>
                                <strong>Username:</strong> <span class="badge bg-secondary">Guest/Non-Member</span><br>
                                <?php endif; ?>
                                <?php 

                                $whatsapp = !empty($reportData['whatsapp_number']) ? $reportData['whatsapp_number'] : 
                                           (!empty($reportData['user_nomor_hp']) ? $reportData['user_nomor_hp'] : '');
                                ?>
                                <?php if (!empty($whatsapp)): ?>
                                <strong>WhatsApp:</strong> 
                                <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $whatsapp); ?>" 
                                   target="_blank" 
                                   class="text-success" 
                                   style="text-decoration: none;">
                                    <i class="fab fa-whatsapp"></i> <?php echo htmlspecialchars($whatsapp); ?>
                                    <small class="text-muted">(klik untuk chat)</small>
                                </a>
                                <?php else: ?>
                                <strong>WhatsApp:</strong> <span class="text-muted">-</span>
                                <?php endif; ?>
                            </p>
                        </div>

                        
                        <?php if ($reportData['deskripsi']): ?>
                            <div class="mb-4">
                                <h6><i class="fas fa-edit"></i> Deskripsi</h6>
                                <p><?php echo nl2br(htmlspecialchars($reportData['deskripsi'])); ?></p>
                            </div>
                        <?php endif; ?>

                        
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

                        
                        <div class="mb-4">
                            <h6><i class="fas fa-map-marker-alt"></i> Lokasi</h6>
                            <p><?php echo htmlspecialchars($reportData['alamat_lokasi']); ?></p>
                            <p>
                                <small class="text-muted">
                                    Koordinat: <?php echo number_format($reportData['lokasi_latitude'], 6); ?>, 
                                    <?php echo number_format($reportData['lokasi_longitude'], 6); ?>
                                </small>
                            </p>
                            <div id="detailMap" style="height: 300px; border-radius: 10px;"></div>
                        </div>

                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-start">
                            <a href="laporan.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Kembali
                            </a>
                            <button type="button" class="btn btn-danger" onclick="confirmDelete(<?php echo $reportData['id']; ?>)">
                                <i class="fas fa-trash"></i> Hapus Laporan
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                
                <div class="card mb-3 fade-in">
                    <div class="card-header">
                        <i class="fas fa-edit"></i> Update Status
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label"><strong>Status Saat Ini:</strong></label>
                                <select name="status" class="form-select" id="statusSelect">
                                    <option value="pending" <?php echo $reportData['status'] == 'pending' ? 'selected' : ''; ?>>
                                        Pending
                                    </option>
                                    <option value="diproses" <?php echo $reportData['status'] == 'diproses' ? 'selected' : ''; ?>>
                                        Diproses
                                    </option>
                                    <option value="selesai" <?php echo $reportData['status'] == 'selesai' ? 'selected' : ''; ?>>
                                        Selesai
                                    </option>
                                    <option value="ditolak" <?php echo $reportData['status'] == 'ditolak' ? 'selected' : ''; ?>>
                                        Ditolak
                                    </option>
                                </select>
                            </div>
                            <button type="submit" name="update_status" class="btn btn-primary w-100 mb-2">
                                <i class="fas fa-save"></i> Update Status
                            </button>
                        </form>
                        
                        
                        <?php if ($reportData['status'] != 'ditolak'): ?>
                        <hr>
                        <button type="button" class="btn btn-danger w-100" data-bs-toggle="modal" data-bs-target="#rejectModal">
                            <i class="fas fa-times-circle"></i> Tolak Laporan
                        </button>
                        <?php endif; ?>
                    </div>
                </div>

                
                <div class="card mb-3 fade-in" style="border-left: 4px solid #f59e0b;">
                    <div class="card-header bg-warning text-dark">
                        <i class="fas fa-user-check"></i> Koreksi Admin
                    </div>
                    <div class="card-body">
                        <?php if (!empty($reportData['admin_correction'])): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-check-circle"></i> Sudah dikoreksi admin.
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label"><strong>Koreksi Kategori:</strong></label>
                                <select name="admin_correction" class="form-select">
                                    <option value="organik" <?php echo $reportData['kategori'] == 'organik' ? 'selected' : ''; ?>>Organik</option>
                                    <option value="anorganik" <?php echo $reportData['kategori'] == 'anorganik' ? 'selected' : ''; ?>>Anorganik</option>
                                    <option value="b3" <?php echo $reportData['kategori'] == 'b3' ? 'selected' : ''; ?>>B3 (Berbahaya)</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label"><strong>Catatan untuk User:</strong></label>
                                <textarea name="admin_feedback" class="form-control" rows="3" required placeholder="Jelaskan kenapa kategori ini dikoreksi..."><?php echo htmlspecialchars($reportData['admin_feedback'] ?? ''); ?></textarea>
                            </div>
                            <button type="submit" name="admin_correct_report" class="btn btn-warning w-100 text-dark font-weight-bold">
                                <i class="fas fa-save"></i> Simpan Koreksi
                            </button>
                        </form>
                    </div>
                </div>

                
                <?php if ($reportData['status'] == 'ditolak' && !empty($reportData['rejection_reason'])): ?>
                <div class="card mb-3 fade-in" style="border-left: 4px solid #ef4444;">
                    <div class="card-header bg-danger text-white">
                        <i class="fas fa-ban"></i> Alasan Penolakan
                    </div>
                    <div class="card-body">
                        <p class="mb-0" style="color: #991b1b;">
                            <i class="fas fa-info-circle"></i> 
                            <?php echo nl2br(htmlspecialchars($reportData['rejection_reason'])); ?>
                        </p>
                    </div>
                </div>
                <?php endif; ?>

                
                <div class="card mb-3 fade-in">
                    <div class="card-header">
                        <i class="fas fa-info-circle"></i> Informasi Laporan
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <tr>
                                <td><strong>ID:</strong></td>
                                <td>#<?php echo $reportData['id']; ?></td>
                            </tr>
                            <tr>
                                <td><strong>Status:</strong></td>
                                <td>
                                    <span class="badge badge-<?php echo $reportData['status']; ?>">
                                        <?php echo ucfirst($reportData['status']); ?>
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
                        </table>
                    </div>
                </div>

                
                <div class="card mb-3 fade-in">
                    <div class="card-header">
                        <i class="fas fa-tasks"></i> Status Pemrosesan
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled">
                            <li class="mb-3">
                                <i class="fas fa-check-circle <?php echo in_array($reportData['status'], ['pending', 'diproses', 'selesai']) ? 'text-success' : ($reportData['status'] == 'ditolak' ? 'text-danger' : 'text-muted'); ?>"></i>
                                <strong>Laporan Diterima</strong>
                                <br><small class="text-muted"><?php echo date('d M Y, H:i', strtotime($reportData['created_at'])); ?></small>
                            </li>
                            <li class="mb-3">
                                <i class="fas fa-<?php echo $reportData['status'] == 'ditolak' ? 'times-circle text-danger' : ($reportData['status'] == 'diproses' || $reportData['status'] == 'selesai' ? 'check-circle text-success' : 'circle text-muted'); ?>"></i>
                                <strong>Sedang Diproses</strong>
                            </li>
                            <?php if ($reportData['status'] == 'ditolak'): ?>
                            <li class="mb-3">
                                <i class="fas fa-ban text-danger"></i>
                                <strong style="color: #ef4444;">Ditolak</strong>
                                <br><small class="text-muted">Laporan tidak sesuai</small>
                            </li>
                            <?php else: ?>
                            <li class="mb-3">
                                <i class="fas fa-<?php echo $reportData['status'] == 'selesai' ? 'check-circle text-success' : 'circle text-muted'; ?>"></i>
                                <strong>Selesai</strong>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>

                
                <div class="card fade-in">
                    <div class="card-header">
                        <i class="fas fa-comments"></i> Komentar Admin
                        <span class="badge bg-primary ms-2"><?php echo count($reportComments); ?></span>
                    </div>
                    <div class="card-body">
                        
                        <form method="POST" class="mb-4">
                            <div class="mb-3">
                                <label for="comment_text" class="form-label"><strong>Tambah Komentar:</strong></label>
                                <textarea 
                                    class="form-control" 
                                    id="comment_text" 
                                    name="comment_text" 
                                    rows="3" 
                                    placeholder="Tulis komentar untuk laporan ini..."
                                    required
                                ></textarea>
                                <small class="text-muted">Komentar ini akan terlihat oleh user yang melaporkan</small>
                            </div>
                            <button type="submit" name="add_comment" class="btn btn-primary w-100">
                                <i class="fas fa-paper-plane"></i> Kirim Komentar
                            </button>
                        </form>

                        <hr>

                        
                        <div class="comments-list">
                            <?php if (count($reportComments) > 0): ?>
                                <?php foreach ($reportComments as $commentItem): ?>
                                    <div class="comment-item mb-3 p-3" style="background: #f8f9fa; border-radius: 8px; border-left: 3px solid #14b8a6;">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div>
                                                <strong>
                                                    <i class="fas fa-user-shield"></i> 
                                                    <?php echo htmlspecialchars($commentItem['admin_nama']); ?>
                                                </strong>
                                                <small class="text-muted ms-2">
                                                    <i class="fas fa-clock"></i> 
                                                    <?php echo date('d M Y, H:i', strtotime($commentItem['created_at'])); ?>
                                                </small>
                                            </div>
                                            <a 
                                                href="?id=<?php echo $id; ?>&delete_comment=<?php echo $commentItem['id']; ?>" 
                                                class="text-danger"
                                                onclick="return confirm('Yakin ingin menghapus komentar ini?')"
                                                title="Hapus komentar"
                                            >
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                        <p class="mb-0" style="color: #333; line-height: 1.6;">
                                            <?php echo nl2br(htmlspecialchars($commentItem['comment'])); ?>
                                        </p>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted text-center mb-0">
                                    <i class="fas fa-comment-slash"></i> Belum ada komentar
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    
    <div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="rejectModalLabel">
                        <i class="fas fa-times-circle"></i> Tolak Laporan
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> 
                            <strong>Peringatan!</strong> Laporan yang ditolak akan berubah status menjadi "Ditolak" dan user akan melihat alasan penolakan.
                        </div>
                        <div class="mb-3">
                            <label for="rejection_reason" class="form-label">
                                <strong>Alasan Penolakan <span class="text-danger">*</span></strong>
                            </label>
                            <textarea 
                                class="form-control" 
                                id="rejection_reason" 
                                name="rejection_reason" 
                                rows="5" 
                                placeholder="Masukkan alasan mengapa laporan ini ditolak. Alasan ini akan terlihat oleh user yang melaporkan."
                                required
                            ></textarea>
                            <small class="text-muted">Alasan penolakan wajib diisi dan akan ditampilkan ke user.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i> Batal
                        </button>
                        <button type="submit" name="reject_report" class="btn btn-danger">
                            <i class="fas fa-ban"></i> Tolak Laporan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    
    <div class="footer">
        <p>&copy; 2024 Aplikasi Pelaporan Sampah dengan AI | Powered by Teachable Machine</p>
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
            .bindPopup('<strong>Lokasi Sampah</strong><br><?php echo htmlspecialchars($reportData['alamat_lokasi']); ?>')
            .openPopup();
    </script>

    
    <script>
        function confirmDelete(reportId) {
            Swal.fire({
                title: 'Hapus Laporan?',
                text: "Laporan yang dihapus tidak dapat dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: '<i class="fas fa-trash"></i> Ya, Hapus!',
                cancelButtonText: '<i class="fas fa-times"></i> Batal',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {

                    Swal.fire({
                        title: 'Menghapus...',
                        text: 'Mohon tunggu sebentar',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    

                    window.location.href = '?delete=' + reportId;
                }
            });
        }
    </script>
</body>
</html>

