<?php
class User {
    private $conn;
    private $table_name = "users";

    public $id;
    public $username;
    public $password;
    public $nama;
    public $nomor_hp;
    public $alamat;
    public $latitude;
    public $longitude;
    public $role;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }


    public function register() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (username, password, nama, nomor_hp, alamat, latitude, longitude, role) 
                  VALUES (:username, :password, :nama, :nomor_hp, :alamat, :latitude, :longitude, :role)";
        
        $stmt = $this->conn->prepare($query);


        $hashed_password = password_hash($this->password, PASSWORD_BCRYPT);


        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":password", $hashed_password);
        $stmt->bindParam(":nama", $this->nama);
        $stmt->bindParam(":nomor_hp", $this->nomor_hp);
        $stmt->bindParam(":alamat", $this->alamat);
        $stmt->bindParam(":latitude", $this->latitude);
        $stmt->bindParam(":longitude", $this->longitude);
        $stmt->bindParam(":role", $this->role);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }


    public function login($username, $password) {
        $query = "SELECT id, username, password, nama, role FROM " . $this->table_name . " 
                  WHERE username = :username LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if(password_verify($password, $row['password'])) {
                $this->id = $row['id'];
                $this->username = $row['username'];
                $this->nama = $row['nama'];
                $this->role = $row['role'];
                return true;
            }
        }
        return false;
    }


    public function getUserById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return null;
    }


    public function usernameExists($username) {
        $query = "SELECT id FROM " . $this->table_name . " WHERE username = :username LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }


    public function getAllUsers() {

        $hasGoogleColumns = false;
        try {
            $checkCol = $this->conn->query("SHOW COLUMNS FROM {$this->table_name} LIKE 'google_id'");
            $hasGoogleColumns = $checkCol->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error checking google_id column: " . $e->getMessage());
        }
        
        if ($hasGoogleColumns) {
            $query = "SELECT id, username, nama, nomor_hp, alamat, latitude, longitude, role, created_at, google_id, email, google_picture 
                      FROM " . $this->table_name . " 
                      ORDER BY created_at DESC";
        } else {
            $query = "SELECT id, username, nama, nomor_hp, alamat, latitude, longitude, role, created_at 
                      FROM " . $this->table_name . " 
                      ORDER BY created_at DESC";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    public function loginWithGoogle($googleId) {

        try {
            $checkCol = $this->conn->query("SHOW COLUMNS FROM {$this->table_name} LIKE 'google_id'");
            if ($checkCol->rowCount() == 0) {

                error_log("google_id column not found in users table");
                return false;
            }
        } catch (PDOException $e) {
            error_log("Error checking google_id column: " . $e->getMessage());
            return false;
        }

        $query = "SELECT id, username, password, nama, role, google_id 
                  FROM " . $this->table_name . " 
                  WHERE google_id = :google_id LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":google_id", $googleId);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->username = $row['username'];
            $this->nama = $row['nama'];
            $this->role = $row['role'];
            return true;
        }
        return false;
    }


    public function getUserByGoogleId($googleId) {
        try {
            $checkCol = $this->conn->query("SHOW COLUMNS FROM {$this->table_name} LIKE 'google_id'");
            if ($checkCol->rowCount() == 0) {
                return null;
            }
        } catch (PDOException $e) {
            error_log("Error checking google_id column: " . $e->getMessage());
            return null;
        }

        $query = "SELECT * FROM " . $this->table_name . " WHERE google_id = :google_id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":google_id", $googleId);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return null;
    }


    public function getUserByEmail($email) {
        try {
            $checkCol = $this->conn->query("SHOW COLUMNS FROM {$this->table_name} LIKE 'email'");
            if ($checkCol->rowCount() == 0) {

                $query = "SELECT * FROM " . $this->table_name . " WHERE username = :email LIMIT 1";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(":email", $email);
                $stmt->execute();
                
                if($stmt->rowCount() > 0) {
                    return $stmt->fetch(PDO::FETCH_ASSOC);
                }
                return null;
            }
        } catch (PDOException $e) {
            error_log("Error checking email column: " . $e->getMessage());
            return null;
        }

        $query = "SELECT * FROM " . $this->table_name . " WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return null;
    }
}
?>





