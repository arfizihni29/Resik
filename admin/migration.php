<?php
require_once '../config/config.php';
require_once '../config/Database.php';


checkLogin();
checkAdmin();

$database = new Database();
$db = $database->getConnection();

$message = '';
$messageType = '';
$migrationResults = [];


function checkTableExists($db, $tableName) {
    try {
        $stmt = $db->query("SHOW TABLES LIKE '{$tableName}'");
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

function checkColumnExists($db, $tableName, $columnName) {
    try {
        $stmt = $db->query("SHOW COLUMNS FROM `{$tableName}` LIKE '{$columnName}'");
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

function checkEnumValues($db, $tableName, $columnName) {
    try {
        $stmt = $db->query("SHOW COLUMNS FROM `{$tableName}` WHERE Field = '{$columnName}'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result && strpos($result['Type'], 'enum') !== false) {
            return $result['Type'];
        }
        return null;
    } catch (PDOException $e) {
        return null;
    }
}


$usersTableExists = checkTableExists($db, 'users');
$reportsTableExists = checkTableExists($db, 'reports');
$commentsTableExists = checkTableExists($db, 'report_comments');
$userLocationsTableExists = checkTableExists($db, 'user_locations');

$nomorHpExists = $usersTableExists ? checkColumnExists($db, 'users', 'nomor_hp') : false;
$whatsappNumberExists = $reportsTableExists ? checkColumnExists($db, 'reports', 'whatsapp_number') : false;
$additionalImagesExists = $reportsTableExists ? checkColumnExists($db, 'reports', 'additional_images') : false;
$tagsExists = $reportsTableExists ? checkColumnExists($db, 'reports', 'tags') : false;


$jenisSampahEnum = $reportsTableExists ? checkEnumValues($db, 'reports', 'jenis_sampah') : null;
$jenisSampahUpdated = false;
if ($jenisSampahEnum && strpos($jenisSampahEnum, 'daun') !== false && strpos($jenisSampahEnum, 'botol_plastik') !== false) {
    $jenisSampahUpdated = true;
}


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['run_migration'])) {
    try {
        $db->beginTransaction();
        
        $migrationsRun = [];
        

        if (!$nomorHpExists && $usersTableExists) {
            $sql = "ALTER TABLE `users` ADD COLUMN `nomor_hp` VARCHAR(20) DEFAULT NULL AFTER `nama`";
            $db->exec($sql);
            $migrationsRun[] = "✓ Kolom nomor_hp ditambahkan ke tabel users";
        }
        

        if (!$whatsappNumberExists && $reportsTableExists) {
            $sql = "ALTER TABLE `reports` 
                    ADD COLUMN `whatsapp_number` varchar(20) DEFAULT NULL 
                    COMMENT 'Nomor WhatsApp user untuk notifikasi' 
                    AFTER `alamat_lokasi`";
            $db->exec($sql);
            $migrationsRun[] = "✓ Kolom whatsapp_number ditambahkan ke tabel reports";
        }
        

        if (!$additionalImagesExists && $reportsTableExists) {
            $sql = "ALTER TABLE `reports` 
                    ADD COLUMN `additional_images` TEXT 
                    COMMENT 'JSON array untuk gambar tambahan' 
                    AFTER `gambar`";
            $db->exec($sql);
            $migrationsRun[] = "✓ Kolom additional_images ditambahkan ke tabel reports";
        }
        

        if (!$tagsExists && $reportsTableExists) {
            $sql = "ALTER TABLE `reports` 
                    ADD COLUMN `tags` VARCHAR(500) 
                    COMMENT 'Comma-separated tags untuk analitik' 
                    AFTER `correction_note`";
            $db->exec($sql);
            $migrationsRun[] = "✓ Kolom tags ditambahkan ke tabel reports";
        }
        

        if (!$userLocationsTableExists) {
            $sql = "CREATE TABLE IF NOT EXISTS `user_locations` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `user_id` int(11) NOT NULL,
              `name` varchar(100) NOT NULL,
              `latitude` decimal(10,8) NOT NULL,
              `longitude` decimal(11,8) NOT NULL,
              `address` text NOT NULL,
              `last_used` timestamp NOT NULL DEFAULT current_timestamp(),
              `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
              PRIMARY KEY (`id`),
              UNIQUE KEY `unique_user_location` (`user_id`,`name`),
              KEY `idx_user_last_used` (`user_id`,`last_used`),
              CONSTRAINT `user_locations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
            $db->exec($sql);
            $migrationsRun[] = "✓ Tabel user_locations dibuat";
        }
        

        if (!$commentsTableExists) {
            $sql = "CREATE TABLE IF NOT EXISTS `report_comments` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `report_id` int(11) NOT NULL,
              `admin_id` int(11) NOT NULL COMMENT 'ID admin yang memberi komentar',
              `comment` text NOT NULL,
              `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
              PRIMARY KEY (`id`),
              KEY `report_id` (`report_id`),
              KEY `admin_id` (`admin_id`),
              CONSTRAINT `report_comments_ibfk_1` FOREIGN KEY (`report_id`) REFERENCES `reports` (`id`) ON DELETE CASCADE,
              CONSTRAINT `report_comments_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
            $db->exec($sql);
            $migrationsRun[] = "✓ Tabel report_comments dibuat";
        }
        
        $db->commit();
        
        if (count($migrationsRun) > 0) {
            $message = "Migration berhasil! " . count($migrationsRun) . " migration dijalankan.";
            $messageType = 'success';
            $migrationResults = $migrationsRun;
            

            $nomorHpExists = checkColumnExists($db, 'users', 'nomor_hp');
            $whatsappNumberExists = checkColumnExists($db, 'reports', 'whatsapp_number');
            $additionalImagesExists = checkColumnExists($db, 'reports', 'additional_images');
            $tagsExists = checkColumnExists($db, 'reports', 'tags');
            $userLocationsTableExists = checkTableExists($db, 'user_locations');
            $commentsTableExists = checkTableExists($db, 'report_comments');
        } else {
            $message = "Tidak ada migration yang perlu dijalankan. Database sudah up-to-date!";
            $messageType = 'info';
        }
        
    } catch (PDOException $e) {
        $db->rollBack();
        $message = "Error: " . $e->getMessage();
        $messageType = 'danger';
    }
}


$needsMigration = !$usersTableExists || !$reportsTableExists || 
                  !$nomorHpExists || !$whatsappNumberExists || 
                  !$additionalImagesExists || !$tagsExists || 
                  !$userLocationsTableExists || !$commentsTableExists ||
                  !$jenisSampahUpdated;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Migration - Admin</title>
    
    
    <link rel="icon" type="image/svg+xml" href="../favicon.svg">
    <link rel="alternate icon" href="../favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="../favicon.svg">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    
    <style>
        .status-badge {
            display: inline-block;
            padding: 0.4rem 0.8rem;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .status-success {
            background: #d1fae5;
            color: #065f46;
        }
        .status-warning {
            background: #fef3c7;
            color: #92400e;
        }
        .migration-card {
            border-left: 4px solid #14b8a6;
            transition: all 0.3s ease;
        }
        .migration-card:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 12px rgba(20, 184, 166, 0.15);
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container-fluid mt-4 mb-5">
        <div class="row mb-4">
            <div class="col-md-8">
                <h2 class="mb-2" style="color: #1f2937; font-weight: 700;">
                    <i class="fas fa-database" style="color: #14b8a6; margin-right: 0.5rem;"></i>
                    Database Migration Tool
                </h2>
                <p class="text-muted mb-0">
                    <i class="fas fa-info-circle me-2"></i>Tool untuk update struktur database secara aman
                </p>
            </div>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
            <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : ($messageType === 'danger' ? 'exclamation-circle' : 'info-circle'); ?> me-2"></i>
            <?php echo htmlspecialchars($message); ?>
            <?php if (count($migrationResults) > 0): ?>
                <ul class="mb-0 mt-2">
                    <?php foreach ($migrationResults as $result): ?>
                    <li><?php echo htmlspecialchars($result); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        
        <div class="row mb-4">
            <div class="col-md-6 mb-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title mb-3">
                            <i class="fas fa-chart-pie me-2" style="color: #14b8a6;"></i>
                            Status Database
                        </h5>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>Users Table</span>
                            <span class="status-badge <?php echo $usersTableExists ? 'status-success' : 'status-warning'; ?>">
                                <?php echo $usersTableExists ? '✓ Ada' : '✗ Tidak Ada'; ?>
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>Reports Table</span>
                            <span class="status-badge <?php echo $reportsTableExists ? 'status-success' : 'status-warning'; ?>">
                                <?php echo $reportsTableExists ? '✓ Ada' : '✗ Tidak Ada'; ?>
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>Report Comments Table</span>
                            <span class="status-badge <?php echo $commentsTableExists ? 'status-success' : 'status-warning'; ?>">
                                <?php echo $commentsTableExists ? '✓ Ada' : '✗ Tidak Ada'; ?>
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span>User Locations Table</span>
                            <span class="status-badge <?php echo $userLocationsTableExists ? 'status-success' : 'status-warning'; ?>">
                                <?php echo $userLocationsTableExists ? '✓ Ada' : '✗ Tidak Ada'; ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 mb-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title mb-3">
                            <i class="fas fa-columns me-2" style="color: #14b8a6;"></i>
                            Status Kolom
                        </h5>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>users.nomor_hp</span>
                            <span class="status-badge <?php echo $nomorHpExists ? 'status-success' : 'status-warning'; ?>">
                                <?php echo $nomorHpExists ? '✓ Ada' : '✗ Tidak Ada'; ?>
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>reports.whatsapp_number</span>
                            <span class="status-badge <?php echo $whatsappNumberExists ? 'status-success' : 'status-warning'; ?>">
                                <?php echo $whatsappNumberExists ? '✓ Ada' : '✗ Tidak Ada'; ?>
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>reports.additional_images</span>
                            <span class="status-badge <?php echo $additionalImagesExists ? 'status-success' : 'status-warning'; ?>">
                                <?php echo $additionalImagesExists ? '✓ Ada' : '✗ Tidak Ada'; ?>
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span>reports.tags</span>
                            <span class="status-badge <?php echo $tagsExists ? 'status-success' : 'status-warning'; ?>">
                                <?php echo $tagsExists ? '✓ Ada' : '✗ Tidak Ada'; ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm migration-card">
                    <div class="card-header" style="background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%); color: white; border: none;">
                        <h5 class="mb-0">
                            <i class="fas fa-cog me-2"></i>
                            Migration Actions
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($needsMigration): ?>
                        <div class="alert alert-warning mb-4">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Database memerlukan update!</strong> Beberapa tabel atau kolom masih belum ada.
                        </div>
                        
                        <form method="POST" onsubmit="return confirm('Yakin ingin menjalankan migration? Pastikan sudah backup database!');">
                            <button type="submit" name="run_migration" class="btn btn-primary btn-lg">
                                <i class="fas fa-play me-2"></i>
                                Jalankan Migration
                            </button>
                            <small class="d-block text-muted mt-2">
                                <i class="fas fa-info-circle me-1"></i>
                                Migration akan dijalankan dalam transaction untuk keamanan. Jika ada error, semua perubahan akan di-rollback.
                            </small>
                        </form>
                        <?php else: ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>
                            <strong>Database sudah up-to-date!</strong> Semua tabel dan kolom yang diperlukan sudah ada.
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        
        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>
                            Daftar Migration
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th style="width: 50px;">#</th>
                                        <th>Migration</th>
                                        <th style="width: 150px;">Status</th>
                                        <th style="width: 200px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>1</td>
                                        <td>
                                            <strong>Add nomor_hp to users</strong><br>
                                            <small class="text-muted">Menambahkan kolom nomor_hp ke tabel users</small>
                                        </td>
                                        <td>
                                            <span class="status-badge <?php echo $nomorHpExists ? 'status-success' : 'status-warning'; ?>">
                                                <?php echo $nomorHpExists ? '✓ Selesai' : '✗ Pending'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (!$nomorHpExists && $usersTableExists): ?>
                                            <code class="small">ALTER TABLE users ADD COLUMN nomor_hp VARCHAR(20) AFTER nama;</code>
                                            <?php else: ?>
                                            <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>2</td>
                                        <td>
                                            <strong>Add whatsapp_number to reports</strong><br>
                                            <small class="text-muted">Menambahkan kolom whatsapp_number ke tabel reports</small>
                                        </td>
                                        <td>
                                            <span class="status-badge <?php echo $whatsappNumberExists ? 'status-success' : 'status-warning'; ?>">
                                                <?php echo $whatsappNumberExists ? '✓ Selesai' : '✗ Pending'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (!$whatsappNumberExists && $reportsTableExists): ?>
                                            <code class="small">ALTER TABLE reports ADD COLUMN whatsapp_number varchar(20) AFTER alamat_lokasi;</code>
                                            <?php else: ?>
                                            <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>3</td>
                                        <td>
                                            <strong>Add additional_images to reports</strong><br>
                                            <small class="text-muted">Menambahkan kolom additional_images untuk gambar tambahan</small>
                                        </td>
                                        <td>
                                            <span class="status-badge <?php echo $additionalImagesExists ? 'status-success' : 'status-warning'; ?>">
                                                <?php echo $additionalImagesExists ? '✓ Selesai' : '✗ Pending'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (!$additionalImagesExists && $reportsTableExists): ?>
                                            <code class="small">ALTER TABLE reports ADD COLUMN additional_images TEXT AFTER gambar;</code>
                                            <?php else: ?>
                                            <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>4</td>
                                        <td>
                                            <strong>Add tags to reports</strong><br>
                                            <small class="text-muted">Menambahkan kolom tags untuk analitik</small>
                                        </td>
                                        <td>
                                            <span class="status-badge <?php echo $tagsExists ? 'status-success' : 'status-warning'; ?>">
                                                <?php echo $tagsExists ? '✓ Selesai' : '✗ Pending'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (!$tagsExists && $reportsTableExists): ?>
                                            <code class="small">ALTER TABLE reports ADD COLUMN tags VARCHAR(500) AFTER correction_note;</code>
                                            <?php else: ?>
                                            <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>5</td>
                                        <td>
                                            <strong>Create user_locations table</strong><br>
                                            <small class="text-muted">Membuat tabel untuk menyimpan lokasi user</small>
                                        </td>
                                        <td>
                                            <span class="status-badge <?php echo $userLocationsTableExists ? 'status-success' : 'status-warning'; ?>">
                                                <?php echo $userLocationsTableExists ? '✓ Selesai' : '✗ Pending'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (!$userLocationsTableExists): ?>
                                            <code class="small">CREATE TABLE user_locations (...)</code>
                                            <?php else: ?>
                                            <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>6</td>
                                        <td>
                                            <strong>Create report_comments table</strong><br>
                                            <small class="text-muted">Membuat tabel untuk komentar admin pada laporan</small>
                                        </td>
                                        <td>
                                            <span class="status-badge <?php echo $commentsTableExists ? 'status-success' : 'status-warning'; ?>">
                                                <?php echo $commentsTableExists ? '✓ Selesai' : '✗ Pending'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (!$commentsTableExists): ?>
                                            <code class="small">CREATE TABLE report_comments (...)</code>
                                            <?php else: ?>
                                            <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        
        <div class="row mt-4">
            <div class="col-12">
                <div class="alert alert-info">
                    <h6 class="alert-heading">
                        <i class="fas fa-lightbulb me-2"></i>
                        Catatan Penting
                    </h6>
                    <ul class="mb-0">
                        <li>Migration menggunakan <strong>transaction</strong> untuk keamanan. Jika ada error, semua perubahan akan di-rollback.</li>
                        <li>Sistem juga memiliki <strong>auto-fix</strong> di classes (Report.php, Comment.php) yang otomatis membuat tabel/kolom jika belum ada.</li>
                        <li>Disarankan untuk <strong>backup database</strong> sebelum menjalankan migration manual.</li>
                        <li>Jika semua status sudah "✓ Ada", berarti database sudah up-to-date dan tidak perlu migration.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
