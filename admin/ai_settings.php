<?php
require_once '../config/config.php';
require_once '../config/Database.php';
require_once '../classes/User.php';




if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$pageTitle = "Pengaturan AI";
$configFile = '../config/api_keys.json';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (isset($_POST['new_key'])) {
        $key = trim($_POST['new_key']);
        if (!empty($key)) {
            $jsonData = file_exists($configFile) ? file_get_contents($configFile) : '[]';
            $data = json_decode($jsonData, true) ?: [];
            
            
            $exists = false;
            foreach ($data as $item) {
                if ($item['key'] === $key) {
                    $exists = true;
                    break;
                }
            }
            
            if (!$exists) {
                $data[] = [
                    'key' => $key,
                    'status' => 'active',
                    'last_used' => 0,
                    'error_count' => 0,
                    'limit_reset_at' => 0
                ];
                file_put_contents($configFile, json_encode($data, JSON_PRETTY_PRINT));
                $successMsg = "API Key berhasil ditambahkan!";
            } else {
                $errorMsg = "API Key sudah ada!";
            }
        }
    }
    
    
    if (isset($_POST['delete_key'])) {
        $keyToDelete = $_POST['delete_key'];
        $jsonData = file_exists($configFile) ? file_get_contents($configFile) : '[]';
        $data = json_decode($jsonData, true) ?: [];
        $newData = [];
        foreach ($data as $item) {
            if ($item['key'] !== $keyToDelete) {
                $newData[] = $item;
            }
        }
        file_put_contents($configFile, json_encode($newData, JSON_PRETTY_PRINT));
        $successMsg = "API Key dihapus.";
    }
    
    
    if (isset($_POST['reset_key'])) {
        $keyToReset = $_POST['reset_key'];
        $jsonData = file_exists($configFile) ? file_get_contents($configFile) : '[]';
        $data = json_decode($jsonData, true) ?: [];
        foreach ($data as &$item) {
            if ($item['key'] === $keyToReset) {
                $item['status'] = 'active';
                $item['error_count'] = 0;
                $item['limit_reset_at'] = 0;
            }
        }
        file_put_contents($configFile, json_encode($data, JSON_PRETTY_PRINT));
        $successMsg = "Status Key di-reset jadi Active.";
    }
}


$jsonData = file_exists($configFile) ? file_get_contents($configFile) : '[]';
$apiKeys = json_decode($jsonData, true) ?: [];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Admin Panel</title>
    
    
    <link rel="icon" type="image/svg+xml" href="../favicon.svg">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    
    <style>
        body { background-color: #f8f9fa; }
        .key-card { transition: all 0.2s; }
        .key-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .status-active { border-left: 4px solid #10b981; }
        .status-limited { border-left: 4px solid #ef4444; background-color: #fef2f2; }
    </style>
</head>
<body>
    
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container py-5">
                <h2 class="mb-4">🤖 Manajemen API Gemini</h2>
                
                <?php if (isset($successMsg)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $successMsg; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <div class="row">
                    
                    <div class="col-md-4 mb-4">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-white fw-bold">
                                <i class="fas fa-plus-circle text-primary"></i> Tambah Key Baru
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="mb-3">
                                        <label class="form-label">Gemini API Key</label>
                                        <input type="text" name="new_key" class="form-control" placeholder="AIzaSy..." required>
                                        <div class="form-text">Dapatkan di aistudio.google.com</div>
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100">Simpan Key</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    
                    <div class="col-md-8">
                        <div class="card shadow-sm">
                            <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-list"></i> Daftar API Key (<?php echo count($apiKeys); ?>)</span>
                                <span class="badge bg-info text-dark">Auto-Rotation Active</span>
                            </div>
                            <div class="card-body p-0">
                                <div class="list-group list-group-flush">
                                    <?php foreach ($apiKeys as $k): ?>
                                        <div class="list-group-item p-3 key-card <?php echo $k['status'] === 'active' ? 'status-active' : 'status-limited'; ?>">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <div class="fw-bold font-monospace text-dark">
                                                        <?php echo substr($k['key'], 0, 8) . '...' . substr($k['key'], -6); ?>
                                                        
                                                        <?php if ($k['status'] === 'active'): ?>
                                                            <span class="badge bg-success ms-2">Active</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-danger ms-2">Rate Limited (429)</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="small text-muted mt-1">
                                                        Errors: <?php echo $k['error_count']; ?> 
                                                        <?php if($k['last_used'] > 0): ?>
                                                            | Last Used: <?php echo date('H:i:s', $k['last_used']); ?>
                                                        <?php endif; ?>
                                                        <?php if($k['limit_reset_at'] > time()): ?>
                                                            | Cooldown: <?php echo ($k['limit_reset_at'] - time()); ?>s left
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <div class="d-flex gap-2">
                                                    <?php if ($k['status'] !== 'active'): ?>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="reset_key" value="<?php echo $k['key']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-warning" title="Reset Status">
                                                                <i class="fas fa-sync-alt"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                    
                                                    <form method="POST" class="d-inline" onsubmit="return confirm('Hapus key ini?');">
                                                        <input type="hidden" name="delete_key" value="<?php echo $k['key']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
            
        </div> 
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>

        document.getElementById("sidebarToggle").addEventListener("click", function(e) {
            e.preventDefault();
            document.body.classList.toggle("sb-sidenav-toggled");
        });
    </script>
</body>
</html>
