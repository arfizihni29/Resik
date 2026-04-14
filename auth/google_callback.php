<?php





require_once '../config/config.php';
require_once '../config/Database.php';
require_once '../config/google_config.php';
require_once '../classes/User.php';


if (isLoggedIn()) {
    if (isAdmin()) {
        redirect('admin/dashboard.php');
    } else {
        redirect('user/dashboard.php');
    }
    exit;
}


if (isset($_GET['error'])) {
    $_SESSION['error'] = 'Gagal autentikasi Google: ' . htmlspecialchars($_GET['error']);
    redirect('login.php');
    exit;
}


if (!isset($_GET['code'])) {
    $_SESSION['error'] = 'Authorization code tidak ditemukan.';
    redirect('login.php');
    exit;
}

$code = $_GET['code'];
$state = $_GET['state'] ?? '';


if (!isset($_SESSION['google_oauth_state']) || $state !== $_SESSION['google_oauth_state']) {
    $_SESSION['error'] = 'Invalid state parameter. Security check failed.';
    redirect('login.php');
    exit;
}


$tokenData = exchangeCodeForToken($code);

if (!$tokenData || !isset($tokenData['access_token'])) {
    $_SESSION['error'] = 'Gagal mendapatkan access token dari Google.';
    redirect('login.php');
    exit;
}


$userInfo = getUserInfoFromGoogle($tokenData['access_token']);

if (!$userInfo || !isset($userInfo['email'])) {
    $_SESSION['error'] = 'Gagal mendapatkan informasi user dari Google.';
    redirect('login.php');
    exit;
}


$database = new Database();
$db = $database->getConnection();
$user = new User($db);


$existingUser = getUserByGoogleIdOrEmail($db, $userInfo['id'], $userInfo['email']);

if ($existingUser) {

    $_SESSION['user_id'] = $existingUser['id'];
    $_SESSION['username'] = $existingUser['username'];
    $_SESSION['nama'] = $existingUser['nama'];
    $_SESSION['role'] = $existingUser['role'];
    $_SESSION['last_login'] = date('Y-m-d H:i:s');
    $_SESSION['login_method'] = 'google';
    

    if (empty($existingUser['google_id'])) {
        updateGoogleId($db, $existingUser['id'], $userInfo['id']);
    }
    

    if ($existingUser['role'] === 'admin') {
        redirect('admin/dashboard.php');
    } else {
        redirect('user/dashboard.php');
    }
} else {

    $newUser = createGoogleUser($db, $userInfo);
    
    if ($newUser) {
        $_SESSION['user_id'] = $newUser['id'];
        $_SESSION['username'] = $newUser['username'];
        $_SESSION['nama'] = $newUser['nama'];
        $_SESSION['role'] = $newUser['role'];
        $_SESSION['last_login'] = date('Y-m-d H:i:s');
        $_SESSION['login_method'] = 'google';
        $_SESSION['success'] = 'Akun berhasil dibuat dengan Google! Silakan lengkapi profil Anda.';
        

        redirect('user/profile.php');
    } else {
        $_SESSION['error'] = 'Gagal membuat akun baru.';
        redirect('login.php');
    }
}




function exchangeCodeForToken($code) {
    $params = [
        'code' => $code,
        'client_id' => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri' => GOOGLE_REDIRECT_URI,
        'grant_type' => 'authorization_code'
    ];
    
    $ch = curl_init(GOOGLE_TOKEN_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        error_log("Google token exchange failed: HTTP $httpCode - $response");
        return null;
    }
    
    return json_decode($response, true);
}




function getUserInfoFromGoogle($accessToken) {
    $ch = curl_init(GOOGLE_USERINFO_URL . '?access_token=' . urlencode($accessToken));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $accessToken]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        error_log("Google userinfo failed: HTTP $httpCode - $response");
        return null;
    }
    
    return json_decode($response, true);
}




function getUserByGoogleIdOrEmail($db, $googleId, $email) {

    $hasGoogleIdColumn = false;
    try {
        $checkCol = $db->query("SHOW COLUMNS FROM users LIKE 'google_id'");
        $hasGoogleIdColumn = $checkCol->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("Error checking google_id column: " . $e->getMessage());
    }
    

    if ($hasGoogleIdColumn) {
        try {
            $query = "SELECT * FROM users WHERE google_id = :google_id LIMIT 1";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':google_id', $googleId);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                return $stmt->fetch(PDO::FETCH_ASSOC);
            }
        } catch (PDOException $e) {
            error_log("Error querying google_id: " . $e->getMessage());
        }
    }
    


    try {
        $query = "SELECT * FROM users WHERE email = :email LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {

        error_log("Email column check failed: " . $e->getMessage());
    }
    

    try {
        $query = "SELECT * FROM users WHERE username = :email LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        error_log("Username check failed: " . $e->getMessage());
    }
    
    return null;
}




function createGoogleUser($db, $userInfo) {

    $username = explode('@', $userInfo['email'])[0];
    

    $originalUsername = $username;
    $counter = 1;
    while (usernameExists($db, $username)) {
        $username = $originalUsername . $counter;
        $counter++;
    }
    


    $hasEmailColumn = false;
    try {
        $checkCol = $db->query("SHOW COLUMNS FROM users LIKE 'email'");
        $hasEmailColumn = $checkCol->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("Column check failed: " . $e->getMessage());
    }
    

    $hasAlamatColumn = true;
    try {
        $checkCol = $db->query("SHOW COLUMNS FROM users LIKE 'alamat'");
        $hasAlamatColumn = $checkCol->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("Column check failed: " . $e->getMessage());
    }
    


    $defaultLat = '3.5952'; 
    $defaultLng = '98.6722';
    $defaultAlamat = 'Lokasi belum diatur - Silakan lengkapi di halaman profile';
    
    if ($hasEmailColumn && $hasAlamatColumn) {
        $query = "INSERT INTO users 
                  (username, password, nama, email, google_id, google_picture, alamat, latitude, longitude, role, created_at) 
                  VALUES (:username, :password, :nama, :email, :google_id, :google_picture, :alamat, :latitude, :longitude, :role, NOW())";
    } else if ($hasEmailColumn) {
        $query = "INSERT INTO users 
                  (username, password, nama, email, google_id, google_picture, role, created_at) 
                  VALUES (:username, :password, :nama, :email, :google_id, :google_picture, :role, NOW())";
    } else if ($hasAlamatColumn) {
        $query = "INSERT INTO users 
                  (username, password, nama, google_id, google_picture, alamat, latitude, longitude, role, created_at) 
                  VALUES (:username, :password, :nama, :google_id, :google_picture, :alamat, :latitude, :longitude, :role, NOW())";
    } else {
        $query = "INSERT INTO users 
                  (username, password, nama, google_id, google_picture, role, created_at) 
                  VALUES (:username, :password, :nama, :google_id, :google_picture, :role, NOW())";
    }
    
    $stmt = $db->prepare($query);
    

    $randomPassword = password_hash(bin2hex(random_bytes(32)), PASSWORD_BCRYPT);
    
    $nama = $userInfo['name'] ?? $userInfo['email'];
    $email = $userInfo['email'];
    $googleId = $userInfo['id'];
    $picture = $userInfo['picture'] ?? null;
    $role = 'user';
    
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':password', $randomPassword);
    $stmt->bindParam(':nama', $nama);
    if ($hasEmailColumn) {
        $stmt->bindParam(':email', $email);
    }
    $stmt->bindParam(':google_id', $googleId);
    $stmt->bindParam(':google_picture', $picture);

    if ($hasAlamatColumn) {
        $stmt->bindParam(':alamat', $defaultAlamat);
        $stmt->bindParam(':latitude', $defaultLat);
        $stmt->bindParam(':longitude', $defaultLng);
    }
    $stmt->bindParam(':role', $role);
    
    if ($stmt->execute()) {
        $userId = $db->lastInsertId();
        return getUserById($db, $userId);
    }
    
    return null;
}




function updateGoogleId($db, $userId, $googleId) {
    $query = "UPDATE users SET google_id = :google_id WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':google_id', $googleId);
    $stmt->bindParam(':id', $userId);
    return $stmt->execute();
}




function usernameExists($db, $username) {
    $query = "SELECT id FROM users WHERE username = :username LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    return $stmt->rowCount() > 0;
}




function getUserById($db, $id) {
    $query = "SELECT * FROM users WHERE id = :id LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    return null;
}
?>

