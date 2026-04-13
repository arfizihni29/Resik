<?php
class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $conn;

    public function __construct() {

        $isLocalhost = $this->isLocalhost();
        
        if ($isLocalhost) {



            $this->host = "localhost";
            $this->db_name = "sampah_db";
            $this->username = "root";
            $this->password = "";
        } else {




            
            $this->host = "sql305.infinityfree.com";
            $this->db_name = "if0_40869378_sampah_db";
            $this->username = "if0_40869378";
            $this->password = "c0TV88fsaWwKXF2";
        }
    }
    
    /**
     * Cek apakah sedang running di localhost
     */
    private function isLocalhost() {
        $whitelist = array('127.0.0.1', '::1', 'localhost');
        return in_array($_SERVER['REMOTE_ADDR'], $whitelist) || 
               in_array($_SERVER['SERVER_NAME'], $whitelist) ||
               (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false);
    }

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password,
                array(
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4; SET time_zone = '+07:00'",
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                )
            );
        } catch(PDOException $e) {

            if ($this->isLocalhost()) {
                die("Database Connection Error: " . $e->getMessage());
            } else {

                error_log("Database Connection Error: " . $e->getMessage());
                die("Maaf, terjadi kesalahan koneksi database. Silakan hubungi administrator.");
            }
        }
        return $this->conn;
    }
    
    /**
     * Get current environment info
     */
    public function getInfo() {
        return [
            'host' => $this->host,
            'database' => $this->db_name,
            'username' => $this->username,
            'environment' => $this->isLocalhost() ? 'localhost' : 'hosting'
        ];
    }
}
?>
