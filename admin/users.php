<?php
require_once '../config/config.php';
require_once '../config/Database.php';
require_once '../classes/User.php';
require_once '../classes/Report.php';

checkLogin();
checkAdmin();

$currentPage = 'users';

$database = new Database();
$db = $database->getConnection();
$user = new User($db);
$report = new Report($db);


if (isset($_GET['delete'])) {
    $deleteId = $_GET['delete'];
    if ($deleteId == $_SESSION['user_id']) {
        $_SESSION['error'] = 'Tidak dapat menghapus akun sendiri!';
    } else {
        $query = "DELETE FROM users WHERE id = :id AND id != :current_user";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $deleteId);
        $stmt->bindParam(':current_user', $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = 'User berhasil dihapus!';
        } else {
            $_SESSION['error'] = 'Gagal menghapus user.';
        }
    }
    header('Location: users.php');
    exit;
}


if (isset($_POST['update_role'])) {
    $userId = $_POST['user_id'];
    $newRole = $_POST['role'];
    
    if ($userId == $_SESSION['user_id']) {
        $_SESSION['error'] = 'Tidak dapat mengubah role akun sendiri!';
    } else {
        $query = "UPDATE users SET role = :role WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':role', $newRole);
        $stmt->bindParam(':id', $userId);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = 'Role user berhasil diupdate!';
        } else {
            $_SESSION['error'] = 'Gagal mengupdate role.';
        }
    }
    header('Location: users.php');
    exit;
}


$allUsers = $user->getAllUsers();
foreach ($allUsers as &$u) {
    $userReports = $report->getByUserId($u['id']);
    $u['report_count'] = count($userReports);
}
unset($u);


$totalUsers = count($allUsers);
$totalAdmins = count(array_filter($allUsers, fn($u) => $u['role'] == 'admin'));
$totalRegularUsers = count(array_filter($allUsers, fn($u) => $u['role'] == 'user'));
$totalGoogleUsers = count(array_filter($allUsers, fn($u) => !empty($u['google_id'])));
$totalReports = array_sum(array_column($allUsers, 'report_count'));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin</title>
    
    
    <link rel="icon" type="image/svg+xml" href="../favicon.svg">
    <link rel="alternate icon" href="../favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="../favicon.svg">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        @media (max-width: 767px) {
            .desktop-table {
                display: none !important;
            }
        }
        @media (min-width: 768px) {
            .mobile-cards {
                display: none !important;
            }
        }
        .user-card-mobile {
            background: white;
            border-radius: 16px;
            padding: 16px;
            margin-bottom: 16px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }
        .user-header-mobile {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px solid #e5e7eb;
        }
        .user-info-mobile {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 12px;
        }
        .user-info-row-mobile {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.875rem;
            color: #6b7280;
        }
        .user-info-row-mobile i {
            width: 20px;
            color: #14b8a6;
        }
        .user-actions-mobile {
            display: flex;
            gap: 8px;
            margin-top: 12px;
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
        .filter-chip:hover,
        .filter-chip.active {
            border-color: #14b8a6;
            background: #14b8a6;
            color: white;
        }
        .user-row:hover {
            background-color: #f8f9fa !important;
            transform: scale(1.01);
            transition: all 0.2s ease;
        }
        .table-hover tbody tr.user-row:hover {
            background-color: #f8f9fa !important;
        }
        
        /* Search Highlight */
        .highlight {
            background-color: #fff3cd;
            padding: 2px 4px;
            border-radius: 3px;
            font-weight: 600;
        }
        
        /* Search Results Counter */
        .search-results-counter {
            padding: 8px 16px;
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
        
        /* Empty State */
        .empty-search-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #6b7280;
        }
        
        .empty-search-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.3;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container-fluid mt-4 mb-5">
            
            <div class="row align-items-center mb-4">
                <div class="col-md-8">
                <h2 class="mb-2" style="color: #14b8a6; font-weight: 700;">
                <i class="fas fa-users"></i> Manage Users
            </h2>
                    <p class="text-muted mb-0 d-none d-md-block">
                        <i class="fas fa-user-cog me-2"></i>Manajemen pengguna aplikasi
                    </p>
                </div>
            </div>

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

            
            <div class="row mb-4">
                <div class="col-6 col-md-3 mb-3">
                <div class="stat-card mobile-card">
                        <div class="stat-icon"><i class="fas fa-users"></i></div>
                    <h2><?php echo $totalUsers; ?></h2>
                        <p class="stat-label">Total Users</p>
                    </div>
                </div>
                <div class="col-6 col-md-3 mb-3">
                <div class="stat-card stat-card-info mobile-card">
                        <div class="stat-icon"><i class="fas fa-user-shield"></i></div>
                    <h2><?php echo $totalAdmins; ?></h2>
                        <p class="stat-label">Admins</p>
                    </div>
                </div>
                <div class="col-6 col-md-3 mb-3">
                <div class="stat-card stat-card-success mobile-card">
                        <div class="stat-icon"><i class="fas fa-user"></i></div>
                    <h2><?php echo $totalRegularUsers; ?></h2>
                        <p class="stat-label">Regular Users</p>
                    </div>
                </div>
                <div class="col-6 col-md-3 mb-3">
                    <div class="stat-card stat-card-warning mobile-card">
                        <div class="stat-icon"><i class="fas fa-file-alt"></i></div>
                    <h2><?php echo $totalReports; ?></h2>
                        <p class="stat-label">Total Reports</p>
                    </div>
                </div>
            </div>

            
            <?php if ($totalGoogleUsers > 0): ?>
            <div class="row mb-4">
                <div class="col-12">
                    <div class="alert alert-info">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <i class="fab fa-google me-2"></i>
                                <strong>Google OAuth Users:</strong> 
                                <span class="badge bg-warning text-dark"><?php echo $totalGoogleUsers; ?> user</span>
                                <small class="text-muted ms-2">(<?php echo round(($totalGoogleUsers / $totalUsers) * 100, 1); ?>% dari total users)</small>
                            </div>
                            <div>
                                <small class="text-muted">
                                    <i class="fas fa-info-circle"></i> User yang registrasi menggunakan akun Google
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            
        <div class="card mb-4">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-6 mb-2 mb-md-0">
                            <label class="form-label fw-bold mb-2">
                                <i class="fas fa-search text-primary"></i> Cari User
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-white">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input 
                                    type="text" 
                                    class="form-control form-control-lg" 
                                    id="userSearchInput" 
                                    placeholder="Cari berdasarkan nama, username, email, atau nomor HP..."
                                    onkeyup="searchUsers(this.value)"
                                    autocomplete="off"
                                >
                                <button 
                                    class="btn btn-outline-secondary" 
                                    type="button" 
                                    onclick="clearSearch()"
                                    id="clearSearchBtn"
                                    style="display: none;"
                                >
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <small class="text-muted mt-2 d-block">
                                <i class="fas fa-info-circle"></i> Ketik untuk mencari user berdasarkan nama, username, email, atau nomor HP
                            </small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold mb-2">
                                <i class="fas fa-filter text-info"></i> Filter
                            </label>
                    <div class="d-flex flex-wrap gap-2">
                    <div class="filter-chip active" onclick="setFilter('all')">
                        <i class="fas fa-th"></i> Semua
                        </div>
                    <div class="filter-chip" onclick="setFilter('admin')">
                        <i class="fas fa-user-shield"></i> Admin
                        </div>
                    <div class="filter-chip" onclick="setFilter('user')">
                        <i class="fas fa-user"></i> User
                                </div>
                                <div class="filter-chip" onclick="setFilter('google')">
                                    <i class="fab fa-google"></i> Google
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        
        <div class="card desktop-table">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                <i class="fas fa-table"></i> Daftar Users
                </div>
                <div>
                    <small class="text-muted">
                        Total: <strong><?php echo $totalUsers; ?></strong> users
                    </small>
                </div>
                        </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th><i class="fas fa-id-badge"></i> ID</th>
                                <th><i class="fas fa-at"></i> Username</th>
                                <th><i class="fas fa-user"></i> Nama</th>
                                <th><i class="fas fa-phone"></i> Nomor HP</th>
                                <th><i class="fas fa-map-marker-alt"></i> Alamat</th>
                                <th><i class="fas fa-user-tag"></i> Role</th>
                                <th class="text-center"><i class="fas fa-file-alt"></i> Total Laporan</th>
                                <th><i class="fas fa-calendar"></i> Tanggal Daftar</th>
                                <th class="text-center"><i class="fas fa-cog"></i> Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allUsers as $u): ?>
                            <?php 

                                $u['latitude'] = $u['latitude'] ?? null;
                                $u['longitude'] = $u['longitude'] ?? null;
                            ?>
                            <tr class="user-row" data-role="<?php echo $u['role']; ?>" data-google="<?php echo !empty($u['google_id']) ? 'yes' : 'no'; ?>" data-user='<?php echo json_encode($u, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>' style="cursor: pointer;">
                                <td><span class="badge bg-secondary">#<?php echo $u['id']; ?></span></td>
                                <td>
                                    <strong style="color: #14b8a6;">@<?php echo htmlspecialchars($u['username']); ?></strong>
                                    <?php if (!empty($u['google_id'])): ?>
                                        <span class="badge bg-warning text-dark ms-2" title="Registrasi via Google OAuth">
                                            <i class="fab fa-google"></i> Google
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($u['nama']); ?></td>
                                <td>
                                    <?php if (!empty($u['nomor_hp'])): ?>
                                    <span style="color: #6b7280;">
                                        <i class="fas fa-phone-alt text-success me-1"></i><?php echo htmlspecialchars($u['nomor_hp']); ?>
                                    </span>
                                    <?php else: ?>
                                    <span class="text-muted">-</span>
                                    <?php endif; ?>
                                    <?php if (!empty($u['email'])): ?>
                                    <br><small class="text-muted">
                                        <i class="fas fa-envelope text-info me-1"></i><?php echo htmlspecialchars($u['email']); ?>
                                    </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($u['alamat'])): ?>
                                    <small class="text-muted" style="display: block; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo htmlspecialchars($u['alamat']); ?>">
                                        <i class="fas fa-map-marker-alt text-danger me-1"></i><?php echo htmlspecialchars(mb_substr($u['alamat'], 0, 30)) . (mb_strlen($u['alamat']) > 30 ? '...' : ''); ?>
                                    </small>
                                    <?php else: ?>
                                    <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?php echo $u['role'] === 'admin' ? 'bg-danger' : 'bg-primary'; ?>">
                                        <i class="fas fa-<?php echo $u['role'] === 'admin' ? 'user-shield' : 'user'; ?>"></i>
                                        <?php echo strtoupper($u['role']); ?>
                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-info">
                                        <i class="fas fa-file-alt"></i> <?php echo $u['report_count']; ?>
                    </span>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <i class="fas fa-calendar-alt me-1"></i>
                                        <?php echo date('d M Y', strtotime($u['created_at'])); ?>
                    </small>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group" role="group">
                                        <button class="btn btn-sm btn-outline-primary" onclick="event.stopPropagation(); showUserModal(<?php echo htmlspecialchars(json_encode($u), ENT_QUOTES); ?>)" title="Lihat Detail">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                        <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                        <button class="btn btn-sm btn-outline-warning" onclick="event.stopPropagation(); showRoleModal(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars($u['nama'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($u['username'], ENT_QUOTES); ?>', '<?php echo $u['role']; ?>')" title="Ubah Role">
                                                <i class="fas fa-user-tag"></i>
                                            </button>
                                        <button class="btn btn-sm btn-outline-danger" onclick="event.stopPropagation(); confirmDelete(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars($u['nama'], ENT_QUOTES); ?>')" title="Hapus User">
                                                        <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
                    </div>

        
        <div class="mobile-cards">
            <?php foreach ($allUsers as $u): ?>
            <?php 

                $u['latitude'] = $u['latitude'] ?? null;
                $u['longitude'] = $u['longitude'] ?? null;
            ?>
            <div class="user-card-mobile" data-user='<?php echo json_encode($u, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>'>
                <div class="user-header-mobile">
                    <div>
                        <h6 class="mb-1" style="font-weight: 700;"><?php echo htmlspecialchars($u['nama']); ?></h6>
                        <p class="mb-0" style="color: #6b7280; font-size: 0.875rem;">
                            @<?php echo htmlspecialchars($u['username']); ?>
                            <?php if (!empty($u['google_id'])): ?>
                                <span class="badge bg-warning text-dark ms-1" style="font-size: 0.65rem;" title="Registrasi via Google OAuth">
                                    <i class="fab fa-google"></i> Google
                                </span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div>
                        <span class="badge <?php echo $u['role'] === 'admin' ? 'bg-danger' : 'bg-primary'; ?>" style="font-size: 0.75rem;">
                            <?php echo strtoupper($u['role']); ?>
                        </span>
                    </div>
                </div>
                <div class="user-info-mobile">
                    <div class="user-info-row-mobile">
                        <i class="fas fa-id-badge"></i>
                        <span>ID: #<?php echo $u['id']; ?></span>
                    </div>
                    <?php if (!empty($u['nomor_hp'])): ?>
                    <div class="user-info-row-mobile">
                        <i class="fas fa-phone"></i>
                        <span><?php echo htmlspecialchars($u['nomor_hp']); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($u['email'])): ?>
                    <div class="user-info-row-mobile">
                        <i class="fas fa-envelope text-info"></i>
                        <span><?php echo htmlspecialchars($u['email']); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($u['alamat'])): ?>
                    <div class="user-info-row-mobile">
                        <i class="fas fa-map-marker-alt"></i>
                        <span style="max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; display: inline-block;" title="<?php echo htmlspecialchars($u['alamat']); ?>">
                            <?php echo htmlspecialchars(mb_substr($u['alamat'], 0, 40)) . (mb_strlen($u['alamat']) > 40 ? '...' : ''); ?>
                        </span>
                    </div>
                    <?php endif; ?>
                    <div class="user-info-row-mobile">
                        <i class="fas fa-file-alt"></i>
                        <span><?php echo $u['report_count']; ?> laporan</span>
                    </div>
                    <div class="user-info-row-mobile">
                        <i class="fas fa-calendar"></i>
                        <span><?php echo date('d M Y', strtotime($u['created_at'])); ?></span>
                    </div>
                </div>
                <div class="d-flex gap-2 mt-3">
                    <button class="btn btn-sm btn-outline-primary flex-fill" onclick="event.stopPropagation(); showUserModal(<?php echo htmlspecialchars(json_encode($u), ENT_QUOTES); ?>)" title="Lihat Detail">
                        <i class="fas fa-eye"></i> Detail
                    </button>
                    <?php if ($u['id'] != $_SESSION['user_id']): ?>
                    <button class="btn btn-sm btn-outline-warning" onclick="event.stopPropagation(); showRoleModal(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars($u['nama'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($u['username'], ENT_QUOTES); ?>', '<?php echo $u['role']; ?>')" title="Ubah Role">
                        <i class="fas fa-user-tag"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="event.stopPropagation(); confirmDelete(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars($u['nama'], ENT_QUOTES); ?>')" title="Hapus User">
                        <i class="fas fa-trash"></i>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    
    <div class="modal fade" id="userModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #14b8a6, #0d9488); color: white;">
                    <h5 class="modal-title"><i class="fas fa-user-circle"></i> Detail User</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="userModalBody" style="padding: 2rem;"></div>
            </div>
        </div>
    </div>

    
    <div class="modal fade" id="roleModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-tag"></i> Update Role User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="roleForm">
                    <div class="modal-body">
                        <input type="hidden" name="user_id" id="roleUserId">
                        <input type="hidden" name="update_role" value="1">
                        <div class="mb-3">
                            <label class="form-label fw-bold">User</label>
                            <p class="text-muted" id="roleUserName"></p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold"><i class="fas fa-user-tag"></i> Pilih Role</label>
                            <select name="role" id="roleSelect" class="form-select form-select-lg" required>
                                <option value="user">👤 User - Pengguna Biasa</option>
                                <option value="admin">👑 Admin - Administrator</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i> Batal
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    
    <div class="modal fade" id="locationModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #14b8a6, #0d9488); color: white;">
                    <h5 class="modal-title"><i class="fas fa-map-marker-alt"></i> Lokasi User</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="userLocationMap" style="height: 400px; border-radius: 12px;"></div>
                </div>
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
        let locationMap = null;
        let allUsers = <?php echo json_encode($allUsers); ?>;
        let currentFilter = 'all';
        let currentSearch = '';


        function searchUsers(searchTerm) {
            currentSearch = searchTerm.toLowerCase().trim();
            

            const clearBtn = document.getElementById('clearSearchBtn');
            if (currentSearch.length > 0) {
                clearBtn.style.display = '';
            } else {
                clearBtn.style.display = 'none';
            }
            

            applyFilters();
        }
        

        function clearSearch() {
            document.getElementById('userSearchInput').value = '';
            currentSearch = '';
            document.getElementById('clearSearchBtn').style.display = 'none';
            applyFilters();
        }
        

        function applyFilters() {
            let visibleCount = 0;
            

            document.querySelectorAll('.user-row').forEach(row => {
                const userData = JSON.parse(row.getAttribute('data-user') || '{}');
                const role = row.getAttribute('data-role');
                const isGoogle = row.getAttribute('data-google') === 'yes';
                

                let passesFilter = false;
                if (currentFilter === 'all') {
                    passesFilter = true;
                } else if (currentFilter === 'admin' && role === 'admin') {
                    passesFilter = true;
                } else if (currentFilter === 'user' && role === 'user') {
                    passesFilter = true;
                } else if (currentFilter === 'google' && isGoogle) {
                    passesFilter = true;
                }
                

                let passesSearch = true;
                if (currentSearch.length > 0) {
                    const searchableText = (
                        (userData.nama || '').toLowerCase() +
                        ' ' + (userData.username || '').toLowerCase() +
                        ' ' + (userData.email || '').toLowerCase() +
                        ' ' + (userData.nomor_hp || '').toLowerCase() +
                        ' ' + (userData.alamat || '').toLowerCase() +
                        ' ' + (userData.id || '').toString()
                    );
                    passesSearch = searchableText.includes(currentSearch);
                }
                
                if (passesFilter && passesSearch) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            

            document.querySelectorAll('.user-card-mobile').forEach(card => {
                const userData = JSON.parse(card.getAttribute('data-user') || '{}');
                const role = userData.role || '';
                const isGoogle = userData.google_id ? true : false;
                

                let passesFilter = false;
                if (currentFilter === 'all') {
                    passesFilter = true;
                } else if (currentFilter === 'admin' && role === 'admin') {
                    passesFilter = true;
                } else if (currentFilter === 'user' && role === 'user') {
                    passesFilter = true;
                } else if (currentFilter === 'google' && isGoogle) {
                    passesFilter = true;
                }
                

                let passesSearch = true;
                if (currentSearch.length > 0) {
                    const searchableText = (
                        (userData.nama || '').toLowerCase() +
                        ' ' + (userData.username || '').toLowerCase() +
                        ' ' + (userData.email || '').toLowerCase() +
                        ' ' + (userData.nomor_hp || '').toLowerCase() +
                        ' ' + (userData.alamat || '').toLowerCase() +
                        ' ' + (userData.id || '').toString()
                    );
                    passesSearch = searchableText.includes(currentSearch);
                }
                
                if (passesFilter && passesSearch) {
                    card.style.display = '';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });
            

            updateSearchResultsCounter(visibleCount);
        }

        function setFilter(filter) {
            currentFilter = filter;

            document.querySelectorAll('.filter-chip').forEach(chip => chip.classList.remove('active'));
            event.target.closest('.filter-chip').classList.add('active');
            

            applyFilters();
        }
        

        function updateSearchResultsCounter(count) {

            const existingCounter = document.querySelector('.search-results-counter');
            if (existingCounter) {
                existingCounter.remove();
            }
            

            const existingEmpty = document.querySelector('.empty-search-state');
            if (existingEmpty) {
                existingEmpty.remove();
            }
            

            if (currentSearch.length > 0) {
                const counter = document.createElement('div');
                counter.className = 'search-results-counter';
                counter.innerHTML = `
                    <i class="fas fa-search me-2"></i>
                    <strong>Hasil pencarian:</strong> 
                    <span class="badge bg-primary">${count} user</span>
                    ditemukan untuk "<strong>${currentSearch}</strong>"
                    ${count === 0 ? ' - <span class="text-danger"><i class="fas fa-exclamation-circle"></i> Tidak ada hasil yang ditemukan</span>' : ''}
                `;
                

                const filterCard = document.querySelector('.card.mb-4');
                const tableCard = document.querySelector('.card.desktop-table');
                if (filterCard && tableCard) {
                    filterCard.parentNode.insertBefore(counter, tableCard);
                } else if (filterCard) {
                    filterCard.parentNode.appendChild(counter);
                }
            }
            

            if (count === 0 && currentSearch.length > 0) {
                const emptyState = document.createElement('div');
                emptyState.className = 'empty-search-state card';
                emptyState.innerHTML = `
                    <div class="card-body">
                        <i class="fas fa-user-slash"></i>
                        <h5>Tidak ada user yang ditemukan</h5>
                        <p class="text-muted">Coba dengan kata kunci lain atau hapus pencarian</p>
                        <button class="btn btn-primary mt-2" onclick="clearSearch()">
                            <i class="fas fa-times"></i> Hapus Pencarian
                        </button>
                    </div>
                `;
                

                const counter = document.querySelector('.search-results-counter');
                if (counter) {
                    counter.parentNode.insertBefore(emptyState, counter.nextSibling);
                } else {
                    const tableCard = document.querySelector('.card.desktop-table');
                    if (tableCard) {
                        tableCard.parentNode.insertBefore(emptyState, tableCard);
                    }
                }
            }
        }
        

        document.addEventListener('DOMContentLoaded', function() {

            document.addEventListener('keydown', function(e) {
                if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
                    e.preventDefault();
                    document.getElementById('userSearchInput').focus();
                }
            });
        });
        
        function showUserModalFromData(element) {
            const userData = JSON.parse(element.getAttribute('data-user'));
            showUserModal(userData);
        }

        function formatDate(dateString) {
                    const date = new Date(dateString);
                    return date.toLocaleDateString('id-ID', {
                        day: '2-digit',
                        month: 'short',
                        year: 'numeric'
                    });
        }

        function showUserModal(user) {
            const body = document.getElementById('userModalBody');
            const createdDate = new Date(user.created_at);
            const dateStr = createdDate.toLocaleDateString('id-ID', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            const timeStr = createdDate.toLocaleTimeString('id-ID', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
            
            body.innerHTML = `
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> <strong>Data Registrasi Pertama Kali</strong>
                    <p class="mb-0 mt-2">Data di bawah ini adalah data yang diinputkan user saat pertama kali melakukan registrasi.</p>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <h6 class="border-bottom pb-2 mb-3">
                            <i class="fas fa-user text-primary"></i> Informasi Personal
                        </h6>
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td width="40%"><strong>ID User:</strong></td>
                                <td><span class="badge bg-secondary">#${user.id}</span></td>
                            </tr>
                            <tr>
                                <td><strong>Username:</strong></td>
                                <td>
                                    <strong style="color: #14b8a6;">@${user.username}</strong>
                                    ${user.google_id ? '<span class="badge bg-warning text-dark ms-2"><i class="fab fa-google"></i> Registrasi via Google</span>' : ''}
                                </td>
                            </tr>
                            ${user.email ? `
                            <tr>
                                <td><strong>Email Google:</strong></td>
                                <td><i class="fas fa-envelope text-info me-1"></i>${user.email}</td>
                            </tr>
                            ` : ''}
                            <tr>
                                <td><strong>Nama Lengkap:</strong></td>
                                <td><strong>${user.nama}</strong></td>
                            </tr>
                            <tr>
                                <td><strong>Nomor HP:</strong></td>
                                <td>${user.nomor_hp ? `<i class="fas fa-phone-alt text-success me-1"></i>${user.nomor_hp}` : '<span class="text-muted">Tidak ada</span>'}</td>
                            </tr>
                            <tr>
                                <td><strong>Role:</strong></td>
                                <td><span class="badge ${user.role === 'admin' ? 'bg-danger' : 'bg-primary'}">${user.role.toUpperCase()}</span></td>
                                    </tr>
                                    <tr>
                                <td><strong>Total Laporan:</strong></td>
                                <td><span class="badge bg-info">${user.report_count || 0} laporan</span></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6 mb-3">
                        <h6 class="border-bottom pb-2 mb-3">
                            <i class="fas fa-map-marker-alt text-danger"></i> Alamat & Lokasi Registrasi
                        </h6>
                        <div class="mb-3">
                            <p class="mb-2"><strong><i class="fas fa-home text-primary"></i> Alamat Lengkap:</strong></p>
                            <div class="alert alert-light border" style="min-height: 80px;">
                                <p class="mb-0" style="line-height: 1.6; color: #495057;">
                                    ${user.alamat ? user.alamat.replace(/\n/g, '<br>') : '<span class="text-muted"><i class="fas fa-exclamation-circle"></i> Alamat tidak tersedia</span>'}
                                </p>
                            </div>
                        </div>
                                ${(user.latitude !== null && user.latitude !== undefined && user.latitude !== '' && user.latitude !== '0' && 
                           user.longitude !== null && user.longitude !== undefined && user.longitude !== '' && user.longitude !== '0' &&
                           !isNaN(parseFloat(user.latitude)) && !isNaN(parseFloat(user.longitude))) ? `
                        <div class="mb-3">
                            <p class="mb-2"><strong><i class="fas fa-globe text-info"></i> Koordinat GPS:</strong></p>
                            <code style="background: #f8f9fa; padding: 8px 12px; border-radius: 5px; display: block; text-align: center;">
                                <i class="fas fa-map-marker-alt text-danger"></i> 
                                <strong>${parseFloat(user.latitude).toFixed(6)}, ${parseFloat(user.longitude).toFixed(6)}</strong>
                            </code>
                        </div>
                        <div class="mb-3">
                            <p class="mb-2"><strong><i class="fas fa-map text-success"></i> Peta Lokasi Registrasi:</strong></p>
                            <div id="userMap_${user.id}" style="height: 250px; border-radius: 8px; border: 2px solid #e5e7eb; margin-bottom: 10px; background: #f3f4f6; display: flex; align-items: center; justify-content: center; color: #6b7280;">
                                <div><i class="fas fa-spinner fa-spin"></i> Memuat peta...</div>
                            </div>
                            <button class="btn btn-sm btn-outline-primary w-100" onclick="showUserLocationMap(${parseFloat(user.latitude)}, ${parseFloat(user.longitude)}, '${String(user.nama || '').replace(/'/g, "\\'")}')">
                                <i class="fas fa-expand"></i> Buka Peta Penuh
                            </button>
                        </div>
                        ` : '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> Koordinat GPS tidak tersedia. User ini mungkin mendaftar sebelum fitur lokasi GPS tersedia atau tidak memberikan izin akses lokasi.</div>'}
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <div class="card" style="background: linear-gradient(135deg, #e8f5e9 0%, #f1f8e9 100%); border-left: 4px solid #4caf50;">
                            <div class="card-body">
                                <h6 class="mb-2"><i class="fas fa-calendar-check text-success"></i> Waktu Registrasi</h6>
                                <p class="mb-1"><strong>Tanggal:</strong> ${dateStr}</p>
                                <p class="mb-0"><strong>Jam:</strong> ${timeStr} WIB</p>
                                <small class="text-muted"><i class="fas fa-clock"></i> ${formatDate(user.created_at)}</small>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            

            if (window.userDetailMap) {
                try {
                    window.userDetailMap.remove();
                } catch(e) {}
                window.userDetailMap = null;
            }
            
                    const modal = new bootstrap.Modal(document.getElementById('userModal'));
                    modal.show();
            

            const modalElement = document.getElementById('userModal');
            

            function initializeUserMap() {
                console.log('=== Initializing User Map ===');
                console.log('User data:', user);
                

                const rawLat = user.latitude;
                const rawLng = user.longitude;
                console.log('Raw coordinates:', {latitude: rawLat, longitude: rawLng});
                

                const lat = (rawLat !== null && rawLat !== undefined && rawLat !== '' && rawLat !== '0') ? parseFloat(rawLat) : null;
                const lng = (rawLng !== null && rawLng !== undefined && rawLng !== '' && rawLng !== '0') ? parseFloat(rawLng) : null;
                
                console.log('Parsed coordinates:', {lat, lng});
                

                if (lat === null || lng === null || isNaN(lat) || isNaN(lng) || lat === 0 || lng === 0) {
                    console.warn('Invalid coordinates - map will not be shown');
                    return;
                }
                
                const mapId = 'userMap_' + user.id;
                const mapElement = document.getElementById(mapId);
                
                console.log('Map ID:', mapId);
                console.log('Map element found:', mapElement);
                console.log('Leaflet available:', typeof L !== 'undefined');
                
                if (!mapElement) {
                    console.error('Map element not found!');
                    return;
                }
                
                if (typeof L === 'undefined') {
                    console.error('Leaflet not loaded! Waiting...');
                    setTimeout(initializeUserMap, 500);
                    return;
                }
                

                if (window.userDetailMap) {
                    try {
                        window.userDetailMap.remove();
                        window.userDetailMap = null;
                        console.log('Old map removed');
                    } catch(e) {
                        console.error('Error removing old map:', e);
                    }
                }
                
                try {

                    mapElement.innerHTML = '';
                    mapElement.style.background = '';
                    mapElement.style.display = '';
                    
                    console.log('Creating map at:', [lat, lng]);
                    

                    window.userDetailMap = L.map(mapId, {
                        zoomControl: true,
                        attributionControl: true
                    }).setView([lat, lng], 15);
                    
                    console.log('Map created successfully');
                    
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '© OpenStreetMap contributors',
                        maxZoom: 19
                    }).addTo(window.userDetailMap);
                    
                    console.log('Tile layer added');
                    
                    const popupContent = '<strong>' + (user.nama || 'User') + '</strong><br><i class="fas fa-map-marker-alt text-danger"></i> Lokasi Registrasi<br><small>' + (user.alamat || '') + '</small>';
                    
                    const customIcon = L.divIcon({
                        className: 'custom-marker',
                        html: '<div style="background-color: #14b8a6; width: 30px; height: 30px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center; color: white;"><i class="fas fa-map-marker-alt"></i></div>',
                        iconSize: [30, 30],
                        iconAnchor: [15, 15]
                    });
                    
                    const marker = L.marker([lat, lng], {icon: customIcon})
                        .addTo(window.userDetailMap)
                        .bindPopup(popupContent);
                    
                    marker.openPopup();
                    
                    console.log('Marker added successfully');
                    

                    setTimeout(() => {
                        if (window.userDetailMap) {
                            window.userDetailMap.invalidateSize();
                            console.log('Map size invalidated (first attempt)');
                        }
                    }, 100);
                    setTimeout(() => {
                        if (window.userDetailMap) {
                            window.userDetailMap.invalidateSize();
                            console.log('Map size invalidated (second attempt)');
                        }
                    }, 300);
                    setTimeout(() => {
                        if (window.userDetailMap) {
                            window.userDetailMap.invalidateSize();
                            console.log('Map size invalidated (third attempt)');
                        }
                    }, 600);
                    
                } catch(e) {
                    console.error('Error creating map:', e);
                    mapElement.innerHTML = '<div style="padding: 20px; text-align: center; color: #dc3545;"><i class="fas fa-exclamation-triangle"></i> Gagal memuat peta: ' + (e.message || e) + '</div>';
                }
            }
            

            const newInitMapHandler = function() {
                console.log('Modal shown event triggered');

                setTimeout(initializeUserMap, 100);

                modalElement.removeEventListener('shown.bs.modal', newInitMapHandler);
            };
            
            modalElement.addEventListener('shown.bs.modal', newInitMapHandler);
        }

        function showRoleModal(id, nama, username, role) {
            document.getElementById('roleUserId').value = id;
            document.getElementById('roleUserName').textContent = nama + ' (@' + username + ')';
            document.getElementById('roleSelect').value = role;
                    const modal = new bootstrap.Modal(document.getElementById('roleModal'));
            modal.show();
        }

        function confirmDelete(id, name) {
                    if (confirm(`Apakah Anda yakin ingin menghapus user "${name}"?\n\nSemua laporan user akan ikut terhapus!`)) {
                window.location.href = 'users.php?delete=' + id;
            }
        }

        function showUserLocationMap(lat, lng, userName) {
            const modal = new bootstrap.Modal(document.getElementById('locationModal'));
            modal.show();
            
            document.getElementById('locationModal').addEventListener('shown.bs.modal', function initMap() {
                if (locationMap) {
                    locationMap.remove();
                }
                
                locationMap = L.map('userLocationMap').setView([lat, lng], 15);
                
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors'
                }).addTo(locationMap);
                
                L.marker([lat, lng]).addTo(locationMap)
                    .bindPopup(`<strong>${userName}</strong><br>Lokasi User`)
                    .openPopup();
                    
                document.getElementById('locationModal').removeEventListener('shown.bs.modal', initMap);
            });
            }
    </script>
</body>
</html>

