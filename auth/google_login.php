<?php





require_once '../config/config.php';
require_once '../config/google_config.php';



if (!defined('GOOGLE_CLIENT_SECRET') || empty(GOOGLE_CLIENT_SECRET) || GOOGLE_CLIENT_SECRET === '') {
    $_SESSION['error'] = 'Google OAuth belum dikonfigurasi. Harap isi GOOGLE_CLIENT_SECRET di file .env';
    if (strpos($_SERVER['REQUEST_URI'], 'dashboard') !== false) {

        redirect('login.php');
    } else {
        redirect('login.php');
    }
    exit;
}


$state = bin2hex(random_bytes(16));
$_SESSION['google_oauth_state'] = $state;


$params = [
    'client_id' => GOOGLE_CLIENT_ID,
    'redirect_uri' => GOOGLE_REDIRECT_URI,
    'response_type' => 'code',
    'scope' => GOOGLE_SCOPES,
    'state' => $state,
    'access_type' => 'online',
    'prompt' => 'select_account'
];

$authUrl = GOOGLE_AUTH_URL . '?' . http_build_query($params);


header('Location: ' . $authUrl);
exit;
?>

