<?php




if (!isset($currentPage)) {
    $currentPage = '';
}
?>
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php">
            <img src="../favicon.svg" alt="Logo" height="30" class="d-inline-block align-text-top me-2"> 
            Pelaporan Sampah - ADMIN
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                
                <li class="nav-item dropdown d-none d-lg-block">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownMenu" role="button" 
                       data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-bars"></i> Menu
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdownMenu">
                        <li>
                            <a class="dropdown-item <?php echo $currentPage == 'dashboard' ? 'active' : ''; ?>" href="dashboard.php">
                                <i class="fas fa-home"></i> Dashboard
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li><h6 class="dropdown-header"><i class="fas fa-file-alt"></i> Laporan</h6></li>
                        <li>
                            <a class="dropdown-item <?php echo $currentPage == 'laporan' ? 'active' : ''; ?>" href="laporan.php">
                                <i class="fas fa-list"></i> Semua Laporan
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item <?php echo $currentPage == 'koreksi' ? 'active' : ''; ?>" href="koreksi.php">
                                <i class="fas fa-edit"></i> Koreksi Data
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item <?php echo $currentPage == 'gallery' ? 'active' : ''; ?>" href="corrections_gallery.php">
                                <i class="fas fa-images"></i> Galeri Koreksi
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li><h6 class="dropdown-header"><i class="fas fa-chart-bar"></i> Data & Analisis</h6></li>
                        <li>
                            <a class="dropdown-item <?php echo $currentPage == 'analytics' ? 'active' : ''; ?>" href="analytics.php">
                                <i class="fas fa-brain"></i> Analytics & Insight
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item <?php echo $currentPage == 'peta' ? 'active' : ''; ?>" href="peta.php">
                                <i class="fas fa-map"></i> Peta
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li><h6 class="dropdown-header"><i class="fas fa-cog"></i> System</h6></li>
                        <li>
                            <a class="dropdown-item <?php echo $currentPage == 'users' ? 'active' : ''; ?>" href="users.php">
                                <i class="fas fa-users"></i> Users
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item <?php echo $currentPage == 'engine_settings' ? 'active' : ''; ?>" href="engine_settings.php">
                                <i class="fas fa-key"></i> Engine Settings
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item <?php echo $currentPage == 'migration' ? 'active' : ''; ?>" href="migration.php">
                                <i class="fas fa-database"></i> Migration
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item <?php echo $currentPage == 'backup' ? 'active' : ''; ?>" href="backup.php">
                                <i class="fas fa-download"></i> Backup
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="../logout.php">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </li>
                    </ul>
                </li>
                
                
                <li class="nav-item d-none d-lg-block">
                    <span class="nav-link">
                        <i class="fas fa-user-shield"></i> <?php echo isset($_SESSION['nama']) ? $_SESSION['nama'] : 'Administrator'; ?>
                    </span>
                </li>
                
                
                <li class="nav-item d-lg-none w-100">
                    <div class="mobile-menu-container">
                        <div class="mobile-menu-header">
                            <small class="mobile-menu-user">
                                <i class="fas fa-user-shield"></i> <?php echo isset($_SESSION['nama']) ? $_SESSION['nama'] : 'Admin'; ?>
                            </small>
                        </div>
                        <div class="mobile-menu-scroll">
                            <a href="dashboard.php" class="mobile-menu-item <?php echo $currentPage == 'dashboard' ? 'active' : ''; ?>">
                                <i class="fas fa-home"></i>
                                <span>Dashboard</span>
                            </a>
                            <div class="mobile-menu-divider"></div>
                            <div class="mobile-menu-group-label">
                                <i class="fas fa-file-alt"></i> Laporan
                            </div>
                            <a href="laporan.php" class="mobile-menu-item <?php echo $currentPage == 'laporan' ? 'active' : ''; ?>">
                                <i class="fas fa-list"></i>
                                <span>Semua Laporan</span>
                            </a>
                            <a href="koreksi.php" class="mobile-menu-item <?php echo $currentPage == 'koreksi' ? 'active' : ''; ?>">
                                <i class="fas fa-edit"></i>
                                <span>Koreksi Data</span>
                            </a>
                            <a href="corrections_gallery.php" class="mobile-menu-item <?php echo $currentPage == 'gallery' ? 'active' : ''; ?>">
                                <i class="fas fa-images"></i>
                                <span>Galeri Koreksi</span>
                            </a>
                            <div class="mobile-menu-divider"></div>
                            <div class="mobile-menu-group-label">
                                <i class="fas fa-chart-bar"></i> Data & Analisis
                            </div>
                            <a href="analytics.php" class="mobile-menu-item <?php echo $currentPage == 'analytics' ? 'active' : ''; ?>">
                                <i class="fas fa-brain"></i>
                                <span>Analytics & Insight</span>
                            </a>
                            <a href="peta.php" class="mobile-menu-item <?php echo $currentPage == 'peta' ? 'active' : ''; ?>">
                                <i class="fas fa-map"></i>
                                <span>Peta</span>
                            </a>
                            <div class="mobile-menu-divider"></div>
                            <div class="mobile-menu-group-label">
                                <i class="fas fa-cog"></i> System
                            </div>
                            <a href="users.php" class="mobile-menu-item <?php echo $currentPage == 'users' ? 'active' : ''; ?>">
                                <i class="fas fa-users"></i>
                                <span>Users</span>
                            </a>
                            <a href="engine_settings.php" class="mobile-menu-item <?php echo $currentPage == 'engine_settings' ? 'active' : ''; ?>">
                                <i class="fas fa-key"></i>
                                <span>Engine Settings</span>
                            </a>
                            <a href="migration.php" class="mobile-menu-item <?php echo $currentPage == 'migration' ? 'active' : ''; ?>">
                                <i class="fas fa-database"></i>
                                <span>Migration</span>
                            </a>
                            <a href="backup.php" class="mobile-menu-item <?php echo $currentPage == 'backup' ? 'active' : ''; ?>">
                                <i class="fas fa-download"></i>
                                <span>Backup</span>
                            </a>
                        </div>
                        <div class="mobile-menu-footer">
                            <a href="../logout.php" class="mobile-menu-item mobile-menu-logout">
                                <i class="fas fa-sign-out-alt"></i>
                                <span>Logout</span>
                            </a>
                        </div>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</nav>

