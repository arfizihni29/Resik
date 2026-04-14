<?php
require_once '../config/config.php';
require_once '../config/Database.php';

checkLogin();
checkAdmin();

$currentPage = 'backup'; 

$database = new Database();
$db = $database->getConnection();


function backupDatabase($db) {
    $backup = "-- Database Backup\n";
    $backup .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    $backup .= "-- Database: pelaporan_sampah\n\n";
    $backup .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
    $backup .= "SET time_zone = \"+00:00\";\n\n";
    

    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($tables as $table) {

        $backup .= "-- --------------------------------------------------------\n";
        $backup .= "-- Table structure for table `$table`\n";
        $backup .= "-- --------------------------------------------------------\n\n";
        
        $stmt = $db->query("SHOW CREATE TABLE `$table`");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $backup .= "DROP TABLE IF EXISTS `$table`;\n";
        $backup .= $row['Create Table'] . ";\n\n";
        

        $backup .= "-- Dumping data for table `$table`\n\n";
        $stmt = $db->query("SELECT * FROM `$table`");
        $rowCount = $stmt->rowCount();
        
        if ($rowCount > 0) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $columns = array_keys($row);
                $values = array_values($row);
                

                $escapedValues = array_map(function($value) use ($db) {
                    if ($value === null) {
                        return 'NULL';
                    }
                    return $db->quote($value);
                }, $values);
                
                $backup .= "INSERT INTO `$table` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $escapedValues) . ");\n";
            }
        }
        
        $backup .= "\n";
    }
    
    return $backup;
}


if (isset($_GET['download'])) {
    try {
        $backup = backupDatabase($db);
        $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
        
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($backup));
        
        echo $backup;
        exit;
    } catch (Exception $e) {
        $_SESSION['backup_error'] = $e->getMessage();
        header('Location: backup.php');
        exit;
    }
}


try {
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $stats = [];
    $totalRows = 0;
    $totalSize = 0;
    
    foreach ($tables as $table) {
        $stmt = $db->query("SELECT COUNT(*) as count FROM `$table`");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        $stmt = $db->query("SHOW TABLE STATUS LIKE '$table'");
        $status = $stmt->fetch(PDO::FETCH_ASSOC);
        $size = $status['Data_length'] + $status['Index_length'];
        
        $stats[] = [
            'name' => $table,
            'rows' => $count,
            'size' => $size
        ];
        
        $totalRows += $count;
        $totalSize += $size;
    }
} catch (PDOException $e) {
    $stats = [];
    $totalRows = 0;
    $totalSize = 0;
}

function formatBytes($bytes) {
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    }
    return $bytes . ' bytes';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Backup - Admin</title>
    
    
    <link rel="icon" type="image/svg+xml" href="../favicon.svg">
    <link rel="alternate icon" href="../favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="../favicon.svg">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
            --secondary-gradient: linear-gradient(135deg, #f0fdfa 0%, #ccfbf1 100%);
            --glass-bg: rgba(255, 255, 255, 0.9);
            --glass-border: 1px solid rgba(255, 255, 255, 0.5);
            --glass-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f0fdfa;
            min-height: 100vh;
            color: #1e293b;
            position: relative;
            overflow-x: hidden;
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

        /* Glass Cards */
        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(12px);
            border: var(--glass-border);
            border-radius: 20px;
            box-shadow: var(--glass-shadow);
            transition: all 0.3s ease;
        }

        .glass-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        /* Page Header */
        .page-header {
            background: var(--primary-gradient);
            color: white;
            padding: 2.5rem 2rem;
            border-radius: 24px;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(20, 184, 166, 0.2);
        }

        .page-header::after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.1'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            opacity: 0.1;
        }

        .header-title {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            letter-spacing: -0.025em;
        }

        .header-subtitle {
            opacity: 0.9;
            font-size: 1.1rem;
            font-weight: 400;
        }

        /* Stats Cards */
        .stat-card {
            padding: 1.5rem;
            border-radius: 20px;
            color: white;
            height: 100%;
            position: relative;
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .stat-card:hover {
            transform: translateY(-5px) scale(1.02);
            filter: brightness(1.1);
        }

        .stat-bg-primary { background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%); }
        .stat-bg-warning { background: linear-gradient(135deg, #f59e0b 0%, #ea580c 100%); }
        .stat-bg-info { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); }

        .stat-icon {
            font-size: 2.5rem;
            opacity: 0.8;
            margin-bottom: 1rem;
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 800;
            line-height: 1;
        }

        .stat-label {
            font-size: 0.95rem;
            font-weight: 500;
            opacity: 0.9;
        }

        /* Action Card */
        .action-card {
            padding: 2.5rem;
            text-align: center;
        }

        .download-btn {
            background: var(--primary-gradient);
            color: white;
            border: none;
            padding: 1.25rem 2.5rem;
            border-radius: 16px;
            font-size: 1.1rem;
            font-weight: 700;
            width: 100%;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px -1px rgba(20, 184, 166, 0.3);
        }

        .download-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px -3px rgba(20, 184, 166, 0.4);
            color: white;
        }

        .download-btn i {
            margin-right: 0.75rem;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-3px); }
            60% { transform: translateY(-2px); }
        }

        /* Modern Table */
        .modern-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 0.75rem;
        }

        .modern-table tr {
            background: white;
            border-radius: 12px;
            box-shadow: var(--glass-shadow);
            transition: all 0.2s ease;
        }

        .modern-table tr:hover {
            transform: scale(1.01);
            box-shadow: 0 8px 12px -3px rgba(0, 0, 0, 0.1);
        }

        .modern-table td {
            padding: 1.25rem;
            vertical-align: middle;
            border: none;
        }

        .modern-table td:first-child {
            border-top-left-radius: 12px;
            border-bottom-left-radius: 12px;
        }

        .modern-table td:last-child {
            border-top-right-radius: 12px;
            border-bottom-right-radius: 12px;
        }

        .table-icon {
            width: 40px;
            height: 40px;
            background: #f0fdfa;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #14b8a6;
            font-size: 1.2rem;
        }

        /* Tips */
        .tips-container {
            border-left: 4px solid #14b8a6;
            background: #f0fdfa;
            padding: 1.5rem;
            border-radius: 0 12px 12px 0;
            margin-top: 2rem;
        }

        .tips-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .tips-list li {
            margin-bottom: 1rem;
            display: flex;
            align-items: start;
        }

        .tips-list li i {
            color: #14b8a6;
            margin-right: 0.75rem;
            margin-top: 0.25rem;
        }

        .navbar {
            backdrop-filter: blur(10px);
            background: rgba(20, 184, 166, 0.95);
        }
    </style>
</head>
<body>
    
    <?php include 'includes/navbar.php'; ?>

    <div class="container py-5">
        
        <div class="page-header fade-in">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h1 class="header-title">
                        <i class="fas fa-database me-2"></i>Database Backup
                    </h1>
                    <p class="header-subtitle mb-0">
                        Amankan data sistem pelaporan sampah dengan melakukan backup rutin.
                    </p>
                </div>
                <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                    <div class="d-inline-block bg-white/20 backdrop-blur-sm px-4 py-2 rounded-full border border-white/30">
                        <i class="fas fa-server me-2"></i>
                        <span>System Status: <strong>Online</strong></span>
                    </div>
                </div>
            </div>
        </div>

        <?php if (isset($_SESSION['backup_error'])): ?>
        <div class="alert alert-danger shadow-sm border-0 rounded-3 mb-4 d-flex align-items-center">
            <i class="fas fa-exclamation-triangle fs-4 me-3"></i>
            <div>
                <strong>Backup Failed!</strong><br>
                <?php echo $_SESSION['backup_error']; unset($_SESSION['backup_error']); ?>
            </div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        
        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="stat-card stat-bg-primary">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="stat-icon mb-0"><i class="fas fa-table"></i></div>
                        <span class="badge bg-white/20 text-white border border-white/30">Structure</span>
                    </div>
                    <div class="stat-value"><?php echo count($stats); ?></div>
                    <div class="stat-label">Total Tables</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card stat-bg-warning">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="stat-icon mb-0"><i class="fas fa-layer-group"></i></div>
                        <span class="badge bg-white/20 text-white border border-white/30">Content</span>
                    </div>
                    <div class="stat-value"><?php echo number_format($totalRows); ?></div>
                    <div class="stat-label">Total Records</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card stat-bg-info">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="stat-icon mb-0"><i class="fas fa-hdd"></i></div>
                        <span class="badge bg-white/20 text-white border border-white/30">Storage</span>
                    </div>
                    <div class="stat-value"><?php echo formatBytes($totalSize); ?></div>
                    <div class="stat-label">Database Size</div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            
            <div class="col-lg-5">
                <div class="glass-card action-card mb-4">
                    <div class="mb-4">
                        <div style="width: 80px; height: 80px; background: #ccfbf1; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto; color: #0d9488; font-size: 2.5rem;">
                            <i class="fas fa-cloud-download-alt"></i>
                        </div>
                    </div>
                    
                    <h3 class="fw-bold text-gray-800 mb-3">Download Backup</h3>
                    <p class="text-muted mb-4">
                        Unduh file SQL lengkap yang berisi seluruh struktur dan data database. File ini dapat digunakan untuk pemulihan sistem.
                    </p>
                    
                    <a href="backup.php?download=1" class="download-btn text-decoration-none d-block">
                        <i class="fas fa-download"></i> Download .SQL File
                    </a>

                    <p class="text-muted small mt-3 mb-0">
                        <i class="fas fa-info-circle me-1"></i> Estimasi ukuran file: <strong><?php echo formatBytes($totalSize); ?></strong>
                    </p>
                </div>

                <div class="glass-card p-4">
                    <h5 class="fw-bold mb-3"><i class="fas fa-shield-alt text-primary me-2"></i>Best Practices</h5>
                    <div class="tips-container mt-0">
                        <ul class="tips-list">
                            <li>
                                <i class="fas fa-check-circle"></i>
                                <span>Lakukan backup secara berkala (minimal seminggu sekali).</span>
                            </li>
                            <li>
                                <i class="fas fa-check-circle"></i>
                                <span>Simpan file backup di lokasi yang aman (cloud storage/hard drive eksternal).</span>
                            </li>
                            <li>
                                <i class="fas fa-check-circle"></i>
                                <span>Jangan berikan file backup kepada pihak yang tidak berwenang karena berisi data sensitif.</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            
            <div class="col-lg-7">
                <div class="glass-card p-4">
                    <h4 class="fw-bold mb-4 d-flex align-items-center">
                        <i class="fas fa-list text-primary me-2"></i> Detail Table
                    </h4>
                    
                    <div class="table-responsive">
                        <table class="modern-table">
                            <tbody>
                                <?php foreach ($stats as $table): ?>
                                <tr>
                                    <td width="60">
                                        <div class="table-icon">
                                            <i class="fas fa-database"></i>
                                        </div>
                                    </td>
                                    <td>
                                        <h6 class="mb-0 fw-bold"><?php echo $table['name']; ?></h6>
                                        <small class="text-muted">Table Structure & Data</small>
                                    </td>
                                    <td class="text-center">
                                        <div class="badge bg-light text-dark border">
                                            <?php echo number_format($table['rows']); ?> rows
                                        </div>
                                    </td>
                                    <td class="text-end fw-bold text-primary">
                                        <?php echo formatBytes($table['size']); ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>

        document.addEventListener('DOMContentLoaded', function() {
            const elements = document.querySelectorAll('.glass-card, .stat-card');
            elements.forEach((el, index) => {
                el.style.opacity = '0';
                el.style.transform = 'translateY(20px)';
                el.style.transition = 'all 0.5s ease-out';
                
                setTimeout(() => {
                    el.style.opacity = '1';
                    el.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>
