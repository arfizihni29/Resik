<?php
session_start();

require_once __DIR__ . '/env.php';


date_default_timezone_set('Asia/Jakarta');


function isLocalhost() {
    $whitelist = array('127.0.0.1', '::1', 'localhost');
    return in_array($_SERVER['REMOTE_ADDR'] ?? '', $whitelist) || 
           in_array($_SERVER['SERVER_NAME'] ?? '', $whitelist) ||
           (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false);
}


if (isLocalhost()) {

    define('BASE_URL', 'http://localhost/bambang/');
    define('ENVIRONMENT', 'development');
} else {

    

    if (defined('FORCE_BASE_URL') && !empty(FORCE_BASE_URL)) {
        $baseUrl = rtrim(FORCE_BASE_URL, '/') . '/';
        define('BASE_URL', $baseUrl);
        define('ENVIRONMENT', 'production');
    } else {

        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || 
                    (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'resik.infinityfreeapp.com';
        


        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
        

        $scriptPath = dirname($scriptName);
        if ($scriptPath === '.' || $scriptPath === '/') {
            $scriptPath = '';
        } else {
            $scriptPath = rtrim($scriptPath, '/') . '/';
        }
        

        if (strpos($host, 'infinityfreeapp.com') !== false || strpos($host, 'resik.infinityfreeapp.com') !== false) {
            $scriptPath = ''; 
        }
        

        $baseUrl = rtrim($protocol . $host, '/') . '/' . $scriptPath;
        $baseUrl = rtrim($baseUrl, '/') . '/';
        

        $baseUrl = preg_replace('#([^:])//+#', '$1/', $baseUrl);
        
        define('BASE_URL', $baseUrl);
        define('ENVIRONMENT', 'production');
    }
}


define('UPLOAD_DIR', __DIR__ . '/../uploads/');

$uploadUrl = rtrim(BASE_URL, '/') . '/uploads/';
define('UPLOAD_URL', $uploadUrl);


function getImageUrl($filename) {
    if (empty($filename)) return '';

    $cleanFilename = ltrim($filename, '/');

    $fullUrl = rtrim(UPLOAD_URL, '/') . '/' . $cleanFilename;

    $fullUrl = preg_replace('/([^:])\/\//', '$1/', $fullUrl);
    return $fullUrl;
}


if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true); 
}


date_default_timezone_set('Asia/Jakarta');

if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/error.log');
}



function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function redirect($url) {
    header("Location: " . BASE_URL . $url);
    exit();
}

function checkLogin() {
    if (!isLoggedIn()) {
        redirect('login.php');
    }
}

function checkAdmin() {
    if (!isAdmin()) {
        redirect('index.php');
    }
}


if (ENVIRONMENT === 'production') {

    header('X-Frame-Options: SAMEORIGIN');
    

    header('X-XSS-Protection: 1; mode=block');
    

    header('X-Content-Type-Options: nosniff');
    


}
?>
