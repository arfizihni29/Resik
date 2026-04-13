<?php
require_once '../config/config.php';
require_once '../config/Database.php';
require_once '../classes/Report.php';
require_once '../classes/User.php';
require_once '../classes/CorrectionManager.php';

checkLogin();

$success = '';
$error = '';

$database = new Database();
$db = $database->getConnection();
$report = new Report($db);
$user = new User($db);


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


if ($reportData['status'] != 'pending') {
    $_SESSION['error'] = 'Hanya laporan dengan status PENDING yang bisa diedit!';
    redirect('dashboard.php');
}

$userData = $user->getUserById($_SESSION['user_id']);


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $kategori = $_POST['kategori'];
    $jenis_sampah = $_POST['jenis_sampah'];
    $deskripsi = trim($_POST['deskripsi']);
    $lokasi_latitude = $_POST['lokasi_latitude'];
    $lokasi_longitude = $_POST['lokasi_longitude'];
    $alamat_lokasi = trim($_POST['alamat_lokasi']);
    $confidence = $_POST['confidence'];
    $ai_prediction = $_POST['ai_prediction'];
    $is_corrected = isset($_POST['is_corrected']) ? 1 : 0;
    $correction_note = trim($_POST['correction_note'] ?? '');
    
    $gambar = $reportData['gambar']; // Keep old image by default


    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['gambar']['name'];
        $fileTmp = $_FILES['gambar']['tmp_name'];
        $fileExt = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($fileExt, $allowed)) {
            $newFilename = uniqid() . '_' . time() . '.' . $fileExt;
            $uploadPath = UPLOAD_DIR . $newFilename;

            if (move_uploaded_file($fileTmp, $uploadPath)) {

                $oldImagePath = UPLOAD_DIR . $reportData['gambar'];
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
                $gambar = $newFilename;
            } else {
                $error = 'Gagal mengupload file baru.';
            }
        } else {
            $error = 'Format file tidak diizinkan. Gunakan JPG, JPEG, PNG, atau GIF.';
        }
    }

    if (empty($error)) {

        $report->kategori = $kategori;
        $report->jenis_sampah = $jenis_sampah;
        $report->gambar = $gambar;
        $report->deskripsi = $deskripsi;
        $report->lokasi_latitude = $lokasi_latitude;
        $report->lokasi_longitude = $lokasi_longitude;
        $report->alamat_lokasi = $alamat_lokasi;
        $report->confidence = $confidence;
        $report->ai_prediction = $ai_prediction;
        $report->is_corrected = $is_corrected;
        $report->correction_note = $correction_note;

        if ($report->update($id)) {

            if ($is_corrected && $ai_prediction !== $kategori) {
                $correctionManager = new CorrectionManager();
                $correctionManager->saveCorrectedImage(
                    UPLOAD_DIR . $gambar,
                    $ai_prediction,
                    $kategori,
                    $id
                );
            }
            
            $_SESSION['success'] = '✅ Laporan berhasil diupdate! Perubahan telah disimpan.';
            header('Location: detail.php?id=' . $id);
            exit();
        } else {
            $error = 'Gagal mengupdate laporan.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Laporan - Pelaporan Sampah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="../assets/css/style.css">
    <!-- Select2 for searchable dropdown -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
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
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card fade-in">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-edit"></i> Edit Laporan #<?php echo $id; ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show">
                                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Info:</strong> Anda sedang mengedit laporan. Jika tidak mengganti gambar, gambar lama akan tetap digunakan.
                        </div>

                        <form method="POST" enctype="multipart/form-data" id="editForm">
                            <!-- Preview Gambar Lama -->
                            <div class="mb-3">
                                <label class="form-label fw-bold">Gambar Saat Ini:</label>
                                <div>
                                    <img src="<?php echo getImageUrl($reportData['gambar']); ?>" 
                                         alt="Gambar" 
                                         class="img-thumbnail" 
                                         style="max-width: 300px;"
                                         onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjIwMCIgdmlld0JveD0iMCAwIDMwMCAyMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHJlY3Qgd2lkdGg9IjMwMCIgaGVpZ2h0PSIyMDAiIGZpbGw9IiNGM0Y0RjYiLz48cGF0aCBkPSJNMTI1IDgwSDE3NVYxMjBIMTI1VjgwWk0xMjUgMTQwSDE3NVYxODBIMTI1VjE0MFoiIGZpbGw9IiM5Q0EzQUYiLz48L3N2Zz4='; this.style.border='1px solid #e5e7eb';">
                                </div>
                            </div>

                            <!-- Upload Gambar Baru (Optional) -->
                            <div class="mb-4">
                                <label class="form-label fw-bold">
                                    <i class="fas fa-camera"></i> Ganti Gambar (Opsional)
                                </label>
                                <input type="file" class="form-control" name="gambar" id="gambar" accept="image/*">
                                <small class="text-muted">Kosongkan jika tidak ingin mengganti gambar</small>
                                <div id="imagePreview" class="mt-2" style="display: none;">
                                    <img id="preview" src="" alt="Preview" style="max-width: 300px;" class="img-thumbnail">
                                </div>
                            </div>

                            <!-- AI Classification Result (Hidden Fields) -->
                            <input type="hidden" name="kategori" id="kategori" value="<?php echo htmlspecialchars($reportData['kategori']); ?>">
                            <input type="hidden" name="confidence" id="confidence" value="<?php echo htmlspecialchars($reportData['confidence']); ?>">
                            <input type="hidden" name="ai_prediction" id="aiPrediction" value="<?php echo htmlspecialchars($reportData['ai_prediction']); ?>">
                            <input type="hidden" name="is_corrected" id="isCorrected" value="<?php echo $reportData['is_corrected'] ? '1' : '0'; ?>">

                            <!-- Current Classification -->
                            <div class="mb-4" id="classificationResult">
                                <h6><i class="fas fa-tag"></i> Kategori Sampah Saat Ini</h6>
                                <div class="alert alert-success">
                                    <h5><strong>Kategori:</strong> <span id="currentCategory"><?php echo strtoupper($reportData['kategori']); ?></span></h5>
                                    <p class="mb-0"><strong>Confidence:</strong> <?php echo $reportData['confidence']; ?>%</p>
                                    <?php if ($reportData['is_corrected']): ?>
                                        <hr>
                                        <small class="text-muted">
                                            <i class="fas fa-info-circle"></i> AI Prediksi Awal: <strong><?php echo strtoupper($reportData['ai_prediction']); ?></strong>
                                        </small>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($reportData['is_corrected']): ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-check-circle"></i> <strong>Laporan ini sudah dikoreksi</strong>
                                    <br><small>Catatan: <?php echo htmlspecialchars($reportData['correction_note']); ?></small>
                                </div>
                                <?php endif; ?>

                                <!-- Button to enable correction -->
                                <button type="button" class="btn btn-warning btn-sm" id="toggleCorrectionBtn">
                                    <i class="fas fa-edit"></i> 
                                    <?php echo $reportData['is_corrected'] ? 'Ubah Koreksi' : 'Koreksi Manual'; ?>
                                </button>
                            </div>

                            <!-- Manual Correction Section -->
                            <div class="mb-4" id="correctionSection" style="display: <?php echo $reportData['is_corrected'] ? 'block' : 'none'; ?>;">
                                <div class="card border-warning">
                                    <div class="card-header bg-warning text-dark">
                                        <i class="fas fa-user-edit"></i> <strong>Koreksi Manual</strong>
                                    </div>
                                    <div class="card-body">
                                        <p class="mb-3">
                                            <i class="fas fa-info-circle"></i> 
                                            Jika AI salah memprediksi, pilih kategori yang benar di bawah ini:
                                        </p>
                                        
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Pilih Kategori yang Benar:</label>
                                            <div class="d-flex gap-2 flex-wrap">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="manual_kategori" 
                                                           id="manualOrganik" value="organik" 
                                                           <?php echo $reportData['kategori'] == 'organik' ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="manualOrganik">
                                                        <span class="badge bg-success">ORGANIK</span>
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="manual_kategori" 
                                                           id="manualAnorganik" value="anorganik"
                                                           <?php echo $reportData['kategori'] == 'anorganik' ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="manualAnorganik">
                                                        <span class="badge bg-primary">ANORGANIK</span>
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="manual_kategori" 
                                                           id="manualB3" value="b3"
                                                           <?php echo $reportData['kategori'] == 'b3' ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="manualB3">
                                                        <span class="badge bg-danger">B3</span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Catatan Koreksi:</label>
                                            <textarea class="form-control" name="correction_note" id="correctionNote" 
                                                      rows="2" placeholder="Mengapa Anda mengkoreksi klasifikasi AI? (opsional)"><?php echo htmlspecialchars($reportData['correction_note']); ?></textarea>
                                            <small class="text-muted">Contoh: "Ini adalah baterai bekas, bukan plastik"</small>
                                        </div>

                                        <div class="d-flex gap-2">
                                            <button type="button" class="btn btn-success btn-sm" id="applyCorrectionBtn">
                                                <i class="fas fa-check"></i> Terapkan Koreksi
                                            </button>
                                            <button type="button" class="btn btn-secondary btn-sm" id="cancelCorrectionBtn">
                                                <i class="fas fa-times"></i> Batal
                                            </button>
                                            <?php if ($reportData['is_corrected']): ?>
                                            <button type="button" class="btn btn-outline-danger btn-sm" id="removeCorrectionBtn">
                                                <i class="fas fa-trash"></i> Hapus Koreksi
                                            </button>
                                            <?php endif; ?>
                                        </div>

                                        <div id="correctedResult" class="mt-3" style="display: none;">
                                            <div class="alert alert-info mb-0">
                                                <i class="fas fa-check-circle"></i> 
                                                <strong>Koreksi diterapkan!</strong> Kategori diubah ke: 
                                                <strong id="correctedCategory"></strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Jenis Sampah -->
                            <div class="mb-4">
                                <label class="form-label fw-bold">
                                    <i class="fas fa-list"></i> Jenis Sampah Spesifik
                                </label>
                                <select name="jenis_sampah" class="form-select select2-jenis-sampah" required>
                                    <option value="">-- Pilih atau Cari Jenis Sampah --</option>
                                    
                                    <?php 
                                    $jenisSampahGroups = [
                                        '🌿 ORGANIK' => [
                                            'daun' => '🍃 Daun / Ranting / Rumput',
                                            'makanan' => '🍎 Sisa Makanan',
                                            'buah_sayur' => '🥬 Buah / Sayur Busuk',
                                            'kayu' => '🪵 Kayu',
                                            'kotoran_hewan' => '🐾 Kotoran Hewan',
                                            'tulang' => '🦴 Tulang',
                                            'kulit_telur' => '🥚 Kulit Telur',
                                            'ampas_kopi' => '☕ Ampas Kopi / Teh',
                                            'kotoran_dapur' => '🍴 Sampah Dapur',
                                            'kertas_tisu' => '🧻 Tisu / Kertas Basah',
                                            'serbuk_gergaji' => '🪚 Serbuk Gergaji'
                                        ],
                                        '♻️ PLASTIK' => [
                                            'botol_plastik' => '🍾 Botol Plastik (PET)',
                                            'kantong_plastik' => '🛍️ Kantong Plastik / Kresek',
                                            'gelas_plastik' => '🥤 Gelas / Cup Plastik',
                                            'sedotan_plastik' => '🥤 Sedotan Plastik',
                                            'styrofoam' => '📦 Styrofoam',
                                            'kemasan_plastik' => '📦 Kemasan Plastik',
                                            'ember_plastik' => '🪣 Ember / Wadah Plastik',
                                            'mainan_plastik' => '🧸 Mainan Plastik',
                                            'jerigen_plastik' => '🧴 Jerigen Plastik',
                                            'plastik_lainnya' => '♻️ Plastik Lainnya'
                                        ],
                                        '📄 KERTAS & KARDUS' => [
                                            'kertas' => '📄 Kertas',
                                            'koran' => '📰 Koran / Majalah',
                                            'buku' => '📚 Buku Bekas',
                                            'karton' => '📦 Kardus / Karton',
                                            'kertas_kantor' => '🗂️ Kertas Kantor',
                                            'amplop' => '✉️ Amplop',
                                            'dus_bekas' => '📦 Dus Bekas'
                                        ],
                                        '🔩 LOGAM' => [
                                            'kaleng_minuman' => '🥫 Kaleng Minuman',
                                            'kaleng_makanan' => '🥫 Kaleng Makanan',
                                            'logam' => '🔩 Logam / Besi',
                                            'kawat' => '🔗 Kawat',
                                            'paku' => '🔨 Paku / Sekrup',
                                            'seng' => '🏗️ Seng / Besi Bekas',
                                            'foil_aluminium' => '🎁 Aluminium Foil'
                                        ],
                                        '🪟 KACA' => [
                                            'botol_kaca' => '🍾 Botol Kaca',
                                            'pecahan_kaca' => '🪟 Pecahan Kaca',
                                            'cermin' => '🪞 Cermin Bekas',
                                            'stoples_kaca' => '🫙 Stoples / Toples Kaca'
                                        ],
                                        '👕 TEKSTIL & PAKAIAN' => [
                                            'kain' => '👕 Kain / Pakaian Bekas',
                                            'sepatu' => '👟 Sepatu Bekas',
                                            'tas' => '🎒 Tas Bekas',
                                            'selimut' => '🛏️ Selimut / Sprei',
                                            'boneka' => '🧸 Boneka Kain',
                                            'topi' => '🧢 Topi Bekas'
                                        ],
                                        '🛞 KARET & KULIT' => [
                                            'ban_bekas' => '🛞 Ban Bekas',
                                            'sandal_karet' => '🩴 Sandal Karet',
                                            'sarung_tangan' => '🧤 Sarung Tangan Karet',
                                            'balon' => '🎈 Balon Karet'
                                        ],
                                        '🏗️ LAINNYA' => [
                                            'pipa_pralon' => '🔧 Pipa / Pralon',
                                            'keramik' => '🏺 Keramik Pecah',
                                            'bata' => '🧱 Bata / Puing',
                                            'karpet' => '🧶 Karpet Bekas',
                                            'kasur' => '🛏️ Kasur Bekas',
                                            'furniture' => '🪑 Furniture Bekas',
                                            'gabus' => '📦 Gabus / Packing',
                                            'lilin' => '🕯️ Lilin Bekas'
                                        ],
                                        '⚠️ B3 - ELEKTRONIK' => [
                                            'elektronik' => '📱 Elektronik / E-Waste',
                                            'hp_bekas' => '📱 HP / Smartphone Bekas',
                                            'komputer' => '💻 Komputer / Laptop',
                                            'tv_bekas' => '📺 TV / Monitor Bekas',
                                            'kabel_elektronik' => '🔌 Kabel Elektronik',
                                            'charger' => '🔌 Charger / Adaptor',
                                            'baterai' => '🔋 Baterai',
                                            'aki' => '🔋 Aki / Accu Bekas',
                                            'lampu' => '💡 Lampu / Bohlam'
                                        ],
                                        '⚠️ B3 - KIMIA & BERBAHAYA' => [
                                            'oli' => '🛢️ Oli / Minyak Bekas',
                                            'cat' => '🎨 Cat / Thinner',
                                            'obat' => '💊 Obat Kadaluarsa',
                                            'pestisida' => '☠️ Pestisida / Racun',
                                            'semprot_serangga' => '🪰 Obat Nyamuk / Semprot',
                                            'tinta_printer' => '🖨️ Tinta / Toner Printer',
                                            'kaleng_aerosol' => '💨 Kaleng Aerosol / Spray',
                                            'termometer' => '🌡️ Termometer Raksa'
                                        ],
                                        '😷 MEDIS & KESEHATAN' => [
                                            'masker' => '😷 Masker Bekas',
                                            'jarum_suntik' => '💉 Jarum Suntik',
                                            'sarung_tangan_medis' => '🧤 Sarung Tangan Medis',
                                            'perban' => '🩹 Perban / Plester'
                                        ],
                                        '❓ TIDAK YAKIN' => [
                                            'tidak_diketahui' => '❓ Tidak Diketahui / Tidak Yakin',
                                            'lainnya' => '🔸 Lainnya'
                                        ]
                                    ];
                                    
                                    foreach ($jenisSampahGroups as $groupLabel => $items) {
                                        echo '<optgroup label="' . $groupLabel . '">';
                                        foreach ($items as $value => $label) {
                                            $selected = ($reportData['jenis_sampah'] == $value) ? 'selected' : '';
                                            echo '<option value="' . $value . '" ' . $selected . '>' . $label . '</option>';
                                        }
                                        echo '</optgroup>';
                                    }
                                    ?>
                                </select>
                            </div>

                            <!-- Deskripsi -->
                            <div class="mb-4">
                                <label class="form-label fw-bold">
                                    <i class="fas fa-align-left"></i> Deskripsi
                                </label>
                                <textarea name="deskripsi" class="form-control" rows="3" 
                                          placeholder="Deskripsikan kondisi sampah..."><?php echo htmlspecialchars($reportData['deskripsi']); ?></textarea>
                            </div>

                            <!-- Lokasi -->
                            <div class="mb-4">
                                <label class="form-label fw-bold">
                                    <i class="fas fa-map-marker-alt"></i> Lokasi Sampah
                                </label>
                                <div class="input-group mb-2">
                                    <button type="button" class="btn btn-primary" id="getLocation">
                                        <i class="fas fa-crosshairs"></i> Gunakan Lokasi Saya
                                    </button>
                                    <input type="text" class="form-control" id="alamat_lokasi" name="alamat_lokasi" 
                                           placeholder="Alamat lokasi..." required value="<?php echo htmlspecialchars($reportData['alamat_lokasi']); ?>">
                                </div>
                                <input type="hidden" name="lokasi_latitude" id="lokasi_latitude" value="<?php echo $reportData['lokasi_latitude']; ?>" required>
                                <input type="hidden" name="lokasi_longitude" id="lokasi_longitude" value="<?php echo $reportData['lokasi_longitude']; ?>" required>
                                <div id="map" style="height: 300px; border-radius: 10px;"></div>
                            </div>

                            <!-- Buttons -->
                            <div class="d-grid gap-2 d-md-flex justify-content-md-between">
                                <a href="dashboard.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Batal
                                </a>
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-save"></i> Simpan Perubahan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>&copy; 2024 Aplikasi Pelaporan Sampah dengan AI | Powered by Teachable Machine</p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="../assets/js/navbar.js"></script>
    <script>

        const lat = <?php echo $reportData['lokasi_latitude']; ?>;
        const lng = <?php echo $reportData['lokasi_longitude']; ?>;
        
        const map = L.map('map').setView([lat, lng], 15);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);
        
        let marker = L.marker([lat, lng], {draggable: true}).addTo(map);
        
        marker.on('dragend', function(e) {
            const position = marker.getLatLng();
            document.getElementById('lokasi_latitude').value = position.lat;
            document.getElementById('lokasi_longitude').value = position.lng;
        });
        

        document.getElementById('getLocation').addEventListener('click', function() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    
                    document.getElementById('lokasi_latitude').value = lat;
                    document.getElementById('lokasi_longitude').value = lng;
                    
                    map.setView([lat, lng], 15);
                    marker.setLatLng([lat, lng]);
                });
            } else {
                alert('Browser tidak mendukung geolocation');
            }
        });


        document.getElementById('gambar').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('preview').src = e.target.result;
                    document.getElementById('imagePreview').style.display = 'block';
                }
                reader.readAsDataURL(file);
            }
        });


        const toggleCorrectionBtn = document.getElementById('toggleCorrectionBtn');
        const correctionSection = document.getElementById('correctionSection');
        const applyCorrectionBtn = document.getElementById('applyCorrectionBtn');
        const cancelCorrectionBtn = document.getElementById('cancelCorrectionBtn');
        const removeCorrectionBtn = document.getElementById('removeCorrectionBtn');
        const kategoriInput = document.getElementById('kategori');
        const isCorrectedInput = document.getElementById('isCorrected');
        const currentCategorySpan = document.getElementById('currentCategory');
        const correctedResult = document.getElementById('correctedResult');
        const correctedCategorySpan = document.getElementById('correctedCategory');


        toggleCorrectionBtn.addEventListener('click', function() {
            if (correctionSection.style.display === 'none') {
                correctionSection.style.display = 'block';
                toggleCorrectionBtn.textContent = '✕ Tutup Koreksi';
            } else {
                correctionSection.style.display = 'none';
                toggleCorrectionBtn.innerHTML = '<i class="fas fa-edit"></i> <?php echo $reportData['is_corrected'] ? 'Ubah Koreksi' : 'Koreksi Manual'; ?>';
                correctedResult.style.display = 'none';
            }
        });


        applyCorrectionBtn.addEventListener('click', function() {
            const selectedKategori = document.querySelector('input[name="manual_kategori"]:checked');
            if (selectedKategori) {
                const newKategori = selectedKategori.value;
                kategoriInput.value = newKategori;
                isCorrectedInput.value = '1';
                
                currentCategorySpan.textContent = newKategori.toUpperCase();
                correctedCategorySpan.textContent = newKategori.toUpperCase();
                correctedResult.style.display = 'block';
                

                alert('Koreksi berhasil diterapkan! Jangan lupa klik "Simpan Perubahan" di bawah.');
            } else {
                alert('Pilih kategori yang benar terlebih dahulu!');
            }
        });


        cancelCorrectionBtn.addEventListener('click', function() {
            correctionSection.style.display = 'none';
            toggleCorrectionBtn.innerHTML = '<i class="fas fa-edit"></i> <?php echo $reportData['is_corrected'] ? 'Ubah Koreksi' : 'Koreksi Manual'; ?>';
            correctedResult.style.display = 'none';
        });


        <?php if ($reportData['is_corrected']): ?>
        removeCorrectionBtn.addEventListener('click', function() {
            if (confirm('Yakin ingin menghapus koreksi dan kembali ke prediksi AI?')) {

                const aiPrediction = document.getElementById('aiPrediction').value;
                kategoriInput.value = aiPrediction;
                isCorrectedInput.value = '0';
                document.getElementById('correctionNote').value = '';
                
                currentCategorySpan.textContent = aiPrediction.toUpperCase();
                correctedResult.style.display = 'none';
                
                alert('Koreksi dihapus! Kategori dikembalikan ke prediksi AI. Jangan lupa klik "Simpan Perubahan".');
            }
        });
        <?php endif; ?>
        

        $(document).ready(function() {
            $('.select2-jenis-sampah').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: '-- Ketik untuk mencari jenis sampah --',
                allowClear: true,
                language: {
                    noResults: function() {
                        return "Jenis sampah tidak ditemukan";
                    },
                    searching: function() {
                        return "Mencari...";
                    },
                    inputTooShort: function() {
                        return "Ketik untuk mencari";
                    }
                }
            });
        });
    </script>
</body>
</html>

