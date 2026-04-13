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
$user = new User($db);
$userData = $user->getUserById($_SESSION['user_id']);


$savedLocationsQuery = "SELECT * FROM user_locations WHERE user_id = :user_id ORDER BY last_used DESC LIMIT 5";
$stmt = $db->prepare($savedLocationsQuery);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$savedLocations = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $kategori = $_POST['kategori'];
    $jenis_sampah = $_POST['jenis_sampah'];
    $deskripsi = trim($_POST['deskripsi']);
    $lokasi_latitude = $_POST['lokasi_latitude'];
    $lokasi_longitude = $_POST['lokasi_longitude'];
    $alamat_lokasi = trim($_POST['alamat_lokasi']);
    $whatsapp_number = trim($_POST['whatsapp_number']); // Nomor WhatsApp
    $confidence = $_POST['confidence'];
    $ai_prediction = $_POST['ai_prediction'];
    $is_corrected = (isset($_POST['is_corrected']) && $_POST['is_corrected'] == '1') ? 1 : 0;
    $correction_note = trim($_POST['correction_note'] ?? '');
    $tags = trim($_POST['tags'] ?? ''); // Tags untuk analitik
    

    if (isset($_POST['save_location']) && $_POST['save_location'] == '1') {
        $locationName = trim($_POST['location_name'] ?? 'Lokasi Tersimpan');
        $saveLocQuery = "INSERT INTO user_locations (user_id, name, latitude, longitude, address, last_used) 
                        VALUES (:user_id, :name, :lat, :lng, :address, NOW())
                        ON DUPLICATE KEY UPDATE last_used = NOW()";
        $saveStmt = $db->prepare($saveLocQuery);
        $saveStmt->execute([
            ':user_id' => $_SESSION['user_id'],
            ':name' => $locationName,
            ':lat' => $lokasi_latitude,
            ':lng' => $lokasi_longitude,
            ':address' => $alamat_lokasi
        ]);
    }


    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['gambar']['name'];
        $fileTmp = $_FILES['gambar']['tmp_name'];
        $fileExt = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($fileExt, $allowed)) {
            $newFilename = uniqid() . '_' . time() . '.' . $fileExt;
            $uploadPath = UPLOAD_DIR . $newFilename;

            if (move_uploaded_file($fileTmp, $uploadPath)) {

                $report = new Report($db);
                $report->user_id = $_SESSION['user_id'];
                $report->kategori = $kategori;
                $report->jenis_sampah = $jenis_sampah;
                $report->gambar = $newFilename;
                $report->deskripsi = $deskripsi;
                $report->lokasi_latitude = $lokasi_latitude;
                $report->lokasi_longitude = $lokasi_longitude;
                $report->alamat_lokasi = $alamat_lokasi;
                $report->whatsapp_number = $whatsapp_number; // Nomor WhatsApp
                $report->confidence = $confidence;
                $report->ai_prediction = $ai_prediction;
                $report->is_corrected = $is_corrected;
                $report->correction_note = $correction_note;
                $report->tags = $tags; // Tags untuk analitik
                $report->status = 'pending';

                if ($report->create()) {
                    $reportId = $db->lastInsertId();
                    $success = 'Laporan berhasil dikirim!';
                    

                    if ($is_corrected && $ai_prediction !== $kategori) {
                        $correctionManager = new CorrectionManager();
                        $correctionManager->saveCorrectedImage(
                            $uploadPath,
                            $ai_prediction,
                            $kategori,
                            $reportId
                        );
                    }
                } else {
                    $error = 'Gagal menyimpan laporan.';
                }
            } else {
                $error = 'Gagal mengupload file.';
            }
        } else {
            $error = 'Format file tidak diizinkan. Gunakan JPG, JPEG, PNG, atau GIF.';
        }
    } else {
        $error = 'Harap pilih gambar untuk diupload.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <meta name="theme-color" content="#14b8a6">
    <title>Lapor Sampah - Pelaporan Sampah</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="../favicon.svg">
    <link rel="alternate icon" href="../favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="../favicon.svg">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Select2 for searchable dropdown -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    
    <style>
        /* Modern Clean Design with Visual Enhancements */
        body {
            background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 50%, #e0f2fe 100%);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            position: relative;
            overflow-x: hidden;
        }
        
        /* Animated Background Decoration */
        body::before {
            content: '';
            position: fixed;
            top: -50%;
            right: -20%;
            width: 800px;
            height: 800px;
            background: radial-gradient(circle, rgba(20, 184, 166, 0.12) 0%, transparent 70%);
            border-radius: 50%;
            animation: floatBackground 20s infinite ease-in-out;
            z-index: 0;
            pointer-events: none;
        }
        
        body::after {
            content: '';
            position: fixed;
            bottom: -30%;
            left: -10%;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(6, 182, 212, 0.1) 0%, transparent 70%);
            border-radius: 50%;
            animation: floatBackground 15s infinite ease-in-out reverse;
            z-index: 0;
            pointer-events: none;
        }
        
        @keyframes floatBackground {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            50% { transform: translate(-30px, -30px) rotate(180deg); }
        }
        
        .container-fluid {
            position: relative;
            z-index: 1;
        }
        
        
        /* Progress Indicator - Modern */
        .progress-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2.5rem;
            position: relative;
            padding: 0 10px;
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        }
        
        .progress-indicator::before {
            content: '';
            position: absolute;
            top: 2.25rem;
            left: 15%;
            right: 15%;
            height: 3px;
            background: #e5e7eb;
            z-index: 0;
            border-radius: 3px;
        }
        
        .progress-line-fill {
            position: absolute;
            top: 2.25rem;
            left: 15%;
            height: 3px;
            background: linear-gradient(90deg, #14b8a6, #0d9488);
            z-index: 0;
            border-radius: 3px;
            transition: width 0.5s ease;
        }
        
        .progress-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 1;
            flex: 1;
        }
        
        .progress-step-circle {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            background: white;
            border: 3px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.125rem;
            color: #9ca3af;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            margin-bottom: 0.75rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }
        
        .progress-step.active .progress-step-circle {
            border-color: #14b8a6;
            background: linear-gradient(135deg, #14b8a6, #0d9488);
            color: white;
            transform: scale(1.15);
            box-shadow: 0 4px 16px rgba(20, 184, 166, 0.4);
        }
        
        .progress-step.completed .progress-step-circle {
            border-color: #14b8a6;
            background: #14b8a6;
            color: white;
            box-shadow: 0 2px 12px rgba(20, 184, 166, 0.3);
        }
        
        .progress-step-label {
            font-size: 0.8125rem;
            color: #6b7280;
            text-align: center;
            font-weight: 600;
            line-height: 1.3;
        }
        
        .progress-step.active .progress-step-label {
            color: #14b8a6;
            font-weight: 700;
        }
        
        .progress-step.completed .progress-step-label {
            color: #14b8a6;
        }
        
        /* Camera Capture Styles - Improved */
        .image-upload-options {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .upload-option-btn {
            padding: 2rem 1.5rem;
            border: 3px dashed #cbd5e1;
            border-radius: 16px;
            background: white;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .upload-option-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(20, 184, 166, 0.05), rgba(13, 148, 136, 0.05));
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .upload-option-btn:hover {
            border-color: #14b8a6;
            background: white;
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(20, 184, 166, 0.15);
        }
        
        .upload-option-btn:hover::before {
            opacity: 1;
        }
        
        .upload-option-btn:active {
            transform: translateY(-2px);
        }
        
        .upload-option-btn i {
            font-size: 2.5rem;
            color: #14b8a6;
            margin-bottom: 0.75rem;
            transition: transform 0.3s ease;
        }
        
        .upload-option-btn:hover i {
            transform: scale(1.1);
        }
        
        .upload-option-btn .option-label {
            font-weight: 700;
            color: #1f2937;
            font-size: 0.9375rem;
            display: block;
            margin-bottom: 0.25rem;
        }
        
        .upload-option-btn .option-hint {
            font-size: 0.75rem;
            color: #6b7280;
            font-weight: 500;
        }
        
        /* ===== IMAGE PREVIEW CARD (ENHANCED) ===== */
        .image-preview-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
            border: 2px solid #14b8a6;
            overflow: hidden;
            animation: slideIn 0.4s ease-out;
        }
        
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .image-preview-header {
            background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
            padding: 1rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .image-preview-title {
            color: white;
            font-weight: 700;
            font-size: 1.125rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .image-preview-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-preview-action {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }
        
        .btn-preview-action:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        
        .image-preview-body {
            padding: 1.5rem;
            background: #f9fafb;
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 300px;
        }
        
        .image-preview-img {
            max-width: 100%;
            max-height: 500px;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        
        .image-preview-img:hover {
            transform: scale(1.02);
        }
        
        .image-preview-overlay {
            position: absolute;
            bottom: 2rem;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(20, 184, 166, 0.95);
            color: white;
            padding: 0.5rem 1.25rem;
            border-radius: 50px;
            font-size: 0.875rem;
            font-weight: 600;
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: none;
            box-shadow: 0 4px 12px rgba(20, 184, 166, 0.4);
        }
        
        .image-preview-body:hover .image-preview-overlay {
            opacity: 1;
        }
        
        .image-preview-info {
            background: white;
            padding: 1rem 1.5rem;
            display: flex;
            justify-content: space-around;
            border-top: 1px solid #e5e7eb;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        
        .info-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #6b7280;
            font-size: 0.875rem;
        }
        
        .info-item i {
            color: #14b8a6;
            font-size: 1rem;
        }
        
        .info-item span {
            font-weight: 600;
            color: #1f2937;
        }
        
        /* ===== ZOOM MODAL ===== */
        .zoom-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.95);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .zoom-close {
            position: absolute;
            top: 20px;
            right: 30px;
            font-size: 3rem;
            color: white;
            cursor: pointer;
            z-index: 10000;
            transition: transform 0.3s ease;
        }
        
        .zoom-close:hover {
            transform: scale(1.1);
        }
        
        .zoom-content {
            width: 90%;
            height: 90%;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        
        .zoom-image {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            transition: transform 0.3s ease;
            cursor: grab;
        }
        
        .zoom-image:active {
            cursor: grabbing;
        }
        
        .zoom-controls {
            position: absolute;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 0.75rem;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }
        
        .zoom-btn {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.25);
            border: none;
            color: white;
            font-size: 1.125rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .zoom-btn:hover {
            background: rgba(20, 184, 166, 0.8);
            transform: scale(1.1);
        }
        
        .zoom-hint {
            position: absolute;
            top: 30px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            font-size: 0.875rem;
        }
        
        /* ===== RESPONSIVE - IMAGE PREVIEW CARD ===== */
        @media (max-width: 768px) {
            .image-preview-header {
                padding: 0.875rem 1rem;
                flex-direction: column;
                gap: 0.75rem;
            }
            
            .image-preview-title {
                font-size: 1rem;
            }
            
            .image-preview-body {
                padding: 1rem;
                min-height: 200px;
            }
            
            .image-preview-img {
                max-height: 350px;
            }
            
            .image-preview-info {
                flex-direction: column;
                padding: 0.875rem 1rem;
            }
            
            .btn-preview-action {
                padding: 0.4rem 0.75rem;
                font-size: 0.8125rem;
            }
            
            .zoom-controls {
                padding: 0.5rem 1rem;
                gap: 0.5rem;
            }
            
            .zoom-btn {
                width: 40px;
                height: 40px;
                font-size: 1rem;
            }
        }
        
        /* Form Section - Cleaner */
        .form-section {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            border: 1px solid #f3f4f6;
            transition: all 0.3s ease;
        }
        
        .form-section:hover {
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
        }
        
        .form-section h5 {
            color: #1f2937;
            font-weight: 700;
            font-size: 1.125rem;
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid #f3f4f6;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .form-section h5 i {
            color: #14b8a6;
        }
        
        .form-disabled {
            opacity: 0.5;
            pointer-events: none;
            filter: grayscale(50%);
        }
        
        /* Modern Form Controls */
        .form-label {
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
            font-size: 0.9375rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .form-label i {
            color: #14b8a6;
        }
        
        .form-control, .form-select {
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            padding: 0.75rem 1rem;
            font-size: 0.9375rem;
            transition: all 0.2s ease;
            background: white;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #14b8a6;
            box-shadow: 0 0 0 4px rgba(20, 184, 166, 0.1);
            outline: none;
        }
        
        .form-text {
            font-size: 0.8125rem;
            color: #6b7280;
            margin-top: 0.375rem;
            display: flex;
            align-items: center;
            gap: 0.375rem;
        }
        
        .form-text i {
            color: #14b8a6;
        }
        
        /* Modern Buttons */
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.9375rem;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            justify-content: center;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #14b8a6, #0d9488);
            color: white;
            box-shadow: 0 4px 12px rgba(20, 184, 166, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(20, 184, 166, 0.4);
        }
        
        .btn-primary:active {
            transform: translateY(0);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }
        
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
        }
        
        .btn-outline-secondary {
            background: white;
            color: #6b7280;
            border: 2px solid #e5e7eb;
        }
        
        .btn-outline-secondary:hover {
            background: #f9fafb;
            border-color: #d1d5db;
            color: #4b5563;
        }
        
        /* Info Box Helper */
        .info-box {
            background: linear-gradient(135deg, #eff6ff, #dbeafe);
            border-left: 4px solid #3b82f6;
            border-radius: 12px;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
        }
        
        .info-box h6 {
            color: #1e40af;
            font-weight: 700;
            font-size: 0.9375rem;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .info-box p {
            color: #1e3a8a;
            font-size: 0.875rem;
            margin: 0;
            line-height: 1.5;
        }
        
        .info-box ul {
            margin: 0.5rem 0 0 0;
            padding-left: 1.25rem;
        }
        
        .info-box li {
            color: #1e3a8a;
            font-size: 0.875rem;
            margin-bottom: 0.25rem;
        }
        
        /* Alert Styles */
        .alert {
            border-radius: 12px;
            border: none;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
            font-size: 0.9375rem;
        }
        
        .alert i {
            margin-right: 0.5rem;
        }
        
        
        /* Preview Modal Styles - Enhanced Modern Design */
        .preview-modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.92);
            backdrop-filter: blur(15px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            padding: 20px;
            overflow-y: auto;
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .preview-content {
            background: white;
            border-radius: 28px;
            max-width: 850px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            animation: slideUpBounce 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
            box-shadow: 0 25px 80px rgba(20, 184, 166, 0.3),
                        0 0 0 1px rgba(255, 255, 255, 0.1);
        }
        
        @keyframes slideUpBounce {
            from {
                opacity: 0;
                transform: translateY(50px) scale(0.9);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
        .preview-header {
            background: linear-gradient(135deg, #0d9488 0%, #14b8a6 50%, #06b6d4 100%);
            color: white;
            padding: 32px 28px;
            border-radius: 28px 28px 0 0;
            position: sticky;
            top: 0;
            z-index: 10;
            box-shadow: 0 4px 20px rgba(20, 184, 166, 0.2);
        }
        
        .preview-header h3 {
            font-size: 1.75rem;
            font-weight: 800;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 12px;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }
        
        .preview-header p {
            opacity: 0.95;
            font-size: 0.95rem;
            font-weight: 500;
        }
        
        .preview-body {
            padding: 32px 28px;
            background: linear-gradient(to bottom, #f9fafb 0%, white 100%);
        }
        
        .preview-section {
            margin-bottom: 28px;
            padding: 24px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
            transition: all 0.3s ease;
        }
        
        .preview-section:hover {
            box-shadow: 0 8px 24px rgba(20, 184, 166, 0.12);
            transform: translateY(-2px);
        }
        
        .preview-section:last-child {
            margin-bottom: 0;
        }
        
        .preview-label {
            font-weight: 700;
            color: #14b8a6;
            font-size: 1rem;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
            padding-bottom: 8px;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .preview-value {
            color: #1f2937;
            font-size: 1.05rem;
            line-height: 1.7;
            font-weight: 500;
        }
        
        .preview-images {
            display: grid;
            grid-template-columns: 1fr;
            gap: 16px;
        }
        
        .preview-image-item {
            border-radius: 16px;
            overflow: hidden;
            position: relative;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
            transition: all 0.3s ease;
        }
        
        .preview-image-item:hover {
            transform: scale(1.02);
            box-shadow: 0 12px 32px rgba(20, 184, 166, 0.25);
        }
        
        .preview-image-item img {
            width: 100%;
            height: auto;
            max-height: 400px;
            object-fit: contain;
            background: #f3f4f6;
        }
        
        .preview-modal .btn {
            font-weight: 700;
            padding: 14px 28px;
            border-radius: 14px;
            font-size: 1.05rem;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .preview-modal .btn-success {
            background: linear-gradient(135deg, #10b981, #059669);
            border: none;
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
        }
        
        .preview-modal .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 28px rgba(16, 185, 129, 0.5);
        }
        
        .preview-modal .btn-outline-secondary {
            border: 2px solid #d1d5db;
            color: #6b7280;
            background: white;
        }
        
        .preview-modal .btn-outline-secondary:hover {
            background: #f3f4f6;
            border-color: #9ca3af;
            color: #374151;
        }
        
        /* Quick Location Presets */
        .location-presets {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 12px;
        }
        
        .location-preset-btn {
            padding: 8px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 20px;
            background: white;
            color: #6b7280;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .location-preset-btn:hover {
            border-color: #14b8a6;
            background: #f0fdf4;
            color: #14b8a6;
        }
        
        /* AI Confirmation Modal Styles - Compact Version */
        .ai-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(8px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            animation: fadeIn 0.3s ease;
            padding: 16px;
        }
        
        .ai-modal-content {
            background: white;
            border-radius: 20px;
            max-width: 400px; /* Reduced width */
            width: 100%;
            padding: 0;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.4s ease;
            overflow: hidden;
        }
        
        .ai-modal-header {
            background: linear-gradient(135deg, #14b8a6, #0d9488);
            color: white;
            padding: 16px; /* Reduced padding */
            text-align: center;
        }
        
        .ai-modal-header i {
            font-size: 2.25rem; /* Reduced icon size */
            animation: bounce 1s infinite;
        }
        
        .ai-modal-header h4 {
            font-size: 1.25rem; /* Reduced font size */
            margin-top: 0.5rem !important;
        }

        .ai-modal-body {
            padding: 20px 16px; /* Reduced padding */
        }
        
        .ai-result-card {
            background: #f0fdf4;
            border: 2px solid #14b8a6;
            border-radius: 12px;
            padding: 16px; /* Reduced padding */
            text-align: center;
            margin-bottom: 16px; /* Reduced margin */
        }
        
        .ai-result-icon {
            font-size: 2.5rem; /* Reduced icon size */
            margin-bottom: 8px;
        }
        
        .ai-result-card h3 {
            font-size: 1.5rem; /* Reduced font size */
        }

        .ai-confidence {
            display: inline-block;
            background: white;
            padding: 6px 12px; /* Reduced padding */
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem; /* Reduced font size */
            color: #14b8a6;
            margin-top: 6px;
        }
        
        .ai-modal-actions {
            display: flex;
            gap: 10px;
            margin-top: 16px; /* Reduced margin */
        }
        
        .ai-modal-btn {
            flex: 1;
            padding: 12px; /* Reduced padding */
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.95rem; /* Slightly reduced font size */
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        
        .ai-modal-btn:active {
            transform: scale(0.95);
        }
        
        .btn-confirm {
            background: #14b8a6;
            color: white;
        }
        
        .btn-confirm:hover {
            background: #0d9488;
        }
        
        .btn-correct {
            background: #f59e0b;
            color: white;
        }
        
        .btn-correct:hover {
            background: #d97706;
        }

        /* Mobile specific adjustments for AI Modal */
        @media (max-width: 576px) {
            .ai-modal-content {
                max-width: 340px; /* Even smaller width for mobile */
                border-radius: 16px;
            }
            
            .ai-modal-header {
                padding: 12px;
            }
            
            .ai-modal-header i {
                font-size: 1.75rem;
            }
            
            .ai-modal-header h4 {
                font-size: 1.1rem;
            }
            
            .ai-modal-header p {
                font-size: 0.8rem !important;
                margin-top: 4px !important;
            }
            
            .ai-modal-body {
                padding: 16px 12px;
            }
            
            .ai-result-card {
                padding: 12px;
                margin-bottom: 12px;
            }
            
            .ai-result-icon {
                font-size: 2rem;
                margin-bottom: 6px;
            }
            
            .ai-result-card h3 {
                font-size: 1.25rem;
                margin-bottom: 4px !important;
            }
            
            .ai-confidence {
                padding: 4px 10px;
                font-size: 0.75rem;
                margin-top: 4px;
            }
            
            .ai-modal-actions {
                margin-top: 12px;
                gap: 8px;
            }
            
            .ai-modal-btn {
                padding: 10px;
                font-size: 0.85rem;
                border-radius: 8px;
            }
            
            .ai-result-card small, 
            .ai-result-card p,
            .text-center p,
            .text-center small {
                font-size: 0.8rem !important;
            }
        }
        
        /* Thank You Modal Styles - Minimalis & Modern */
        .thank-you-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            animation: fadeIn 0.2s ease;
            padding: 16px;
            cursor: pointer;
        }
        
        .thank-you-content {
            background: white;
            border-radius: 20px;
            max-width: 400px;
            width: 100%;
            padding: 32px 24px;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            animation: slideUp 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            position: relative;
            cursor: default;
        }
        
        .thank-you-close {
            position: absolute;
            top: 12px;
            right: 12px;
            width: 32px;
            height: 32px;
            border: none;
            background: #f3f4f6;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: #6b7280;
            font-size: 0.875rem;
            transition: all 0.2s ease;
        }
        
        .thank-you-close:hover {
            background: #e5e7eb;
            color: #1f2937;
            transform: rotate(90deg);
        }
        
        .thank-you-icon-wrapper {
            margin-bottom: 20px;
        }
        
        .thank-you-icon {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            color: white;
            font-size: 1.75rem;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
            animation: checkMark 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }
        
        .thank-you-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 12px;
        }
        
        .thank-you-message {
            font-size: 0.9375rem;
            color: #6b7280;
            margin-bottom: 24px;
            line-height: 1.5;
        }
        
        .thank-you-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
        }
        
        .thank-you-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.9375rem;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .thank-you-btn:active {
            transform: scale(0.97);
        }
        
        .btn-primary-action {
            background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(20, 184, 166, 0.3);
        }
        
        .btn-primary-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(20, 184, 166, 0.4);
            color: white;
        }
        
        .btn-secondary-action {
            background: #f3f4f6;
            color: #374151;
        }
        
        .btn-secondary-action:hover {
            background: #e5e7eb;
            color: #1f2937;
        }
        
        @keyframes checkMark {
            0% {
                transform: scale(0) rotate(-180deg);
                opacity: 0;
            }
            50% {
                transform: scale(1.1) rotate(10deg);
            }
            100% {
                transform: scale(1) rotate(0deg);
                opacity: 1;
            }
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideUp {
            from {
                transform: translateY(50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        @keyframes scaleUp {
            from {
                transform: scale(0.8);
                opacity: 0;
            }
            to {
                transform: scale(1);
                opacity: 1;
            }
        }
        
        @keyframes slideUp {
            from {
                transform: translateY(20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        
        @keyframes successPulse {
            0% {
                transform: scale(0);
                opacity: 0;
            }
            50% {
                transform: scale(1.2);
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }
        
        /* Form sections that get enabled after AI confirmation */
        .form-disabled {
            pointer-events: none;
            opacity: 0.5;
        }
        
        /* Tags Input System */
        .tags-container {
            min-height: 40px;
            padding: 0.5rem;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            background: #f9fafb;
        }
        
        .tags-container:empty {
            display: none;
        }
        
        .tag-item {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: linear-gradient(135deg, #14b8a6, #0d9488);
            color: white;
            padding: 0.375rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
            animation: tagPop 0.3s ease;
        }
        
        @keyframes tagPop {
            0% { transform: scale(0); opacity: 0; }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); opacity: 1; }
        }
        
        .tag-item i {
            cursor: pointer;
            opacity: 0.8;
            transition: opacity 0.2s;
        }
        
        .tag-item i:hover {
            opacity: 1;
        }
        
        .suggested-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        
        .suggested-tag-btn {
            background: white;
            border: 2px solid #e5e7eb;
            color: #6b7280;
            padding: 0.25rem 0.75rem;
            border-radius: 16px;
            font-size: 0.8125rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .suggested-tag-btn:hover {
            border-color: #14b8a6;
            color: #14b8a6;
            background: #f0fdf4;
            transform: translateY(-1px);
        }
        
        .suggested-tag-btn:active {
            transform: translateY(0);
        }
        
        /* Mobile Responsive Optimizations */
        @media (max-width: 768px) {
            body {
                padding-top: 0;
            }
            
            .lapor-hero {
                padding: 2rem 0;
                border-radius: 0 0 16px 16px;
            }
            
            .lapor-hero h1 {
                font-size: 1.5rem;
            }
            
            .lapor-hero p {
                font-size: 0.875rem;
            }
            
            .progress-indicator {
                padding: 1.25rem;
                border-radius: 12px;
                margin-bottom: 1.5rem;
            }
            
            .progress-indicator::before {
                top: 2rem;
            }
            
            .progress-step-circle {
                width: 42px;
                height: 42px;
                font-size: 1rem;
            }
            
            .progress-step-label {
                font-size: 0.75rem;
            }
            
            .image-upload-options {
                grid-template-columns: 1fr;
                gap: 0.875rem;
            }
            
            .upload-option-btn {
                padding: 1.5rem 1rem;
            }
            
            .form-section {
                padding: 1.5rem;
                border-radius: 12px;
            }
            
            .form-section h5 {
                font-size: 1rem;
            }
            
            .btn {
                padding: 0.875rem 1.25rem;
                font-size: 0.875rem;
            }
        }
        
        @media (max-width: 480px) {
            .lapor-hero {
                padding: 1.5rem 0;
            }
            
            .lapor-hero h1 {
                font-size: 1.25rem;
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .progress-step-circle {
                width: 38px;
                height: 38px;
                font-size: 0.875rem;
            }
            
            .progress-step-label {
                font-size: 0.6875rem;
            }
            
            .form-section {
                padding: 1.25rem;
            }
        }
        
        /* Animation Keyframes */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease;
        }
        
        /* Toast Notification Animations */
        @keyframes slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(400px);
                opacity: 0;
            }
        }
    </style>
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
                        <a class="nav-link active" href="lapor.php">
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
    <div class="container mb-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <!-- Hero Header -->
                <div class="text-center mb-4 mt-4">
                    <h1 style="font-size: clamp(1.75rem, 5vw, 2.5rem); font-weight: 800; color: #0f766e; margin-bottom: 0.75rem; background: linear-gradient(135deg, #0f766e, #14b8a6, #06b6d4); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
                        <i class="fas fa-recycle" style="-webkit-text-fill-color: #14b8a6;"></i> Lapor Sampah Pintar
                    </h1>
                    <p style="font-size: 1.1rem; color: #64748b; max-width: 600px; margin: 0 auto 2rem;">
                        Laporkan sampah dengan bantuan AI yang otomatis mengklasifikasi jenis sampah Anda
                    </p>
                </div>

                        <?php if ($error): ?>
                    <div class="alert alert-danger fade-in">
                                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                            </div>
                        <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success fade-in">
                        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                            </div>
                        <?php endif; ?>

                <!-- AI Model Loading Indicator -->
                <div id="aiModelLoading" style="display: none;" class="mb-3"></div>

                <!-- Progress Indicator -->
                <div class="progress-indicator fade-in">
                    <div class="progress-step active" id="step0">
                        <div class="progress-step-circle">0</div>
                        <div class="progress-step-label">Pelajari Jenis Sampah</div>
                    </div>
                    <div class="progress-step" id="step1">
                        <div class="progress-step-circle">1</div>
                        <div class="progress-step-label">Upload Foto</div>
                    </div>
                    <div class="progress-step" id="step2">
                        <div class="progress-step-circle">2</div>
                        <div class="progress-step-label">Konfirmasi AI</div>
                    </div>
                    <div class="progress-step" id="step3">
                        <div class="progress-step-circle">3</div>
                        <div class="progress-step-label">Isi Detail</div>
                    </div>
                    <div class="progress-step" id="step4">
                        <div class="progress-step-circle">4</div>
                        <div class="progress-step-label">Review & Submit</div>
                    </div>
                </div>

                        <form method="POST" action="" enctype="multipart/form-data" id="laporForm">
                            <!-- STEP 0: Edukasi Jenis Sampah - Minimalis -->
                            <div class="form-section mb-4" id="section-edukasi" style="background: white; border-radius: 12px; padding: 1.25rem; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06); border: 1px solid #e5e7eb;">
                                <h6 class="mb-3" style="font-weight: 600; color: #1f2937; font-size: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                                    <i class="fas fa-book-open" style="color: #14b8a6; font-size: 1rem;"></i>
                                    Pelajari Jenis Sampah
                                </h6>
                                
                                <div class="alert alert-info mb-3" style="background: #eff6ff; border: 1px solid #93c5fd; border-radius: 8px; padding: 0.75rem; font-size: 0.875rem;">
                                    <small style="color: #1e40af; margin: 0;">
                                        <i class="fas fa-info-circle"></i> Sebelum melanjutkan, pelajari jenis-jenis sampah di bawah ini.
                                    </small>
                                </div>

                                <!-- Jenis Sampah Cards - Minimalis -->
                                <div class="row g-2 mb-3">
                                    <!-- Organik -->
                                    <div class="col-md-4">
                                        <div class="card h-100" style="border: 1px solid #10b981; border-radius: 8px; overflow: hidden;">
                                            <div class="card-body" style="padding: 1rem;">
                                                <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem;">
                                                    <div style="width: 40px; height: 40px; background: #10b981; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                                        <i class="fas fa-leaf text-white" style="font-size: 1.125rem;"></i>
                                                    </div>
                                                    <h6 style="color: #065f46; font-weight: 700; margin: 0; font-size: 0.9375rem;">ORGANIK</h6>
                                                </div>
                                                <p style="color: #047857; font-size: 0.8125rem; margin-bottom: 0.75rem; line-height: 1.4;">Sampah yang dapat terurai secara alami</p>
                                                
                                                <div style="background: #f0fdf4; padding: 0.75rem; border-radius: 6px; margin-bottom: 0.75rem;">
                                                    <small style="color: #047857; font-weight: 600; font-size: 0.75rem; display: block; margin-bottom: 0.5rem;">Contoh:</small>
                                                    <div style="color: #059669; font-size: 0.75rem; line-height: 1.6;">
                                                        🌿 Daun, rumput • 🍎 Sisa makanan • 🥬 Buah/sayur busuk • 🪵 Kayu • 🦴 Tulang
                                                    </div>
                                                </div>
                                                
                                                <div style="background: #dcfce7; padding: 0.5rem; border-radius: 6px;">
                                                    <small style="color: #065f46; font-size: 0.75rem;">
                                                        <i class="fas fa-recycle"></i> Dapat dijadikan kompos
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Anorganik -->
                                    <div class="col-md-4">
                                        <div class="card h-100" style="border: 1px solid #3b82f6; border-radius: 8px; overflow: hidden;">
                                            <div class="card-body" style="padding: 1rem;">
                                                <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem;">
                                                    <div style="width: 40px; height: 40px; background: #3b82f6; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                                        <i class="fas fa-recycle text-white" style="font-size: 1.125rem;"></i>
                                                    </div>
                                                    <h6 style="color: #1e40af; font-weight: 700; margin: 0; font-size: 0.9375rem;">ANORGANIK</h6>
                                                </div>
                                                <p style="color: #2563eb; font-size: 0.8125rem; margin-bottom: 0.75rem; line-height: 1.4;">Sampah yang tidak dapat terurai secara alami</p>
                                                
                                                <div style="background: #eff6ff; padding: 0.75rem; border-radius: 6px; margin-bottom: 0.75rem;">
                                                    <small style="color: #2563eb; font-weight: 600; font-size: 0.75rem; display: block; margin-bottom: 0.5rem;">Contoh:</small>
                                                    <div style="color: #3b82f6; font-size: 0.75rem; line-height: 1.6;">
                                                        🥤 Plastik • 📦 Kertas • 🥫 Kaleng • 🍶 Kaca • 📱 Elektronik
                                                    </div>
                                                </div>
                                                
                                                <div style="background: #dbeafe; padding: 0.5rem; border-radius: 6px;">
                                                    <small style="color: #1e40af; font-size: 0.75rem;">
                                                        <i class="fas fa-redo"></i> Dapat didaur ulang
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- B3 -->
                                    <div class="col-md-4">
                                        <div class="card h-100" style="border: 1px solid #ef4444; border-radius: 8px; overflow: hidden;">
                                            <div class="card-body" style="padding: 1rem;">
                                                <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem;">
                                                    <div style="width: 40px; height: 40px; background: #ef4444; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                                        <i class="fas fa-exclamation-triangle text-white" style="font-size: 1.125rem;"></i>
                                                    </div>
                                                    <h6 style="color: #991b1b; font-weight: 700; margin: 0; font-size: 0.9375rem;">B3</h6>
                                                </div>
                                                <p style="color: #dc2626; font-size: 0.8125rem; margin-bottom: 0.75rem; line-height: 1.4;">Bahan Berbahaya & Beracun</p>
                                                
                                                <div style="background: #fef2f2; padding: 0.75rem; border-radius: 6px; margin-bottom: 0.75rem;">
                                                    <small style="color: #dc2626; font-weight: 600; font-size: 0.75rem; display: block; margin-bottom: 0.5rem;">Contoh:</small>
                                                    <div style="color: #ef4444; font-size: 0.75rem; line-height: 1.6;">
                                                        🔋 Baterai • 💡 Lampu neon • 🛢️ Oli bekas • 🧪 Bahan kimia • ☣️ Pestisida
                                                    </div>
                                                </div>
                                                
                                                <div style="background: #fee2e2; padding: 0.5rem; border-radius: 6px;">
                                                    <small style="color: #991b1b; font-size: 0.75rem;">
                                                        <i class="fas fa-exclamation-circle"></i> Berbahaya! Butuh penanganan khusus
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Tips Minimalis -->
                                <div class="alert alert-warning mb-3" style="background: #fffbeb; border: 1px solid #fcd34d; border-radius: 8px; padding: 0.75rem;">
                                    <small style="color: #78350f; font-size: 0.8125rem; line-height: 1.5;">
                                        <i class="fas fa-lightbulb" style="color: #f59e0b;"></i> 
                                        <strong>Tips:</strong> AI akan otomatis mengklasifikasi setelah upload. Jika salah, Anda bisa koreksi manual. Pastikan foto jelas.
                                    </small>
                                </div>

                                <!-- Continue Button - Minimalis -->
                                <div class="text-center">
                                    <button type="button" class="btn btn-success" id="btn-continue-edukasi" onclick="continueToUpload()" style="padding: 0.625rem 1.5rem; font-size: 0.9375rem; font-weight: 600; border-radius: 8px;">
                                        <i class="fas fa-check-circle me-2"></i> Lanjutkan
                                    </button>
                                </div>
                            </div>

                            <!-- STEP 1: Upload Gambar -->
                            <div class="form-section mb-4" id="section-upload" style="background: white; border-radius: 24px; padding: 2rem; box-shadow: 0 8px 32px rgba(20, 184, 166, 0.12); border: 2px solid rgba(20, 184, 166, 0.1); display: none;">
                                <h5 class="mb-4" style="font-weight: 700; color: #0f766e; font-size: 1.25rem; display: flex; align-items: center; gap: 10px;">
                                    <span style="background: linear-gradient(135deg, #14b8a6, #0d9488); color: white; width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-camera"></i>
                                    </span>
                                    Step 1: Upload Foto Sampah
                                </h5>
                                
                                <!-- Upload Options -->
                                <div class="image-upload-options">
                                    <div class="upload-option-btn" onclick="document.getElementById('gambar').click()">
                                        <div><i class="fas fa-image"></i></div>
                                        <div class="option-label">Pilih dari Galeri</div>
                                        <div class="option-hint">Upload foto yang sudah ada</div>
                                    </div>
                                    <div class="upload-option-btn" onclick="document.getElementById('cameraInput').click()">
                                        <div><i class="fas fa-camera"></i></div>
                                        <div class="option-label">Ambil Foto</div>
                                        <div class="option-hint">Gunakan kamera langsung</div>
                                    </div>
                            </div>
                                
                                <!-- Hidden file inputs -->
                                <input type="file" id="gambar" name="gambar" accept="image/*" style="display: none;" required>
                                <input type="file" id="cameraInput" accept="image/*" capture="environment" style="display: none;">
                                
                                <!-- Info Box Helper -->
                                <div class="info-box" style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border: 2px solid #fbbf24; border-radius: 16px; padding: 1.25rem; margin-top: 1rem;">
                                    <h6 style="color: #92400e; font-weight: 700; margin-bottom: 0.75rem;">
                                        <i class="fas fa-lightbulb" style="color: #f59e0b;"></i> Tips Upload Foto
                                    </h6>
                                    <ul style="margin: 0; padding-left: 1.5rem; color: #78350f;">
                                        <li style="margin-bottom: 0.5rem;">✨ Pastikan sampah terlihat jelas dalam foto</li>
                                        <li style="margin-bottom: 0.5rem;">🤖 AI akan otomatis mengklasifikasi jenis sampah</li>
                                        <li>✅ Anda perlu konfirmasi hasil AI setelah upload</li>
                                    </ul>
                                </div>

                            <!-- Image Preview (Enhanced) -->
                                <div class="mb-4" id="imagePreviewContainer" style="display: none;">
                                    <div class="image-preview-card">
                                        <div class="image-preview-header">
                                            <div class="image-preview-title">
                                                <i class="fas fa-image"></i> Preview Gambar
                                            </div>
                                            <div class="image-preview-actions">
                                                <button type="button" class="btn-preview-action" onclick="zoomPreview()" title="Zoom Fullscreen">
                                                    <i class="fas fa-search-plus"></i>
                                                </button>
                                                <button type="button" class="btn-preview-action" onclick="changeImage()" title="Ganti Gambar">
                                                    <i class="fas fa-sync-alt"></i> Ganti
                                                </button>
                                            </div>
                                        </div>
                                        <div class="image-preview-body">
                                            <img id="imagePreview" class="image-preview-img" alt="Preview" onclick="zoomPreview()" />
                                            <div class="image-preview-overlay">
                                                <i class="fas fa-search-plus"></i> Klik untuk zoom
                                            </div>
                                        </div>
                                        <div class="image-preview-info">
                                            <div class="info-item">
                                                <i class="fas fa-file-image"></i>
                                                <span id="fileName">-</span>
                                            </div>
                                            <div class="info-item">
                                                <i class="fas fa-weight"></i>
                                                <span id="fileSize">-</span>
                                            </div>
                                            <div class="info-item">
                                                <i class="fas fa-ruler-combined"></i>
                                                <span id="imageDimensions">-</span>
                                            </div>
                                        </div>
                                    </div>
                            </div>
                            
                            <!-- Fullscreen Zoom Modal -->
                            <div id="zoomModal" class="zoom-modal" style="display: none;" onclick="closeZoom()">
                                <span class="zoom-close">&times;</span>
                                <div class="zoom-content">
                                    <img id="zoomedImage" class="zoom-image" alt="Zoomed Preview">
                                    <div class="zoom-controls">
                                        <button onclick="event.stopPropagation(); zoomIn()" class="zoom-btn">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                        <button onclick="event.stopPropagation(); zoomOut()" class="zoom-btn">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                        <button onclick="event.stopPropagation(); resetZoom()" class="zoom-btn">
                                            <i class="fas fa-sync"></i>
                                        </button>
                                    </div>
                                    <div class="zoom-hint">
                                        <small>Scroll untuk zoom | Drag untuk geser | ESC untuk tutup</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Loading Spinner -->
                                <div class="spinner-container" id="loadingSpinner" style="display: none;">
                                <div class="spinner-border" role="status"></div>
                                <p class="mt-2">Menganalisis gambar dengan AI...</p>
                            </div>

                            <!-- Classification Result -->
                            <div id="classificationResult" style="display: none;"></div>

                                <!-- Confirmation Status -->
                                <div id="confirmationStatus" style="display: none;" class="mt-3"></div>
                            </div>

                            <!-- Manual Correction Section (Hidden, shown in modal) -->
                            <div id="correctionSection" style="display: none;" class="mb-3">
                                <div class="card" style="border: 2px solid #ff9800;">
                                    <div class="card-header" style="background: #fff3e0; color: #f57c00;">
                                        <i class="fas fa-edit"></i> Koreksi Manual (AI Salah)
                                    </div>
                                    <div class="card-body">
                                        <!-- Info Box Bantuan -->
                                        <div class="alert alert-info" style="background-color: #e3f2fd; border-left: 4px solid #2196f3; margin-bottom: 1rem;">
                                            <h6 style="color: #1976d2; margin-bottom: 0.5rem;">
                                                <i class="fas fa-question-circle"></i> Tidak Yakin Kategori yang Benar?
                                            </h6>
                                            <p style="margin-bottom: 0.5rem; font-size: 0.9rem;">
                                                <strong>Opsi 1:</strong> Klik "Batal" dan lanjutkan. Pilih <strong>"Tidak Diketahui"</strong> di jenis sampah nanti.
                                            </p>
                                            <p style="margin-bottom: 0; font-size: 0.9rem;">
                                                <strong>Opsi 2:</strong> Pilih kategori yang paling mendekati + tulis catatan untuk Admin.
                                            </p>
                                        </div>
                                        
                                        <p class="text-muted mb-3">
                                            <i class="fas fa-hand-point-right"></i> Pilih kategori yang benar:
                                        </p>
                                        <div class="row">
                                            <div class="col-md-4 mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="manual_kategori" 
                                                           id="manual_organik" value="organik">
                                                    <label class="form-check-label" for="manual_organik">
                                                        <i class="fas fa-leaf text-success"></i> <strong>Organik</strong>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-md-4 mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="manual_kategori" 
                                                           id="manual_anorganik" value="anorganik">
                                                    <label class="form-check-label" for="manual_anorganik">
                                                        <i class="fas fa-recycle text-primary"></i> <strong>Anorganik</strong>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-md-4 mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="manual_kategori" 
                                                           id="manual_b3" value="b3">
                                                    <label class="form-check-label" for="manual_b3">
                                                        <i class="fas fa-exclamation-triangle text-danger"></i> <strong>B3</strong>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mt-3">
                                            <label for="correction_note" class="form-label">
                                                <i class="fas fa-comment-dots"></i> Catatan untuk Admin (Opsional)
                                            </label>
                                            <textarea class="form-control" id="correction_note" name="correction_note" 
                                                      rows="3" placeholder="Contoh: Saya tidak yakin, tapi sepertinya ini kardus bekas makanan. Mohon admin cek kembali."></textarea>
                                            <div class="form-text">
                                                <i class="fas fa-lightbulb"></i> Tulis catatan jika Anda tidak 100% yakin. Admin akan membantu memverifikasi.
                                            </div>
                                        </div>
                                        <div class="mt-3 d-grid gap-2">
                                            <button type="button" class="btn btn-warning" id="applyCorrection">
                                                <i class="fas fa-check"></i> Terapkan Koreksi
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary" id="cancelCorrection">
                                                <i class="fas fa-times"></i> Batal
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Hidden inputs for classification -->
                            <input type="hidden" id="kategori" name="kategori" required>
                            <input type="hidden" id="confidence" name="confidence">
                            <input type="hidden" id="ai_prediction" name="ai_prediction">
                            <input type="hidden" id="is_corrected" name="is_corrected" value="0">

                            <!-- STEP 3: Isi Detail -->
                            <div class="form-section form-disabled" id="section-details" style="background: white; border-radius: 24px; padding: 2rem; box-shadow: 0 8px 32px rgba(20, 184, 166, 0.12); border: 2px solid rgba(20, 184, 166, 0.1);">
                                <h5 class="mb-4" style="font-weight: 700; color: #0f766e; font-size: 1.25rem; display: flex; align-items: center; gap: 10px;">
                                    <span style="background: linear-gradient(135deg, #14b8a6, #0d9488); color: white; width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-edit"></i>
                                    </span>
                                    Step 3: Isi Detail Laporan
                                </h5>

                            <!-- Jenis Sampah Detail -->
                            <div class="mb-3">
                                <label for="jenis_sampah" class="form-label">
                                        <i class="fas fa-tag"></i> Jenis Sampah Spesifik <span class="text-danger">*</span>
                                </label>
                                <select class="form-select select2-jenis-sampah" id="jenis_sampah" name="jenis_sampah" required>
                                    <option value="">-- Pilih atau Cari Jenis Sampah --</option>
                                    
                                    <optgroup label="🌿 ORGANIK">
                                        <option value="daun">🍃 Daun / Ranting / Rumput</option>
                                        <option value="makanan">🍎 Sisa Makanan</option>
                                        <option value="buah_sayur">🥬 Buah / Sayur Busuk</option>
                                        <option value="kayu">🪵 Kayu</option>
                                        <option value="kotoran_hewan">🐾 Kotoran Hewan</option>
                                        <option value="tulang">🦴 Tulang</option>
                                        <option value="kulit_telur">🥚 Kulit Telur</option>
                                        <option value="ampas_kopi">☕ Ampas Kopi / Teh</option>
                                        <option value="kotoran_dapur">🍴 Sampah Dapur</option>
                                        <option value="kertas_tisu">🧻 Tisu / Kertas Basah</option>
                                        <option value="serbuk_gergaji">🪚 Serbuk Gergaji</option>
                                    </optgroup>
                                    
                                    <optgroup label="♻️ PLASTIK">
                                        <option value="botol_plastik">🍾 Botol Plastik (PET)</option>
                                        <option value="kantong_plastik">🛍️ Kantong Plastik / Kresek</option>
                                        <option value="gelas_plastik">🥤 Gelas / Cup Plastik</option>
                                        <option value="sedotan_plastik">🥤 Sedotan Plastik</option>
                                        <option value="styrofoam">📦 Styrofoam</option>
                                        <option value="kemasan_plastik">📦 Kemasan Plastik</option>
                                        <option value="ember_plastik">🪣 Ember / Wadah Plastik</option>
                                        <option value="mainan_plastik">🧸 Mainan Plastik</option>
                                        <option value="jerigen_plastik">🧴 Jerigen Plastik</option>
                                        <option value="plastik_lainnya">♻️ Plastik Lainnya</option>
                                    </optgroup>
                                    
                                    <optgroup label="📄 KERTAS & KARDUS">
                                        <option value="kertas">📄 Kertas</option>
                                        <option value="koran">📰 Koran / Majalah</option>
                                        <option value="buku">📚 Buku Bekas</option>
                                        <option value="karton">📦 Kardus / Karton</option>
                                        <option value="kertas_kantor">🗂️ Kertas Kantor</option>
                                        <option value="amplop">✉️ Amplop</option>
                                        <option value="dus_bekas">📦 Dus Bekas</option>
                                    </optgroup>
                                    
                                    <optgroup label="🔩 LOGAM">
                                        <option value="kaleng_minuman">🥫 Kaleng Minuman (Aluminium)</option>
                                        <option value="kaleng_makanan">🥫 Kaleng Makanan</option>
                                        <option value="logam">🔩 Logam / Besi</option>
                                        <option value="kawat">🔗 Kawat</option>
                                        <option value="paku">🔨 Paku / Sekrup</option>
                                        <option value="seng">🏗️ Seng / Besi Bekas</option>
                                        <option value="foil_aluminium">🎁 Aluminium Foil</option>
                                    </optgroup>
                                    
                                    <optgroup label="🪟 KACA">
                                        <option value="botol_kaca">🍾 Botol Kaca</option>
                                        <option value="pecahan_kaca">🪟 Pecahan Kaca</option>
                                        <option value="cermin">🪞 Cermin Bekas</option>
                                        <option value="stoples_kaca">🫙 Stoples / Toples Kaca</option>
                                    </optgroup>
                                    
                                    <optgroup label="👕 TEKSTIL & PAKAIAN">
                                        <option value="kain">👕 Kain / Pakaian Bekas</option>
                                        <option value="sepatu">👟 Sepatu Bekas</option>
                                        <option value="tas">🎒 Tas Bekas</option>
                                        <option value="selimut">🛏️ Selimut / Sprei</option>
                                        <option value="boneka">🧸 Boneka Kain</option>
                                        <option value="topi">🧢 Topi Bekas</option>
                                    </optgroup>
                                    
                                    <optgroup label="🛞 KARET & KULIT">
                                        <option value="ban_bekas">🛞 Ban Bekas</option>
                                        <option value="sandal_karet">🩴 Sandal Karet</option>
                                        <option value="sarung_tangan">🧤 Sarung Tangan Karet</option>
                                        <option value="balon">🎈 Balon Karet</option>
                                    </optgroup>
                                    
                                    <optgroup label="🏗️ LAINNYA">
                                        <option value="pipa_pralon">🔧 Pipa / Pralon</option>
                                        <option value="keramik">🏺 Keramik Pecah</option>
                                        <option value="bata">🧱 Bata / Puing</option>
                                        <option value="karpet">🧶 Karpet Bekas</option>
                                        <option value="kasur">🛏️ Kasur Bekas</option>
                                        <option value="furniture">🪑 Furniture Bekas</option>
                                        <option value="gabus">📦 Gabus / Packing</option>
                                        <option value="lilin">🕯️ Lilin Bekas</option>
                                    </optgroup>
                                    
                                    <optgroup label="⚠️ B3 - ELEKTRONIK">
                                        <option value="elektronik">📱 Elektronik / E-Waste</option>
                                        <option value="hp_bekas">📱 HP / Smartphone Bekas</option>
                                        <option value="komputer">💻 Komputer / Laptop</option>
                                        <option value="tv_bekas">📺 TV / Monitor Bekas</option>
                                        <option value="kabel_elektronik">🔌 Kabel Elektronik</option>
                                        <option value="charger">🔌 Charger / Adaptor</option>
                                        <option value="baterai">🔋 Baterai</option>
                                        <option value="aki">🔋 Aki / Accu Bekas</option>
                                        <option value="lampu">💡 Lampu / Bohlam</option>
                                    </optgroup>
                                    
                                    <optgroup label="⚠️ B3 - KIMIA & BERBAHAYA">
                                        <option value="oli">🛢️ Oli / Minyak Bekas</option>
                                        <option value="cat">🎨 Cat / Thinner</option>
                                        <option value="obat">💊 Obat Kadaluarsa</option>
                                        <option value="pestisida">☠️ Pestisida / Racun</option>
                                        <option value="semprot_serangga">🪰 Obat Nyamuk / Semprot</option>
                                        <option value="tinta_printer">🖨️ Tinta / Toner Printer</option>
                                        <option value="kaleng_aerosol">💨 Kaleng Aerosol / Spray</option>
                                        <option value="termometer">🌡️ Termometer Raksa</option>
                                    </optgroup>
                                    
                                    <optgroup label="😷 MEDIS & KESEHATAN">
                                        <option value="masker">😷 Masker Bekas</option>
                                        <option value="jarum_suntik">💉 Jarum Suntik</option>
                                        <option value="sarung_tangan_medis">🧤 Sarung Tangan Medis</option>
                                        <option value="perban">🩹 Perban / Plester</option>
                                    </optgroup>
                                    
                                    <optgroup label="❓ TIDAK YAKIN">
                                        <option value="tidak_diketahui">❓ Tidak Diketahui / Tidak Yakin</option>
                                        <option value="lainnya">🔸 Lainnya</option>
                                    </optgroup>
                                </select>
                                <div class="form-text">
                                    <i class="fas fa-info-circle"></i> Pilih "Tidak Diketahui" jika Anda tidak yakin jenis sampahnya. Admin akan membantu mengidentifikasi.
                                </div>
                            </div>

                            <!-- Deskripsi -->
                            <div class="mb-3">
                                <label for="deskripsi" class="form-label">
                                        <i class="fas fa-comment"></i> Deskripsi & Catatan (Opsional)
                                </label>
                                <textarea class="form-control" id="deskripsi" name="deskripsi" 
                                              rows="3" placeholder="Contoh: Sampah ini ditemukan di depan warung. Saya tidak yakin kategorinya, mohon admin periksa."></textarea>
                                <div class="form-text">
                                    <i class="fas fa-info-circle"></i> Tulis catatan jika Anda tidak yakin dengan jenis sampah. Admin akan membantu mengidentifikasi.
                                </div>
                            </div>

                                <!-- Quick Location Presets -->
                                <?php if ($savedLocations): ?>
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-map-pin"></i> Lokasi Tersimpan
                                    </label>
                                    <div class="location-presets">
                                        <?php foreach ($savedLocations as $loc): ?>
                                        <button type="button" class="location-preset-btn" 
                                                onclick="usePresetLocation(<?php echo $loc['latitude']; ?>, <?php echo $loc['longitude']; ?>, '<?php echo htmlspecialchars(addslashes($loc['address'])); ?>')">
                                            <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($loc['name']); ?>
                                        </button>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php endif; ?>

                            <!-- Lokasi -->
                            <div class="mb-3">
                                <label class="form-label">
                                        <i class="fas fa-map-marker-alt"></i> Lokasi Sampah <span class="text-danger">*</span>
                                </label>
                                <div class="btn-group w-100 mb-2" role="group">
                                    <button type="button" class="btn btn-outline-success" id="useMyLocation">
                                            <i class="fas fa-crosshairs"></i> Lokasi Saya
                                    </button>
                                    <button type="button" class="btn btn-outline-success" id="useCurrentLocation">
                                            <i class="fas fa-location-arrow"></i> Deteksi GPS
                                    </button>
                                </div>
                                    <div id="reportMap" style="height: 300px; border-radius: 12px; border: 2px solid #e5e7eb;"></div>
                                <input type="hidden" id="lokasi_latitude" name="lokasi_latitude" required>
                                <input type="hidden" id="lokasi_longitude" name="lokasi_longitude" required>
                                    <small class="text-muted mt-2 d-block">
                                        <i class="fas fa-info-circle"></i> Klik pada peta atau tarik marker untuk mengubah lokasi
                                    </small>
                            </div>

                            <!-- Alamat Lokasi -->
                            <div class="mb-3">
                                <label for="alamat_lokasi" class="form-label">
                                        <i class="fas fa-home"></i> Alamat Lokasi <span class="text-danger">*</span>
                                </label>
                                    <div class="input-group">
                                <textarea class="form-control" id="alamat_lokasi" name="alamat_lokasi" 
                                                  rows="2" required placeholder="Alamat akan terisi otomatis dari GPS..."></textarea>
                                        <button type="button" class="btn btn-outline-secondary" id="reverseGeocodeBtn" title="Auto-fill dari GPS">
                                            <i class="fas fa-sync-alt"></i>
                                        </button>
                                    </div>
                                    <small class="text-muted">
                                        <i class="fas fa-magic"></i> Alamat akan terisi otomatis saat Anda memilih lokasi GPS
                                    </small>
                            </div>

                                <!-- Nomor WhatsApp -->
                                <div class="mb-3">
                                    <label for="whatsapp_number" class="form-label">
                                        <i class="fab fa-whatsapp"></i> Nomor WhatsApp <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-success text-white">
                                            <i class="fab fa-whatsapp"></i>
                                        </span>
                                        <input type="tel" class="form-control" id="whatsapp_number" name="whatsapp_number" 
                                               required placeholder="Contoh: 08123456789 atau +628123456789"
                                               pattern="^(\+62|62|0)[0-9]{9,13}$"
                                               title="Format: 08xxx atau +628xxx atau 628xxx">
                                    </div>
                                    <small class="text-muted">
                                        <i class="fas fa-info-circle"></i> Nomor WhatsApp akan digunakan untuk notifikasi status laporan
                                    </small>
                                </div>

                                <!-- Save Location Option -->
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="save_location" name="save_location" value="1">
                                        <label class="form-check-label" for="save_location">
                                            <i class="fas fa-bookmark"></i> Simpan lokasi ini untuk penggunaan cepat
                                        </label>
                                    </div>
                                    <div id="locationNameDiv" style="display: none;" class="mt-2">
                                        <input type="text" class="form-control form-control-sm" id="location_name" name="location_name" 
                                               placeholder="Nama lokasi (contoh: Rumah, Kantor, dsb)">
                                    </div>
                                </div>

                                <!-- Tags untuk Analitik -->
                                <div class="mb-4">
                                    <label for="tags_input" class="form-label">
                                        <i class="fas fa-tags"></i> Tags (Opsional - untuk analitik data)
                                    </label>
                                    <div id="tagsContainer" class="tags-container mb-2"></div>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="tags_input" 
                                               placeholder="Ketik tag dan tekan Enter (contoh: tumpukan, jalanan, bau, dll)">
                                        <button type="button" class="btn btn-outline-success" id="addTagBtn">
                                            <i class="fas fa-plus"></i> Tambah
                                        </button>
                                    </div>
                                    <input type="hidden" id="tags" name="tags" value="">
                                    <div class="form-text">
                                        <i class="fas fa-info-circle"></i> Tags membantu admin menganalisis pola sampah. Contoh: basah, berbau, menumpuk, dll.
                                    </div>
                                    <!-- Suggested Tags -->
                                    <div class="mt-2">
                                        <small class="text-muted d-block mb-1"><i class="fas fa-lightbulb"></i> Saran tags:</small>
                                        <div class="suggested-tags">
                                            <button type="button" class="suggested-tag-btn" data-tag="menumpuk">menumpuk</button>
                                            <button type="button" class="suggested-tag-btn" data-tag="berbau">berbau</button>
                                            <button type="button" class="suggested-tag-btn" data-tag="jalanan">jalanan</button>
                                            <button type="button" class="suggested-tag-btn" data-tag="rumah">rumah</button>
                                            <button type="button" class="suggested-tag-btn" data-tag="basah">basah</button>
                                            <button type="button" class="suggested-tag-btn" data-tag="kering">kering</button>
                                            <button type="button" class="suggested-tag-btn" data-tag="besar">besar</button>
                                            <button type="button" class="suggested-tag-btn" data-tag="kecil">kecil</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Submit Buttons -->
                            <div class="d-grid gap-2">
                                <button type="button" class="btn btn-primary btn-lg" id="reviewSubmitBtn" disabled>
                                    <i class="fas fa-check-circle"></i> Review & Kirim Laporan
                                </button>
                                <a href="dashboard.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left"></i> Kembali
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Info Cards -->
                <div class="row mt-4">
                    <div class="col-md-4 mb-3">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-leaf fa-3x text-success mb-2"></i>
                                <h6>Organik</h6>
                                <small class="text-muted">Sampah yang dapat terurai secara alami</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-recycle fa-3x text-primary mb-2"></i>
                                <h6>Anorganik</h6>
                                <small class="text-muted">Sampah yang sulit terurai (plastik, logam)</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-exclamation-triangle fa-3x text-danger mb-2"></i>
                                <h6>B3</h6>
                                <small class="text-muted">Bahan Berbahaya & Beracun</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- AI Confirmation Modal -->
    <div id="aiConfirmationModal" style="display: none;">
        <!-- Modal content will be injected by JavaScript -->
    </div>
    
    <!-- Preview Modal -->
    <div id="previewModal" style="display: none;">
        <!-- Modal content will be injected by JavaScript -->
    </div>
    
    <!-- Thank You Modal - Minimalis & Modern -->
    <?php if ($success): ?>
    <div id="thankYouModal" class="thank-you-modal-overlay" onclick="if(event.target === this) window.location.href='dashboard.php'">
        <div class="thank-you-content" onclick="event.stopPropagation()">
            <button class="thank-you-close" onclick="window.location.href='dashboard.php'" aria-label="Close">
                <i class="fas fa-times"></i>
            </button>
            <div class="thank-you-icon-wrapper">
                <div class="thank-you-icon">
                    <i class="fas fa-check"></i>
                </div>
            </div>
            <h3 class="thank-you-title">Laporan Terkirim!</h3>
            <p class="thank-you-message">
                Laporan Anda telah berhasil dikirim dan akan segera diproses. Terima kasih atas kontribusi Anda! 🌱
            </p>
            <div class="thank-you-actions">
                <a href="dashboard.php" class="thank-you-btn btn-primary-action">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="lapor.php" class="thank-you-btn btn-secondary-action">
                    <i class="fas fa-plus"></i> Lapor Lagi
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Footer -->
    <div class="footer">
        <p>&copy; 2024 Aplikasi Pelaporan Sampah dengan AI | Powered by Teachable Machine</p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs@latest/dist/tf.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@teachablemachine/image@latest/dist/teachablemachine-image.min.js"></script>
    <script src="../assets/js/navbar.js"></script>
    <script src="../assets/js/classifier.js?v=<?php echo time(); ?>"></script>
    <script>

        let aiConfirmed = false;
        let currentPrediction = null;
        let allPredictions = null;
        let currentStep = 0; // Start at step 0 (edukasi)
        

        window.continueToUpload = function() {

            document.getElementById('section-edukasi').style.display = 'none';

            document.getElementById('section-upload').style.display = 'block';

            updateProgress(1);

            window.scrollTo({ top: 0, behavior: 'smooth' });
        };
        

        const userLat = <?php echo $userData['latitude'] ?? -6.200000; ?>;
        const userLng = <?php echo $userData['longitude'] ?? 106.816666; ?>;
        
        let lastGeocodeTime = 0; // Track last geocoding request to avoid rate limiting
        let reportMap = L.map('reportMap').setView([userLat, userLng], 13);
        let reportMarker;

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(reportMap);


        function updateProgress(step) {
            currentStep = step;
            const steps = document.querySelectorAll('.progress-step');
            steps.forEach((stepEl, index) => {
                stepEl.classList.remove('active', 'completed');
                if (index < step) {
                    stepEl.classList.add('completed');
                } else if (index === step) {
                    stepEl.classList.add('active');
                }
            });
        }


        document.getElementById('gambar').addEventListener('change', handleImageUpload);
        document.getElementById('cameraInput').addEventListener('change', handleImageUpload);

        async function handleImageUpload(e) {
            const file = e.target.files[0];
            if (!file) return;
            
            console.log('📁 File selected:', file.name, formatFileSize(file.size));
            

            aiConfirmed = false;
            document.getElementById('reviewSubmitBtn').disabled = true;
            document.getElementById('confirmationStatus').style.display = 'none';
            document.getElementById('section-details').classList.add('form-disabled');
            

            const reader = new FileReader();
            reader.onload = async function(event) {
                const imgElement = document.getElementById('imagePreview');
                imgElement.src = event.target.result;
                

                document.getElementById('imagePreviewContainer').style.display = 'block';
                

                document.getElementById('fileName').textContent = file.name;
                document.getElementById('fileSize').textContent = formatFileSize(file.size);
                

                document.getElementById('loadingSpinner').style.display = 'block';
                document.getElementById('classificationResult').style.display = 'none';
                

                imgElement.onload = async function() {

                    const dimensions = `${imgElement.naturalWidth} × ${imgElement.naturalHeight}px`;
                    document.getElementById('imageDimensions').textContent = dimensions;
                    console.log('🖼️ Image dimensions:', dimensions);
                    

                    await analyzeImage(imgElement);
                };
            };
            reader.readAsDataURL(file);
        }
        

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        }




        
        async function analyzeImage(imgElement) {
            console.log('🔬 Starting AI analysis...');
            const loadingSpinner = document.getElementById('loadingSpinner');

            try {

                if(loadingSpinner) loadingSpinner.style.display = 'block';
                

                if (!imgElement || !imgElement.src || imgElement.naturalWidth === 0) {
                    throw new Error('Invalid image element');
                }
                
                console.log('🤖 Calling predictImage...');
                

                const predictions = await predictImage(imgElement);
                

                if(loadingSpinner) loadingSpinner.style.display = 'none';
                
                if (predictions && predictions.length > 0) {
                    console.log('✅ Got predictions:', predictions);
                    

                    const bestPrediction = predictions[0];
                    


                    const category = bestPrediction.className; 
                    const confidence = (bestPrediction.probability * 100).toFixed(2);
                    
                    console.log('🏆 Result:', category, '@', confidence + '%');
                    

                    allPredictions = predictions;
                    currentPrediction = {
                        className: category,
                        category: category,
                        probability: bestPrediction.probability,
                        confidence: confidence,
                        objects: bestPrediction.objects || [], // Capture objects
                        reason: bestPrediction.reason || ''    // Capture reason
                    };
                    

                    if(typeof updateProgress === 'function') updateProgress(2); 
                    


                    showAIConfirmationModal(currentPrediction, predictions);
                    
                } else {
                    console.error('❌ No predictions returned');
                    alert('❌ Gagal menganalisis gambar. Tidak ada respon dari AI.');
                }
                
            } catch (error) {
                console.error('❌ Error in analyzeImage:', error);
                if(loadingSpinner) loadingSpinner.style.display = 'none';
                alert('⚠️ Error: ' + error.message);
            }
        }


        function showAIConfirmationModal(prediction, predictions) {
            console.log('📋 Showing confirmation modal for:', prediction.category);
            
            const modal = document.getElementById('aiConfirmationModal');
            

            let categoryColor = '#4caf50';
            let categoryIcon = 'leaf';
            let categoryName = 'Organik';
            
            if (prediction.category === 'anorganik') {
                categoryColor = '#2196f3';
                categoryIcon = 'recycle';
                categoryName = 'Anorganik';
            } else if (prediction.category === 'b3') {
                categoryColor = '#f44336';
                categoryIcon = 'exclamation-triangle';
                categoryName = 'B3 (Bahan Berbahaya)';
            } else if (prediction.category === 'organik') {
                categoryColor = '#4caf50';
                categoryIcon = 'leaf';
                categoryName = 'Organik';
            }
            
            modal.innerHTML = `
                <div class="ai-modal-overlay">
                    <div class="ai-modal-content">
                        <div class="ai-modal-header">
                            <i class="fas fa-robot"></i>
                            <h4 class="mt-3 mb-0">Hasil Deteksi Otomatis</h4>
                            <p class="mb-0 mt-2" style="font-size: 0.9rem; opacity: 0.9;">Mohon konfirmasi jenis sampah</p>
                        </div>
                        <div class="ai-modal-body">
                            <div class="ai-result-card" style="background: ${categoryColor}15; border-color: ${categoryColor};">
                                <div class="ai-result-icon" style="color: ${categoryColor};">
                                    <i class="fas fa-${categoryIcon}"></i>
                                </div>
                                <h3 style="color: ${categoryColor}; margin-bottom: 8px;">${categoryName}</h3>
                                <div class="ai-confidence">
                                    <i class="fas fa-chart-line"></i> ${prediction.confidence}% confidence
                                </div>
                                
                                <!-- Object Details Hidden for Privacy -->
                            </div>
                            
                            <div class="text-center mb-3">
                                <p class="fw-bold mb-2" style="color: #1f2937;">
                                    <i class="fas fa-question-circle"></i> Apakah hasil deteksi ini benar?
                                </p>
                                <p class="text-muted small mb-0">
                                    Konfirmasi sebelum melanjutkan
                                </p>
                            </div>
                            
                            <div class="ai-modal-actions">
                                <button class="ai-modal-btn btn-confirm" onclick="confirmAIResult(true)">
                                    <i class="fas fa-check-circle"></i> Ya, Benar
                                </button>
                                <button class="ai-modal-btn btn-correct" onclick="confirmAIResult(false)">
                                    <i class="fas fa-edit"></i> Koreksi Manual
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            modal.style.display = 'block';
        }


        window.confirmAIResult = function(isCorrect) {
            console.log('✓ User confirmed AI result:', isCorrect ? 'CORRECT' : 'NEEDS CORRECTION');
            
            const modal = document.getElementById('aiConfirmationModal');
            modal.style.display = 'none';
            
            aiConfirmed = true;
            
            if (isCorrect) {

                console.log('✅ Using AI prediction:', currentPrediction.category);
                

                document.getElementById('kategori').value = currentPrediction.category;
                document.getElementById('confidence').value = currentPrediction.confidence;
                document.getElementById('ai_prediction').value = currentPrediction.category;
                document.getElementById('is_corrected').value = '0';
                

                displayClassificationResult(currentPrediction, currentPrediction.reason);
                

                showConfirmationStatus('confirmed');
                

                document.getElementById('section-details').classList.remove('form-disabled');
                document.getElementById('reviewSubmitBtn').disabled = false;
                
                updateProgress(3);
                
            } else {
                console.log('⚠️ User wants to correct AI result');
                

                displayClassificationResult(currentPrediction, currentPrediction.reason);
                

                document.getElementById('correctionSection').style.display = 'block';
                showConfirmationStatus('correction_needed');
                

                setTimeout(() => {
                    document.getElementById('correctionSection').scrollIntoView({ 
                        behavior: 'smooth', 
                        block: 'center' 
                    });
                }, 300);
            }
        };


        function showConfirmationStatus(type) {
            const statusDiv = document.getElementById('confirmationStatus');
            
            if (type === 'confirmed') {
                statusDiv.innerHTML = `
                    <div class="alert alert-success" style="border-left: 4px solid #14b8a6;">
                        <i class="fas fa-check-circle"></i> 
                        <strong>Terkonfirmasi:</strong> Hasil AI sudah benar. Silakan lanjutkan mengisi detail.
                    </div>
                `;
            } else if (type === 'correction_needed') {
                statusDiv.innerHTML = `
                    <div class="alert alert-warning" style="border-left: 4px solid #f59e0b;">
                        <i class="fas fa-exclamation-triangle"></i> 
                        <strong>Koreksi Diperlukan:</strong> Silakan pilih kategori yang benar di bawah.
                    </div>
                `;
            } else if (type === 'corrected') {
                statusDiv.innerHTML = `
                    <div class="alert alert-info" style="border-left: 4px solid #3b82f6;">
                        <i class="fas fa-user-edit"></i> 
                        <strong>Telah Dikoreksi:</strong> Kategori telah diubah sesuai koreksi Anda.
                    </div>
                `;
            }
            
            statusDiv.style.display = 'block';
        }


        document.getElementById('applyCorrection').addEventListener('click', function() {
            const selectedCategory = document.querySelector('input[name="manual_kategori"]:checked');
            
            if (!selectedCategory) {
                alert('Silakan pilih kategori yang benar!');
                return;
            }
            
            const manualCategory = selectedCategory.value;
            const correctionNote = document.getElementById('correction_note').value;
            

            document.getElementById('kategori').value = manualCategory;
            document.getElementById('is_corrected').value = '1';
            

            updateCorrectedResult(manualCategory, correctionNote);
            

            showConfirmationStatus('corrected');
            

            document.getElementById('section-details').classList.remove('form-disabled');
            document.getElementById('reviewSubmitBtn').disabled = false;
            

            document.getElementById('correctionSection').style.display = 'none';
            
            updateProgress(3);
        });

        document.getElementById('cancelCorrection').addEventListener('click', function() {

            const aiPrediction = document.getElementById('ai_prediction').value;
            document.getElementById('kategori').value = aiPrediction;
            document.getElementById('is_corrected').value = '0';
            document.getElementById('correction_note').value = '';
            

            document.querySelectorAll('input[name="manual_kategori"]').forEach(radio => {
                radio.checked = false;
            });
            

            displayClassificationResult(currentPrediction, allPredictions);
            

            showConfirmationStatus('confirmed');
            

            document.getElementById('section-details').classList.remove('form-disabled');
            document.getElementById('reviewSubmitBtn').disabled = false;
            

            document.getElementById('correctionSection').style.display = 'none';
            
            updateProgress(3);
        });


        function updateCorrectedResult(category, note) {
            let categoryColor = '#4caf50';
            let categoryIcon = 'leaf';
            let categoryName = 'Organik';
            
            if (category === 'anorganik') {
                categoryColor = '#2196f3';
                categoryIcon = 'recycle';
                categoryName = 'Anorganik';
            } else if (category === 'b3') {
                categoryColor = '#f44336';
                categoryIcon = 'exclamation-triangle';
                categoryName = 'B3 (Bahan Berbahaya Beracun)';
            }
            
            const resultDiv = document.getElementById('classificationResult');
            let html = `
                <div class="classification-result">
                    <h5><i class="fas fa-user-edit"></i> Hasil Setelah Koreksi Manual</h5>
                    <div class="alert" style="background-color: #fff3e0; border-left: 4px solid #ff9800;">
                        <p class="mb-2">
                            <i class="fas fa-info-circle"></i> 
                            <strong>Anda telah mengkoreksi hasil AI</strong>
                        </p>
                    </div>
                    <div class="alert" style="background-color: ${categoryColor}22; border-left: 4px solid ${categoryColor};">
                        <h4 style="color: ${categoryColor}; margin-bottom: 10px;">
                            <i class="fas fa-${categoryIcon}"></i> ${categoryName}
                        </h4>
                        ${note ? `<p><strong>Catatan:</strong> ${note}</p>` : ''}
                    </div>
                    <small class="text-muted">
                        <i class="fas fa-lightbulb"></i> Data koreksi Anda akan membantu meningkatkan akurasi AI
                    </small>
                </div>
            `;
            
            resultDiv.innerHTML = html;
            resultDiv.style.display = 'block';
            

            resultDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }


        function setReportLocation(lat, lng, autoFillAddress = false) {
            document.getElementById('lokasi_latitude').value = lat;
            document.getElementById('lokasi_longitude').value = lng;
            
            if (reportMarker) {
                reportMap.removeLayer(reportMarker);
            }
            
            reportMarker = L.marker([lat, lng], { draggable: true }).addTo(reportMap);
            reportMarker.on('dragend', function(e) {
                const pos = reportMarker.getLatLng();
                setReportLocation(pos.lat, pos.lng, true);
            });
            
            reportMap.setView([lat, lng], 15);
            

            if (autoFillAddress) {
                reverseGeocode(lat, lng);
            }
        }


        async function reverseGeocode(lat, lng, retryCount = 0) {
            const addressField = document.getElementById('alamat_lokasi');
            const btn = document.getElementById('reverseGeocodeBtn');
            const maxRetries = 2;
            
            try {
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                btn.disabled = true;
                

                const now = Date.now();
                const timeSinceLastRequest = now - lastGeocodeTime;
                if (timeSinceLastRequest < 1000) {
                    await new Promise(resolve => setTimeout(resolve, 1000 - timeSinceLastRequest));
                }
                lastGeocodeTime = Date.now();
                

                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 10000); // 10 second timeout
                
                const response = await fetch(
                    `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`,
                    {
                        headers: {
                            'Accept-Language': 'id',
                            'User-Agent': 'PelaporanSampahApp/1.0'
                        },
                        signal: controller.signal
                    }
                );
                
                clearTimeout(timeoutId);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                
                if (data && data.display_name) {
                    addressField.value = data.display_name;
                    

                    addressField.style.borderColor = '#14b8a6';
                    setTimeout(() => {
                        addressField.style.borderColor = '';
                    }, 2000);
                    
                    console.log('✅ Reverse geocoding berhasil');
                } else if (data && data.address) {

                    const addr = data.address;
                    const parts = [];
                    if (addr.road) parts.push(addr.road);
                    if (addr.suburb || addr.village) parts.push(addr.suburb || addr.village);
                    if (addr.city || addr.town) parts.push(addr.city || addr.town);
                    if (addr.state) parts.push(addr.state);
                    
                    if (parts.length > 0) {
                        addressField.value = parts.join(', ');
                        addressField.style.borderColor = '#14b8a6';
                        setTimeout(() => {
                            addressField.style.borderColor = '';
                        }, 2000);
                    } else {
                        throw new Error('No address data available');
                    }
                } else {
                    throw new Error('No address data in response');
                }
            } catch (error) {
                console.error('Reverse geocoding error:', error);
                

                if (retryCount < maxRetries && error.name !== 'AbortError') {
                    console.log(`🔄 Mencoba lagi... (${retryCount + 1}/${maxRetries})`);
                    await new Promise(resolve => setTimeout(resolve, 1000)); // Wait 1 second before retry
                    return reverseGeocode(lat, lng, retryCount + 1);
                }
                

                if (addressField.value.trim() === '') {
                    addressField.value = `Lokasi: ${lat.toFixed(6)}, ${lng.toFixed(6)}`;
                    addressField.placeholder = 'Silakan edit alamat ini dengan alamat yang lebih spesifik';
                }
                

                const errorMsg = error.name === 'AbortError' 
                    ? 'Koneksi timeout. Alamat koordinat sudah diisi, silakan edit jika perlu.' 
                    : 'Gagal mendapatkan alamat otomatis. Alamat koordinat sudah diisi, silakan edit jika perlu.';
                

                const toast = document.createElement('div');
                toast.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: #ff9800;
                    color: white;
                    padding: 1rem 1.5rem;
                    border-radius: 12px;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                    z-index: 9999;
                    animation: slideIn 0.3s ease;
                `;
                toast.innerHTML = `<i class="fas fa-exclamation-triangle"></i> ${errorMsg}`;
                document.body.appendChild(toast);
                
                setTimeout(() => {
                    toast.style.animation = 'slideOut 0.3s ease';
                    setTimeout(() => toast.remove(), 300);
                }, 5000);
            } finally {
                btn.innerHTML = '<i class="fas fa-sync-alt"></i>';
                btn.disabled = false;
            }
        }


        document.getElementById('useMyLocation').addEventListener('click', function() {
            setReportLocation(userLat, userLng, true);
        });


        document.getElementById('useCurrentLocation').addEventListener('click', function() {
            if (navigator.geolocation) {
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mendapatkan lokasi...';
                this.disabled = true;
                
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        setReportLocation(position.coords.latitude, position.coords.longitude, true);
                        this.innerHTML = '<i class="fas fa-location-arrow"></i> Deteksi GPS';
                        this.disabled = false;
                    },
                    (error) => {
                        alert('Tidak dapat mengakses lokasi GPS.');
                        this.innerHTML = '<i class="fas fa-location-arrow"></i> Deteksi GPS';
                        this.disabled = false;
                    }
                );
            } else {
                alert('Browser tidak mendukung Geolocation.');
            }
        });


        document.getElementById('reverseGeocodeBtn').addEventListener('click', function() {
            const lat = document.getElementById('lokasi_latitude').value;
            const lng = document.getElementById('lokasi_longitude').value;
            
            if (lat && lng) {
                reverseGeocode(lat, lng);
            } else {
                alert('Silakan pilih lokasi di peta terlebih dahulu!');
            }
        });


        reportMap.on('click', function(e) {
            setReportLocation(e.latlng.lat, e.latlng.lng, true);
        });


        setReportLocation(userLat, userLng);


        function usePresetLocation(lat, lng, address) {
            setReportLocation(lat, lng);
            document.getElementById('alamat_lokasi').value = address;
        }


        document.getElementById('save_location').addEventListener('change', function() {
            document.getElementById('locationNameDiv').style.display = this.checked ? 'block' : 'none';
        });


        let tagsArray = [];
        
        function addTag(tagText) {
            tagText = tagText.trim().toLowerCase();
            if (tagText === '' || tagsArray.includes(tagText)) return;
            
            tagsArray.push(tagText);
            updateTagsDisplay();
            updateTagsHiddenField();
        }
        
        function removeTag(tagText) {
            tagsArray = tagsArray.filter(t => t !== tagText);
            updateTagsDisplay();
            updateTagsHiddenField();
        }
        
        function updateTagsDisplay() {
            const container = document.getElementById('tagsContainer');
            container.innerHTML = '';
            
            tagsArray.forEach(tag => {
                const tagEl = document.createElement('div');
                tagEl.className = 'tag-item';
                tagEl.innerHTML = `
                    <span>${tag}</span>
                    <i class="fas fa-times" onclick="removeTag('${tag}')"></i>
                `;
                container.appendChild(tagEl);
            });
        }
        
        function updateTagsHiddenField() {
            document.getElementById('tags').value = tagsArray.join(',');
        }
        

        document.getElementById('addTagBtn').addEventListener('click', function() {
            const input = document.getElementById('tags_input');
            addTag(input.value);
            input.value = '';
        });
        

        document.getElementById('tags_input').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                addTag(this.value);
                this.value = '';
            }
        });
        

        document.querySelectorAll('.suggested-tag-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                addTag(this.dataset.tag);
            });
        });
        

        window.removeTag = removeTag;
        

        document.getElementById('reviewSubmitBtn').addEventListener('click', function() {
            updateProgress(4);
            showReviewModal();
        });

        function showReviewModal() {
            const modal = document.getElementById('previewModal');
            
            const kategori = document.getElementById('kategori').value;
            const jenisSampah = document.getElementById('jenis_sampah').value;
            const deskripsi = document.getElementById('deskripsi').value || '-';
            const alamat = document.getElementById('alamat_lokasi').value;
            const isCorrected = document.getElementById('is_corrected').value;
            const tags = tagsArray;
            
            let kategoriDisplay = kategori.charAt(0).toUpperCase() + kategori.slice(1);
            let jenisSampahDisplay = document.querySelector('#jenis_sampah option:checked')?.text || jenisSampah;
            const imageSrc = document.getElementById('imagePreview').src;
            
            modal.innerHTML = `
                <div class="preview-modal">
                    <div class="preview-content">
                        <div class="preview-header">
                            <h3 class="mb-0"><i class="fas fa-eye"></i> Preview Laporan</h3>
                            <p class="mb-0 mt-2" style="font-size: 0.9rem; opacity: 0.9;">Periksa kembali data sebelum mengirim</p>
                        </div>
                        <div class="preview-body">
                            <div class="preview-section">
                                <div class="preview-label">📸 Foto Sampah</div>
                                <div class="preview-images">
                                    <div class="preview-image-item">
                                        <img src="${imageSrc}" alt="Foto Sampah">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="preview-section">
                                <div class="preview-label">🤖 Klasifikasi AI</div>
                                <div class="preview-value">
                                    <span class="badge bg-${kategori === 'organik' ? 'success' : (kategori === 'anorganik' ? 'primary' : 'danger')} me-2">
                                        ${kategoriDisplay}
                                    </span>
                                    ${isCorrected === '1' ? '<span class="badge bg-warning">Dikoreksi Manual</span>' : '<span class="badge bg-info">Sesuai AI</span>'}
                                </div>
                            </div>
                            
                            <div class="preview-section">
                                <div class="preview-label">🏷️ Jenis Sampah</div>
                                <div class="preview-value">${jenisSampahDisplay}</div>
                            </div>
                            
                            <div class="preview-section">
                                <div class="preview-label">📝 Deskripsi</div>
                                <div class="preview-value">${deskripsi}</div>
                            </div>
                            
                            ${tags.length > 0 ? `
                            <div class="preview-section">
                                <div class="preview-label">🏷️ Tags Analitik</div>
                                <div class="preview-value">
                                    ${tags.map(tag => `<span class="badge bg-info me-1">${tag}</span>`).join('')}
                                </div>
                            </div>
                            ` : ''}
                            
                            <div class="preview-section">
                                <div class="preview-label">📍 Lokasi</div>
                                <div class="preview-value">${alamat}</div>
                            </div>
                            
                            <div class="d-grid gap-2 mt-4">
                                <button type="button" class="btn btn-success btn-lg" onclick="confirmSubmit()">
                                    <i class="fas fa-paper-plane"></i> Ya, Kirim Sekarang
                                </button>
                                <button type="button" class="btn btn-outline-secondary" onclick="closePreviewModal()">
                                    <i class="fas fa-times"></i> Batal, Edit Lagi
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            modal.style.display = 'block';
        }

        window.closePreviewModal = function() {
            document.getElementById('previewModal').style.display = 'none';
            updateProgress(3);
        };

        window.confirmSubmit = function() {
            document.getElementById('previewModal').style.display = 'none';

            document.getElementById('laporForm').submit();
        };


        document.getElementById('laporForm').addEventListener('submit', function(e) {
            const lat = document.getElementById('lokasi_latitude').value;
            const lng = document.getElementById('lokasi_longitude').value;
            const kategori = document.getElementById('kategori').value;
            
            if (!aiConfirmed) {
                e.preventDefault();
                alert('⚠️ Anda HARUS konfirmasi hasil AI terlebih dahulu!\n\nSetelah upload foto, tunggu analisis AI selesai dan konfirmasi hasilnya.');
                return false;
            }
            
            if (!lat || !lng) {
                e.preventDefault();
                alert('Harap tentukan lokasi sampah!');
                return false;
            }
            
            if (!kategori) {
                e.preventDefault();
                alert('Harap upload gambar dan tunggu klasifikasi selesai!');
                return false;
            }
            

            const submitBtn = document.getElementById('submitBtn');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengirim...';
            submitBtn.disabled = true;
        });


        <?php if ($success): ?>
        setTimeout(function() {
            const modal = document.getElementById('thankYouModal');
            if (modal) {
                modal.style.animation = 'fadeOut 0.5s ease';
                setTimeout(function() {
                    window.location.href = 'dashboard.php';
                }, 500);
            }
        }, 5000);
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
        

        let currentZoomLevel = 1;
        let isDragging = false;
        let startX, startY, scrollLeft, scrollTop;
        
        window.zoomPreview = function() {
            const previewImg = document.getElementById('imagePreview');
            const zoomedImg = document.getElementById('zoomedImage');
            const modal = document.getElementById('zoomModal');
            

            zoomedImg.src = previewImg.src;
            

            currentZoomLevel = 1;
            zoomedImg.style.transform = 'scale(1)';
            

            modal.style.display = 'flex';
            
            console.log('🔍 Zoom preview opened');
        };
        
        window.closeZoom = function() {
            const modal = document.getElementById('zoomModal');
            modal.style.display = 'none';
            currentZoomLevel = 1;
            console.log('❌ Zoom closed');
        };
        
        window.zoomIn = function() {
            const zoomedImg = document.getElementById('zoomedImage');
            currentZoomLevel = Math.min(currentZoomLevel + 0.5, 5); // Max 5x
            zoomedImg.style.transform = `scale(${currentZoomLevel})`;
            console.log('🔍+ Zoom in:', currentZoomLevel + 'x');
        };
        
        window.zoomOut = function() {
            const zoomedImg = document.getElementById('zoomedImage');
            currentZoomLevel = Math.max(currentZoomLevel - 0.5, 0.5); // Min 0.5x
            zoomedImg.style.transform = `scale(${currentZoomLevel})`;
            console.log('🔍- Zoom out:', currentZoomLevel + 'x');
        };
        
        window.resetZoom = function() {
            const zoomedImg = document.getElementById('zoomedImage');
            currentZoomLevel = 1;
            zoomedImg.style.transform = 'scale(1)';
            console.log('🔄 Zoom reset');
        };
        

        document.addEventListener('DOMContentLoaded', function() {
            const zoomedImg = document.getElementById('zoomedImage');
            const modal = document.getElementById('zoomModal');
            
            zoomedImg.addEventListener('wheel', function(e) {
                e.preventDefault();
                if (e.deltaY < 0) {
                    zoomIn();
                } else {
                    zoomOut();
                }
            });
            

            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && modal.style.display === 'flex') {
                    closeZoom();
                }
            });
            

            zoomedImg.addEventListener('mousedown', function(e) {
                if (currentZoomLevel > 1) {
                    isDragging = true;
                    startX = e.clientX;
                    startY = e.clientY;
                    const transform = zoomedImg.style.transform;
                    const matrix = new DOMMatrix(getComputedStyle(zoomedImg).transform);
                    scrollLeft = matrix.m41;
                    scrollTop = matrix.m42;
                }
            });
            
            document.addEventListener('mousemove', function(e) {
                if (!isDragging) return;
                e.preventDefault();
                const x = e.clientX - startX;
                const y = e.clientY - startY;
                zoomedImg.style.transform = `scale(${currentZoomLevel}) translate(${scrollLeft + x}px, ${scrollTop + y}px)`;
            });
            
            document.addEventListener('mouseup', function() {
                isDragging = false;
            });
        });
        

        window.changeImage = function() {
            console.log('🔄 Change image clicked');
            

            if (!confirm('Ganti gambar? Hasil analisis AI akan hilang dan harus dianalisis ulang.')) {
                return;
            }
            

            document.getElementById('imagePreviewContainer').style.display = 'none';
            document.getElementById('loadingSpinner').style.display = 'none';
            document.getElementById('classificationResult').style.display = 'none';
            document.getElementById('confirmationStatus').style.display = 'none';
            

            document.getElementById('gambar').value = '';
            document.getElementById('cameraInput').value = '';
            

            aiConfirmed = false;
            document.getElementById('reviewSubmitBtn').disabled = true;
            

            document.getElementById('kategori').value = '';
            document.getElementById('jenis_sampah').value = '';
            

            document.getElementById('gambar').click();
            
            console.log('✅ Image reset, ready for new upload');
        };
    </script>
    
    <style>
        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }
    </style>
</body>
</html>
