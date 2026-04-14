<?php
class Comment {
    private $conn;
    private $table_name = "report_comments";

    public $id;
    public $report_id;
    public $admin_id;
    public $comment;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;

        $this->ensureTableExists();
    }

    


    private function ensureTableExists() {
        try {

            $stmt = $this->conn->query("SHOW TABLES LIKE '{$this->table_name}'");
            if ($stmt->rowCount() == 0) {

                $sql = "CREATE TABLE IF NOT EXISTS `{$this->table_name}` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `report_id` int(11) NOT NULL,
                  `admin_id` int(11) NOT NULL COMMENT 'ID admin yang memberi komentar',
                  `comment` text NOT NULL,
                  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                  PRIMARY KEY (`id`),
                  KEY `report_id` (`report_id`),
                  KEY `admin_id` (`admin_id`),
                  CONSTRAINT `report_comments_ibfk_1` FOREIGN KEY (`report_id`) REFERENCES `reports` (`id`) ON DELETE CASCADE,
                  CONSTRAINT `report_comments_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
                
                $this->conn->exec($sql);
                error_log("Table {$this->table_name} created automatically");
            }
        } catch (PDOException $e) {

            error_log("Error checking/creating table {$this->table_name}: " . $e->getMessage());
        }
    }


    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (report_id, admin_id, comment) 
                  VALUES (:report_id, :admin_id, :comment)";
        
        $stmt = $this->conn->prepare($query);


        $stmt->bindParam(":report_id", $this->report_id);
        $stmt->bindParam(":admin_id", $this->admin_id);
        $stmt->bindParam(":comment", $this->comment);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }


    public function getByReportId($report_id) {
        $query = "SELECT c.*, u.nama as admin_nama, u.username as admin_username 
                  FROM " . $this->table_name . " c 
                  LEFT JOIN users u ON c.admin_id = u.id 
                  WHERE c.report_id = :report_id 
                  ORDER BY c.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":report_id", $report_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    public function getById($id) {
        $query = "SELECT c.*, u.nama as admin_nama, u.username as admin_username 
                  FROM " . $this->table_name . " c 
                  LEFT JOIN users u ON c.admin_id = u.id 
                  WHERE c.id = :id 
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return null;
    }


    public function update($id) {
        $query = "UPDATE " . $this->table_name . " 
                  SET comment = :comment 
                  WHERE id = :id AND admin_id = :admin_id";
        
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":comment", $this->comment);
        $stmt->bindParam(":id", $id);
        $stmt->bindParam(":admin_id", $this->admin_id);

        return $stmt->execute();
    }


    public function delete($id) {
        $query = "DELETE FROM " . $this->table_name . " 
                  WHERE id = :id AND admin_id = :admin_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->bindParam(":admin_id", $this->admin_id);
        
        return $stmt->execute();
    }


    public function countByReportId($report_id) {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " 
                  WHERE report_id = :report_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":report_id", $report_id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }
}
?>

