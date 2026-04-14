<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'classes/User.php';


if (isLoggedIn()) {
    if (isAdmin()) {
        redirect('admin/dashboard.php');
    } else {
        redirect('user/dashboard.php');
    }
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $nama = trim($_POST['nama']);
    $nomor_hp = trim($_POST['nomor_hp']);
    $alamat = trim($_POST['alamat']);
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];

    if (!empty($username) && !empty($password) && !empty($nama) && !empty($nomor_hp) && !empty($alamat)) {
        if ($password === $confirm_password) {
            $database = new Database();
            $db = $database->getConnection();
            $user = new User($db);

            if (!$user->usernameExists($username)) {
                $user->username = $username;
                $user->password = $password;
                $user->nama = $nama;
                $user->nomor_hp = $nomor_hp;
                $user->alamat = $alamat;
                $user->latitude = $latitude;
                $user->longitude = $longitude;
                $user->role = 'user';

                if ($user->register()) {
                    $success = 'Registrasi berhasil! Silakan login.';
                } else {
                    $error = 'Terjadi kesalahan saat registrasi.';
                }
            } else {
                $error = 'Username sudah digunakan!';
            }
        } else {
            $error = 'Password dan konfirmasi password tidak cocok!';
        }
    } else {
        $error = 'Semua field harus diisi!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes, viewport-fit=cover">
    <meta name="theme-color" content="#14b8a6">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    
    
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
    <link rel="dns-prefetch" href="https://cdnjs.cloudflare.com">
    <link rel="dns-prefetch" href="https://fonts.googleapis.com">
    <link rel="dns-prefetch" href="https://unpkg.com">
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    
    <title>Register - Lapor Sampah</title>
    
    
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="alternate icon" href="favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="favicon.svg">
    
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" as="style">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" media="print" onload="this.media='all'">
    
    <style>
        /* ===== PERFORMANCE OPTIMIZATIONS ===== */
        :root {
            scroll-behavior: smooth;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        *,
        *::before,
        *::after {
            -webkit-backface-visibility: hidden;
            backface-visibility: hidden;
        }
        
        html {
            font-size: 16px;
            -webkit-text-size-adjust: 100%;
            scroll-behavior: smooth;
            -webkit-tap-highlight-color: transparent;
            touch-action: manipulation;
            height: -webkit-fill-available;
        }
        
        /* Smooth transitions */
        button, a, input, textarea, select {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        /* Reduce motion */
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                transition-duration: 0.01ms !important;
                scroll-behavior: auto !important;
            }
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #f0fdfa 0%, #ccfbf1 100%);
            min-height: 100vh;
            min-height: -webkit-fill-available;
            padding: 40px 20px;
            position: relative;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        html {
            height: -webkit-fill-available;
        }
        
        /* Animated Background Blobs */
        body::before {
            content: '';
            position: fixed;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(20, 184, 166, 0.1) 0%, transparent 80%),
                        radial-gradient(circle at 80% 20%, rgba(6, 182, 212, 0.1) 0%, transparent 50%);
            z-index: 0;
            animation: rotate 20s linear infinite;
        }
        
        body::after {
            content: '';
            position: fixed;
            bottom: -20%;
            right: -20%;
            width: 80%;
            height: 80%;
            background: radial-gradient(circle, rgba(13, 148, 136, 0.1) 0%, transparent 70%);
            z-index: 0;
            animation: float 10s ease-in-out infinite;
        }
        
        @keyframes rotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        @keyframes float {
            0%, 100% { transform: translate(0, 0); }
            50% { transform: translate(-30px, 30px); }
        }
        
        .register-container {
            max-width: 800px;
            width: 100%;
            margin: 0 auto;
            position: relative;
            z-index: 1;
            animation: fadeInUp 0.8s ease;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .back-home {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1000;
        }
        
        .back-home a {
            color: #6b7280;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 0.75rem 1.25rem;
            border-radius: 12px;
            transition: all 0.3s ease;
            -webkit-tap-highlight-color: transparent;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .back-home a:hover {
            color: #14b8a6;
            background: rgba(255, 255, 255, 1);
            transform: translateX(-3px);
            box-shadow: 0 6px 20px rgba(20, 184, 166, 0.2);
        }
        
        .modern-register-card {
            position: relative;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 25px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            backdrop-filter: blur(10px);
            margin-bottom: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.5);
        }
        
        .register-glass-header {
            background: linear-gradient(135deg, rgba(255,255,255,0.9) 0%, rgba(240,253,250,0.9) 100%);
            padding: 2.5rem 2rem;
            text-align: center;
            color: #1f2937;
            position: relative;
            overflow: hidden;
            border-bottom: 1px solid rgba(20, 184, 166, 0.1);
        }
        
        .register-glass-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(20, 184, 166, 0.05) 0%, transparent 60%);
            animation: rotate 15s linear infinite;
        }
        
        .register-avatar {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #14b8a6, #06b6d4);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.25rem;
            font-size: 2.5rem;
            color: white;
            box-shadow: 0 10px 20px rgba(20, 184, 166, 0.3);
            position: relative;
            z-index: 1;
        }
        
        .register-title {
            font-size: 1.75rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 1;
            background: linear-gradient(135deg, #1f2937 0%, #4b5563 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: -0.5px;
        }
        
        .register-subtitle {
            color: #6b7280;
            margin: 0;
            position: relative;
            z-index: 1;
        }
        
        .register-form {
            padding: 2.5rem 2rem 2rem;
        }
        
        .modern-input-group {
            position: relative;
            margin-bottom: 1.5rem;
        }
        
        .input-icon {
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 1.125rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 2;
        }
        
        .modern-input,
        .modern-textarea {
            width: 100%;
            padding: 0.875rem 2rem 0.875rem 2.25rem;
            border: none;
            background: #f9fafb;
            border-radius: 12px;
            font-size: 1rem;
            color: #1f2937;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            outline: none;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.02);
        }
        
        .modern-textarea {
            resize: vertical;
            min-height: 80px;
        }
        
        .modern-input:focus,
        .modern-textarea:focus {
            background: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(20, 184, 166, 0.15);
        }
        
        .floating-label {
            position: absolute;
            left: 2.25rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 0.95rem;
            pointer-events: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: transparent;
            padding: 0 0.5rem;
            z-index: 1;
        }
        
        .textarea-group .floating-label {
            top: 1rem;
            transform: none;
        }
        
        .modern-input-group.is-focused .floating-label,
        .modern-input-group.has-value .floating-label {
            top: -10px;
            left: 1.5rem;
            font-size: 0.75rem;
            color: #14b8a6;
            font-weight: 600;
            background: white;
            border-radius: 4px;
        }
        
        .modern-input-group.is-focused .input-icon {
            color: #14b8a6;
            transform: translateY(-50%) scale(1.1);
        }
        
        .input-border {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background: linear-gradient(90deg, #14b8a6, #06b6d4);
            transition: width 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .modern-input-group.is-focused .input-border {
            width: 100%;
        }
        
        .password-eye {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #9ca3af;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 50%;
            transition: all 0.3s ease;
            z-index: 3;
        }
        
        .password-eye:hover {
            color: #14b8a6;
            background: rgba(20, 184, 166, 0.1);
        }
        
        .modern-alert {
            padding: 1rem 1.25rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.95rem;
            animation: slideDown 0.4s ease;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .modern-alert-error {
            background: #fef2f2;
            color: #ef4444;
            border-left: 4px solid #ef4444;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.1);
        }
        
        .modern-alert-success {
            background: #f0fdf4;
            color: #059669;
            border-left: 4px solid #059669;
            box-shadow: 0 4px 12px rgba(5, 150, 105, 0.1);
        }
        
        .map-container {
            margin-bottom: 1.5rem;
        }
        
        .map-label {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #374151;
            font-weight: 600;
            margin-bottom: 0.75rem;
            font-size: 0.95rem;
        }
        
        #map {
            height: 300px;
            border-radius: 15px;
            border: 3px solid rgba(20, 184, 166, 0.2);
            box-shadow: 0 4px 12px rgba(20, 184, 166, 0.1);
            margin-top: 0.75rem;
        }
        
        .btn-location {
            width: 100%;
            padding: 0.875rem 1.5rem;
            background: white;
            border: 2px solid #14b8a6;
            border-radius: 12px;
            color: #14b8a6;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .btn-location:hover {
            background: #14b8a6;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(20, 184, 166, 0.3);
        }
        
        .location-info {
            margin-top: 0.5rem;
            color: #6b7280;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .modern-btn-register {
            width: 100%;
            padding: 1.125rem 2rem;
            background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.125rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 10px 20px rgba(20, 184, 166, 0.3);
            position: relative;
            overflow: hidden;
            margin-bottom: 1.5rem;
        }
        
        .modern-btn-register::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: 0.5s;
        }
        
        .modern-btn-register:hover:not(:disabled) {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(20, 184, 166, 0.4);
        }
        
        .modern-btn-register:hover:not(:disabled)::before {
            left: 100%;
        }
        
        .modern-btn-register:active:not(:disabled) {
            transform: translateY(-1px);
        }
        
        .modern-btn-register:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
            background: #9ca3af;
            box-shadow: none;
        }
        
        .btn-content {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
        }
        
        .btn-content i:last-child {
            transition: transform 0.3s ease;
        }
        
        .modern-btn-register:hover:not(:disabled) .btn-content i:last-child {
            transform: translateX(5px);
        }
        
        .register-footer {
            text-align: center;
            padding-top: 1rem;
            border-top: 1px solid rgba(0, 0, 0, 0.06);
        }
        
        .register-footer p {
            color: #6b7280;
            font-size: 0.95rem;
            margin-bottom: 0.75rem;
        }
        
        .login-link {
            color: #14b8a6;
            text-decoration: none;
            font-weight: 700;
            font-size: 1rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            padding: 0.5rem 1rem;
            border-radius: 8px;
        }
        
        .login-link:hover {
            color: #0d9488;
            background: rgba(20, 184, 166, 0.1);
            transform: translateX(3px);
        }
        
        .login-link i {
            transition: transform 0.3s ease;
        }
        
        .login-link:hover i {
            transform: translateX(3px);
        }
        
        .divider-section {
            display: flex;
            align-items: center;
            margin: 1.5rem 0;
            gap: 1rem;
        }
        
        .divider-line {
            flex: 1;
            height: 1px;
            background: rgba(0, 0, 0, 0.1);
        }
        
        .divider-text {
            color: #6b7280;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .google-login-btn {
            width: 100%;
            padding: 0.875rem 1.5rem;
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            color: #374151;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            text-decoration: none;
            margin-bottom: 1rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        
        .google-login-btn:hover {
            background: #f9fafb;
            border-color: #d1d5db;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            color: #1f2937;
        }
        
        .google-login-btn:active {
            transform: translateY(0);
            box-shadow: none;
        }
        
        .google-icon {
            width: 20px;
            height: 20px;
        }
        
        @media (max-width: 768px) {
            body {
                padding: 20px 10px;
            }
            
            .modern-register-card {
                border-radius: 25px;
            }
            
            .register-glass-header {
                padding: 2rem 1.5rem;
            }
            
            .register-avatar {
                width: 70px;
                height: 70px;
                font-size: 2.25rem;
            }
            
            .register-title {
                font-size: 1.5rem;
            }
            
            .register-form {
                padding: 2rem 1.5rem 1.5rem;
            }
            
            #map {
                height: 250px;
            }
        }
        
        @media (max-width: 576px) {
            .register-glass-header {
                padding: 1.75rem 1.25rem;
            }
            
            .register-avatar {
                width: 60px;
                height: 60px;
                font-size: 2rem;
            }
            
            .register-title {
                font-size: 1.35rem;
            }
            
            .register-subtitle {
                font-size: 0.9rem;
            }
            
            .register-form {
                padding: 1.75rem 1.25rem 1.25rem;
            }
            
            .modern-input,
            .modern-textarea {
                padding: 0.8125rem 2.75rem 0.8125rem 2.125rem;
            }
            
            .input-icon {
                font-size: 1rem;
            }
            
            .modern-btn-register,
            .btn-location {
                padding: 1rem 1.5rem;
                font-size: 1rem;
            }
            
            #map {
                height: 200px;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="back-home">
            <a href="index.php">
                <i class="fas fa-arrow-left"></i> Kembali ke Beranda
            </a>
        </div>
        
        <div class="modern-register-card">
            <div class="register-glass-header">
                <div class="register-avatar">
                    <i class="fas fa-user-plus"></i>
                </div>
                <h1 class="register-title">Daftar Sekarang</h1>
                <p class="register-subtitle">Bergabung dan berkontribusi untuk lingkungan yang lebih bersih</p>
            </div>
            
            <div class="register-form">
                <?php if ($error): ?>
                    <div class="modern-alert modern-alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo $error; ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="modern-alert modern-alert-success">
                        <i class="fas fa-check-circle"></i>
                        <span><?php echo $success; ?> <a href="login.php" style="color: #065f46; font-weight: 700;">Login di sini</a></span>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" id="registerForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="modern-input-group">
                                <div class="input-icon">
                                    <i class="fas fa-user"></i>
                                </div>
                                <input type="text" class="modern-input" id="username" name="username" required>
                                <label class="floating-label">Username</label>
                                <div class="input-border"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="modern-input-group">
                                <div class="input-icon">
                                    <i class="fas fa-id-card"></i>
                                </div>
                                <input type="text" class="modern-input" id="nama" name="nama" required>
                                <label class="floating-label">Nama Lengkap</label>
                                <div class="input-border"></div>
                            </div>
                        </div>
                    </div>

                    <div class="modern-input-group">
                        <div class="input-icon">
                            <i class="fas fa-phone"></i>
                        </div>
                        <input type="tel" class="modern-input" id="nomor_hp" name="nomor_hp" pattern="[0-9+\-\s()]+" required>
                        <label class="floating-label">Nomor HP / WhatsApp</label>
                        <div class="input-border"></div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="modern-input-group">
                                <div class="input-icon">
                                    <i class="fas fa-lock"></i>
                                </div>
                                <input type="password" class="modern-input" id="password" name="password" required>
                                <label class="floating-label">Password</label>
                                <button type="button" class="password-eye" onclick="togglePassword('password')">
                                    <i class="fas fa-eye" id="password-eye-icon"></i>
                                </button>
                                <div class="input-border"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="modern-input-group">
                                <div class="input-icon">
                                    <i class="fas fa-lock"></i>
                                </div>
                                <input type="password" class="modern-input" id="confirm_password" name="confirm_password" required>
                                <label class="floating-label">Konfirmasi Password</label>
                                <button type="button" class="password-eye" onclick="togglePassword('confirm_password')">
                                    <i class="fas fa-eye" id="confirm-password-eye-icon"></i>
                                </button>
                                <div class="input-border"></div>
                            </div>
                        </div>
                    </div>

                    <div class="modern-input-group textarea-group">
                        <div class="input-icon" style="top: 1.5rem; transform: none;">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <textarea class="modern-textarea" id="alamat" name="alamat" required></textarea>
                        <label class="floating-label">Alamat Lengkap</label>
                        <div class="input-border"></div>
                    </div>

                    <div class="map-container">
                        <label class="map-label">
                            <i class="fas fa-map-marked-alt text-teal-600"></i>
                            <span>Lokasi Peta (Opsional)</span>
                        </label>
                        <button type="button" class="btn-location" onclick="getLocation()">
                            <i class="fas fa-location-arrow"></i>
                            Gunakan Lokasi Saat Ini
                        </button>
                        <div id="map"></div>
                        <div class="location-info">
                            <i class="fas fa-info-circle"></i>
                            <span>Klik pada peta untuk memilih lokasi manual</span>
                        </div>
                        <input type="hidden" name="latitude" id="latitude">
                        <input type="hidden" name="longitude" id="longitude">
                    </div>

                    <button type="submit" class="modern-btn-register">
                        <span class="btn-content">
                            <i class="fas fa-user-plus"></i>
                            <span>Daftar Sekarang</span>
                            <i class="fas fa-arrow-right"></i>
                        </span>
                    </button>
                    
                    
                    <div class="divider-section">
                        <div class="divider-line"></div>
                        <span class="divider-text">atau daftar dengan</span>
                        <div class="divider-line"></div>
                    </div>
                    
                    <a href="auth/google_login.php" class="google-login-btn">
                        <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" alt="Google" class="google-icon">
                        <span>Daftar dengan Google</span>
                    </a>

                    <div class="register-footer">
                        <p>Sudah punya akun?</p>
                        <a href="login.php" class="login-link">
                            Login Sekarang <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>

        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('.modern-input, .modern-textarea');
            
            inputs.forEach(input => {

                if (input.value) {
                    input.parentElement.classList.add('has-value');
                }
                

                input.addEventListener('focus', () => {
                    input.parentElement.classList.add('is-focused');
                });
                
                input.addEventListener('blur', () => {
                    input.parentElement.classList.remove('is-focused');
                    if (input.value) {
                        input.parentElement.classList.add('has-value');
                    } else {
                        input.parentElement.classList.remove('has-value');
                    }
                });
                

                input.addEventListener('input', () => {
                    if (input.value) {
                        input.parentElement.classList.add('has-value');
                    } else {
                        input.parentElement.classList.remove('has-value');
                    }
                });
            });
        });


        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const iconId = inputId === 'password' ? 'password-eye-icon' : 'confirm-password-eye-icon';
            const icon = document.getElementById(iconId);
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }


        var map = L.map('map').setView([-6.2088, 106.8456], 13); // Default Jakarta
        var marker;

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        function setMarker(lat, lng) {
            if (marker) {
                map.removeLayer(marker);
            }
            marker = L.marker([lat, lng], {draggable: true}).addTo(map);
            

            updateCoordinates(lat, lng);
            

            marker.on('dragend', function(e) {
                var position = marker.getLatLng();
                updateCoordinates(position.lat, position.lng);
            });
            
            map.setView([lat, lng], 16);
        }
        
        function updateCoordinates(lat, lng) {
            document.getElementById('latitude').value = lat;
            document.getElementById('longitude').value = lng;
        }

        function getLocation() {
            if (navigator.geolocation) {
                const btn = document.querySelector('.btn-location');
                const originalText = btn.innerHTML;
                
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengambil Lokasi...';
                btn.disabled = true;
                
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        setMarker(lat, lng);
                        
                        btn.innerHTML = '<i class="fas fa-check"></i> Lokasi Ditemukan';
                        btn.classList.add('btn-success');
                        setTimeout(() => {
                            btn.innerHTML = originalText;
                            btn.disabled = false;
                            btn.classList.remove('btn-success');
                        }, 2000);
                    },
                    function(error) {
                        console.error("Error getting location: ", error);
                        btn.innerHTML = '<i class="fas fa-exclamation-circle"></i> Gagal Mengambil Lokasi';
                        setTimeout(() => {
                            btn.innerHTML = originalText;
                            btn.disabled = false;
                        }, 2000);
                        alert("Gagal mengambil lokasi. Pastikan GPS aktif dan izin lokasi diberikan.");
                    }
                );
            } else {
                alert("Geolocation tidak didukung oleh browser ini.");
            }
        }


        map.on('click', function(e) {
            setMarker(e.latlng.lat, e.latlng.lng);
        });
    </script>
</body>
</html>
