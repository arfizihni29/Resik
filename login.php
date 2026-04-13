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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (!empty($username) && !empty($password)) {
        $database = new Database();
        $db = $database->getConnection();
        $user = new User($db);

        if ($user->login($username, $password)) {
            $_SESSION['user_id'] = $user->id;
            $_SESSION['username'] = $user->username;
            $_SESSION['nama'] = $user->nama;
            $_SESSION['role'] = $user->role;
            $_SESSION['last_login'] = date('Y-m-d H:i:s'); // Save login time in WIB

            if ($user->role === 'admin') {
                redirect('admin/dashboard.php');
            } else {
                redirect('user/dashboard.php');
            }
        } else {
            $error = 'Username atau password salah!';
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
    
    <!-- Performance Optimization -->
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
    <link rel="dns-prefetch" href="https://cdnjs.cloudflare.com">
    <link rel="dns-prefetch" href="https://fonts.googleapis.com">
    <link rel="dns-prefetch" href="https://unpkg.com">
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    
    <title>Login - Lapor Sampah</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="apple-touch-icon" href="favicon.svg">
    
    <!-- Critical CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Fonts - Preload -->
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" as="style">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Vue 3 - Load synchronously for login form -->
    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
    
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
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
        
        .login-container {
            max-width: 480px;
            width: 100%;
            position: relative;
            z-index: 1;
            animation: fadeInUp 0.8s ease;
            margin-top: 0;
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
        
        @media (max-width: 768px) {
            body {
                padding: 20px 10px;
            }
            .back-home {
                top: 10px;
                left: 10px;
            }
            
            .back-home a {
                padding: 0.5rem 0.875rem;
                font-size: 0.875rem;
            }
        }
        
        /* Modern Login Card */
        .modern-login-card {
            position: relative;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 25px;
            padding: 0;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }
        
        .modern-login-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 30px 60px rgba(20, 184, 166, 0.15);
        }
        
        .login-glass-header {
            background: linear-gradient(135deg, rgba(255,255,255,0.9) 0%, rgba(240,253,250,0.9) 100%);
            padding: 3rem 2rem 1.5rem;
            text-align: center;
            position: relative;
            overflow: hidden;
            border-bottom: 1px solid rgba(20, 184, 166, 0.1);
        }
        
        .login-glass-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(20, 184, 166, 0.05) 0%, transparent 60%);
            animation: rotate 15s linear infinite;
        }
        
        .login-avatar {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #14b8a6, #06b6d4);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 3rem;
            color: white;
            box-shadow: 0 10px 20px rgba(20, 184, 166, 0.3);
            position: relative;
            z-index: 1;
        }
        
        .login-welcome {
            font-size: 2rem;
            font-weight: 800;
            color: #1f2937;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 1;
            background: linear-gradient(135deg, #1f2937 0%, #4b5563 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .login-subtitle {
            color: #6b7280;
            font-size: 1rem;
            margin: 0;
            position: relative;
            z-index: 1;
        }
        
        .modern-login-form {
            padding: 2.5rem 2rem 2rem;
        }
        
        .modern-input-group {
            position: relative;
            margin-bottom: 2rem;
        }
        
        .input-icon {
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 1.25rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 2;
        }
        
        .modern-input {
            width: 100%;
            padding: 1rem 3rem 1rem 2.5rem;
            border: none;
            background: #f9fafb;
            border-radius: 15px;
            font-size: 1rem;
            color: #1f2937;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            outline: none;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.02);
        }
        
        .modern-input:focus {
            background: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(20, 184, 166, 0.15);
        }
        
        .modern-input:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .floating-label {
            position: absolute;
            left: 2.5rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 1rem;
            pointer-events: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: transparent;
            padding: 0 0.5rem;
            z-index: 1;
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
        
        .password-eye:disabled {
            opacity: 0.5;
            cursor: not-allowed;
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
        
        .modern-alert i {
            font-size: 1.25rem;
        }
        
        .modern-btn-login {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 20px rgba(20, 184, 166, 0.3);
        }
        
        .modern-btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: 0.5s;
        }
        
        .modern-btn-login:hover:not(:disabled) {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(20, 184, 166, 0.4);
        }
        
        .modern-btn-login:hover:not(:disabled)::before {
            left: 100%;
        }
        
        .modern-btn-login:active:not(:disabled) {
            transform: translateY(-1px);
        }
        
        .modern-btn-login:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            background: #9ca3af;
            box-shadow: none;
        }
        
        .btn-content {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .btn-content i:last-child {
            transition: transform 0.3s ease;
        }
        
        .modern-btn-login:hover:not(:disabled) .btn-content i:last-child {
            transform: translateX(5px);
        }
        
        .login-footer {
            margin-top: 2rem;
            text-align: center;
        }
        
        .login-footer p {
            color: #6b7280;
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }
        
        .register-link {
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
        
        .register-link:hover {
            color: #0d9488;
            background: rgba(20, 184, 166, 0.1);
            transform: translateX(3px);
        }
        
        .register-link i {
            transition: transform 0.3s ease;
        }
        
        .register-link:hover i {
            transform: translateX(3px);
        }
        
        /* Card Decoration */
        .card-decoration {
            position: absolute;
            background: linear-gradient(135deg, #14b8a6, #06b6d4);
            border-radius: 50%;
            opacity: 0.1;
            z-index: 0;
        }
        
        .decoration-1 {
            width: 150px;
            height: 150px;
            top: -50px;
            right: -50px;
        }
        
        .decoration-2 {
            width: 100px;
            height: 100px;
            bottom: -30px;
            left: -30px;
        }
        
        .shake-animation {
            animation: shake 0.5s cubic-bezier(0.36, 0.07, 0.19, 0.97);
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-8px); }
            20%, 40%, 60%, 80% { transform: translateX(8px); }
        }
        
        .pulse-animation {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.8; transform: scale(1.05); }
        }
        
        .alert-fade-enter-active,
        .alert-fade-leave-active {
            transition: all 0.3s ease;
        }
        
        .alert-fade-enter-from {
            opacity: 0;
            transform: translateY(-10px);
        }
        
        /* Divider & Google Button */
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
            .modern-login-card {
                border-radius: 25px;
            }
            
            .login-glass-header {
                padding: 2.5rem 1.5rem 2rem;
            }
            
            .login-avatar {
                width: 75px;
                height: 75px;
                font-size: 3rem;
            }
            
            .login-welcome {
                font-size: 1.75rem;
            }
            
            .modern-login-form {
                padding: 2rem 1.5rem 1.5rem;
            }
            
            .modern-input-group {
                margin-bottom: 1.5rem;
            }
        }
        
        @media (max-width: 576px) {
            .login-glass-header {
                padding: 2rem 1.25rem 1.75rem;
            }
            
            .login-avatar {
                width: 65px;
                height: 65px;
                font-size: 2.5rem;
            }
            
            .login-welcome {
                font-size: 1.5rem;
            }
            
            .login-subtitle {
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <!-- Back to Home Button - Fixed Position -->
    <div class="back-home">
        <a href="index.php">
            <i class="fas fa-arrow-left"></i> Kembali ke Beranda
        </a>
    </div>
    
    <div class="login-container">
        <div id="loginApp">
            <!-- Fallback if Vue.js doesn't load -->
            <noscript>
                <div class="modern-login-card">
                    <div class="login-glass-header">
                        <div class="login-avatar">
                            <i class="fas fa-user-circle"></i>
                        </div>
                        <h3 class="login-welcome">Welcome Back!</h3>
                        <p class="login-subtitle">Login untuk melanjutkan</p>
                    </div>
                    
                    <form method="POST" action="" class="modern-login-form">
                        <?php if ($error): ?>
                            <div class="modern-alert modern-alert-error">
                                <i class="fas fa-exclamation-circle"></i>
                                <span><?php echo $error; ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <div class="modern-input-group has-value">
                            <div class="input-icon">
                                <i class="fas fa-user"></i>
                            </div>
                            <input type="text" class="modern-input" name="username" required>
                            <label class="floating-label">Username</label>
                            <div class="input-border"></div>
                        </div>
                        
                        <div class="modern-input-group has-value">
                            <div class="input-icon">
                                <i class="fas fa-lock"></i>
                            </div>
                            <input type="password" class="modern-input" name="password" required>
                            <label class="floating-label">Password</label>
                            <div class="input-border"></div>
                        </div>
                        
                        <button type="submit" class="modern-btn-login">
                            <span class="btn-content">
                                <i class="fas fa-sign-in-alt"></i>
                                <span>Login</span>
                                <i class="fas fa-arrow-right"></i>
                            </span>
                        </button>
                        
                        <!-- Google Login Button -->
                        <div class="divider-section">
                            <div class="divider-line"></div>
                            <span class="divider-text">atau</span>
                            <div class="divider-line"></div>
                        </div>
                        
                        <a href="auth/google_login.php" class="google-login-btn">
                            <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" alt="Google" class="google-icon">
                            <span>Login dengan Google</span>
                        </a>
                        
                        <div class="login-footer">
                            <p>Belum punya akun?</p>
                            <a href="register.php" class="register-link">
                                Daftar Sekarang <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </form>
                    <div class="card-decoration decoration-1"></div>
                    <div class="card-decoration decoration-2"></div>
                </div>
            </noscript>
            
            <modern-login-form :initial-error="<?php echo json_encode($error); ?>"></modern-login-form>
        </div>
    </div>

    <!-- Scripts - Bootstrap can be deferred -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>
    <script>

        window.addEventListener('DOMContentLoaded', function() {

            if (typeof Vue === 'undefined') {
            console.error('Vue.js failed to load');

            document.getElementById('loginApp').innerHTML = `
                <div class="modern-login-card">
                    <div class="login-glass-header">
                        <div class="login-avatar">
                            <i class="fas fa-user-circle"></i>
                        </div>
                        <h3 class="login-welcome">Welcome Back!</h3>
                        <p class="login-subtitle">Login untuk melanjutkan</p>
                    </div>
                    
                    <form method="POST" action="" class="modern-login-form">
                        <?php if ($error): ?>
                            <div class="modern-alert modern-alert-error">
                                <i class="fas fa-exclamation-circle"></i>
                                <span><?php echo htmlspecialchars($error); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <div class="modern-input-group">
                            <div class="input-icon">
                                <i class="fas fa-user"></i>
                            </div>
                            <input type="text" class="modern-input" name="username" placeholder="Username" required>
                            <div class="input-border"></div>
                        </div>
                        
                        <div class="modern-input-group">
                            <div class="input-icon">
                                <i class="fas fa-lock"></i>
                            </div>
                            <input type="password" class="modern-input" name="password" placeholder="Password" required>
                            <div class="input-border"></div>
                        </div>
                        
                        <button type="submit" class="modern-btn-login">
                            <span class="btn-content">
                                <i class="fas fa-sign-in-alt"></i>
                                <span>Login</span>
                                <i class="fas fa-arrow-right"></i>
                            </span>
                        </button>
                        
                        <!-- Google Login Button -->
                        <div class="divider-section">
                            <div class="divider-line"></div>
                            <span class="divider-text">atau</span>
                            <div class="divider-line"></div>
                        </div>
                        
                        <a href="auth/google_login.php" class="google-login-btn">
                            <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" alt="Google" class="google-icon">
                            <span>Login dengan Google</span>
                        </a>
                        
                        <div class="login-footer">
                            <p>Belum punya akun?</p>
                            <a href="register.php" class="register-link">
                                Daftar Sekarang <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </form>
                    <div class="card-decoration decoration-1"></div>
                    <div class="card-decoration decoration-2"></div>
                </div>
            `;
        } else {
            const { createApp } = Vue;
        
        const ModernLoginForm = {
            props: ['initialError'],
            template: `
                <div class="modern-login-card" :class="{ 'shake-animation': shakeError }">
                    <div class="login-glass-header">
                        <div class="login-avatar" :class="{ 'pulse-animation': loading }">
                            <i class="fas fa-user-circle"></i>
                        </div>
                        <h3 class="login-welcome">Welcome Back!</h3>
                        <p class="login-subtitle">Login untuk melanjutkan</p>
                    </div>
                    
                    <form @submit.prevent="handleLogin" class="modern-login-form">
                        <transition name="alert-fade">
                            <div v-if="error" class="modern-alert modern-alert-error">
                                <i class="fas fa-exclamation-circle"></i>
                                <span>{{ error }}</span>
                            </div>
                        </transition>
                        
                        <div class="modern-input-group" :class="{ 'is-focused': usernameFocused, 'has-value': username }">
                            <div class="input-icon">
                                <i class="fas fa-user"></i>
                            </div>
                            <input 
                                type="text" 
                                class="modern-input" 
                                v-model="username"
                                @focus="usernameFocused = true"
                                @blur="usernameFocused = false"
                                :disabled="loading"
                                name="username"
                                required
                            >
                            <label class="floating-label">Username</label>
                            <div class="input-border"></div>
                        </div>
                        
                        <div class="modern-input-group" :class="{ 'is-focused': passwordFocused, 'has-value': password }">
                            <div class="input-icon">
                                <i class="fas fa-lock"></i>
                            </div>
                            <input 
                                :type="showPassword ? 'text' : 'password'" 
                                class="modern-input" 
                                v-model="password"
                                @focus="passwordFocused = true"
                                @blur="passwordFocused = false"
                                :disabled="loading"
                                name="password"
                                required
                            >
                            <label class="floating-label">Password</label>
                            <button 
                                type="button" 
                                class="password-eye"
                                @click="showPassword = !showPassword"
                                :disabled="loading"
                            >
                                <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                            </button>
                            <div class="input-border"></div>
                        </div>
                        
                        <button 
                            type="submit" 
                            class="modern-btn-login"
                            :disabled="!isFormValid || loading"
                        >
                            <span v-if="!loading" class="btn-content">
                                <i class="fas fa-sign-in-alt"></i>
                                <span>Login</span>
                                <i class="fas fa-arrow-right"></i>
                            </span>
                            <span v-else class="btn-content">
                                <i class="fas fa-spinner fa-spin"></i>
                                <span>Memproses...</span>
                            </span>
                        </button>
                        
                        <!-- Google Login Button -->
                        <div class="divider-section">
                            <div class="divider-line"></div>
                            <span class="divider-text">atau</span>
                            <div class="divider-line"></div>
                        </div>
                        
                        <a href="auth/google_login.php" class="google-login-btn">
                            <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" alt="Google" class="google-icon">
                            <span>Login dengan Google</span>
                        </a>
                        
                        <div class="login-footer">
                            <p>Belum punya akun?</p>
                            <a href="register.php" class="register-link">
                                Daftar Sekarang <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </form>
                    
                    <div class="card-decoration decoration-1"></div>
                    <div class="card-decoration decoration-2"></div>
                </div>
            `,
            data() {
                return {
                    username: '',
                    password: '',
                    showPassword: false,
                    loading: false,
                    error: this.initialError || '',
                    shakeError: false,
                    usernameFocused: false,
                    passwordFocused: false
                }
            },
            computed: {
                isFormValid() {
                    return this.username.length > 0 && this.password.length > 0;
                }
            },
            mounted() {
                if (this.error) {
                    this.shakeError = true;
                    setTimeout(() => {
                        this.shakeError = false;
                    }, 500);
                }
            },
            methods: {
                async handleLogin() {
                    if (!this.isFormValid || this.loading) return;
                    
                    this.loading = true;
                    this.error = '';
                    
                    const formData = new FormData();
                    formData.append('username', this.username);
                    formData.append('password', this.password);
                    
                    try {
                        const response = await fetch('login.php', {
                            method: 'POST',
                            body: formData
                        });
                        
                        if (response.redirected) {
                            window.location.href = response.url;
                            return;
                        }
                        
                        const text = await response.text();
                        
                        if (text.includes('dashboard') || response.url.includes('dashboard')) {
                            window.location.href = response.url;
                        } else {
                            this.error = 'Username atau password salah!';
                            this.shakeError = true;
                            setTimeout(() => {
                                this.shakeError = false;
                            }, 500);
                            this.loading = false;
                        }
                    } catch (err) {
                        console.error('Login error:', err);
                        this.error = 'Terjadi kesalahan. Silakan coba lagi.';
                        this.shakeError = true;
                        setTimeout(() => {
                            this.shakeError = false;
                        }, 500);
                        this.loading = false;
                    }
                }
            }
        };
        
        const loginApp = createApp({
            components: {
                'modern-login-form': ModernLoginForm
            }
        });
        
        try {
            loginApp.mount('#loginApp');
            console.log('Vue login app mounted successfully');
        } catch (error) {
            console.error('Failed to mount Vue app:', error);

            document.getElementById('loginApp').innerHTML = `
                <div class="modern-login-card">
                    <div class="login-glass-header">
                        <div class="login-avatar">
                            <i class="fas fa-user-circle"></i>
                        </div>
                        <h3 class="login-welcome">Welcome Back!</h3>
                        <p class="login-subtitle">Login untuk melanjutkan</p>
                    </div>
                    
                    <form method="POST" action="" class="modern-login-form">
                        <?php if ($error): ?>
                            <div class="modern-alert modern-alert-error">
                                <i class="fas fa-exclamation-circle"></i>
                                <span><?php echo $error; ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <div class="modern-input-group">
                            <div class="input-icon">
                                <i class="fas fa-user"></i>
                            </div>
                            <input type="text" class="modern-input" name="username" placeholder="Username" required>
                            <div class="input-border"></div>
                        </div>
                        
                        <div class="modern-input-group">
                            <div class="input-icon">
                                <i class="fas fa-lock"></i>
                            </div>
                            <input type="password" class="modern-input" name="password" placeholder="Password" required>
                            <div class="input-border"></div>
                        </div>
                        
                        <button type="submit" class="modern-btn-login">
                            <span class="btn-content">
                                <i class="fas fa-sign-in-alt"></i>
                                <span>Login</span>
                                <i class="fas fa-arrow-right"></i>
                            </span>
                        </button>
                        
                        <!-- Google Login Button -->
                        <div class="divider-section">
                            <div class="divider-line"></div>
                            <span class="divider-text">atau</span>
                            <div class="divider-line"></div>
                        </div>
                        
                        <a href="auth/google_login.php" class="google-login-btn">
                            <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" alt="Google" class="google-icon">
                            <span>Login dengan Google</span>
                        </a>
                        
                        <div class="login-footer">
                            <p>Belum punya akun?</p>
                            <a href="register.php" class="register-link">
                                Daftar Sekarang <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </form>
                    <div class="card-decoration decoration-1"></div>
                    <div class="card-decoration decoration-2"></div>
                </div>
            `;
        }
            } // End of Vue check
        }); // End of DOMContentLoaded
    </script>
</body>
</html>
