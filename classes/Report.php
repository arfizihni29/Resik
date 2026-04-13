<?php
class Report {
    private $conn;
    private $table_name = "reports";

    public $id;
    public $user_id; // Nullable for guest
    public $guest_name; // For guest reporting
    public $kategori;
    public $jenis_sampah;
    public $gambar;
    public $deskripsi;
    public $lokasi_latitude;
    public $lokasi_longitude;
    public $alamat_lokasi;
    public $whatsapp_number; // Nomor WhatsApp user
    public $confidence;
    public $engine_prediction;
    public $is_corrected;
    public $correction_note;
    public $tags; // Tags untuk analitik
    public $status;
    public $created_at;
    public $admin_correction;
    public $admin_feedback;

    public function __construct($db) {
        $this->conn = $db;

        $this->ensureColumnsExist();
    }

    /**
     * Auto-create missing columns jika belum ada
     */
    private function ensureColumnsExist() {
        try {

            $stmt = $this->conn->query("SHOW COLUMNS FROM {$this->table_name} LIKE 'whatsapp_number'");
            if ($stmt->rowCount() == 0) {

                $sql = "ALTER TABLE `{$this->table_name}` 
                        ADD COLUMN `whatsapp_number` varchar(20) DEFAULT NULL 
                        COMMENT 'Nomor WhatsApp user untuk notifikasi' 
                        AFTER `alamat_lokasi`";
                $this->conn->exec($sql);
                error_log("Column whatsapp_number added to {$this->table_name} automatically");
            }


            $stmt = $this->conn->query("SHOW COLUMNS FROM {$this->table_name} LIKE 'guest_name'");
            if ($stmt->rowCount() == 0) {

                $sql = "ALTER TABLE `{$this->table_name}` 
                        ADD COLUMN `guest_name` varchar(100) DEFAULT NULL 
                        COMMENT 'Nama pelapor tamu (jika user_id null)' 
                        AFTER `user_id`";
                $this->conn->exec($sql);
                error_log("Column guest_name added to {$this->table_name} automatically");
            }




            try {
                $sql = "ALTER TABLE `{$this->table_name}` MODIFY COLUMN `user_id` int(11) NULL";
                $this->conn->exec($sql);
            } catch (PDOException $e) {

            }


            $stmt = $this->conn->query("SHOW COLUMNS FROM {$this->table_name} LIKE 'rejection_reason'");
            if ($stmt->rowCount() == 0) {

                $sql = "ALTER TABLE `{$this->table_name}` 
                        ADD COLUMN `rejection_reason` text DEFAULT NULL 
                        COMMENT 'Alasan penolakan laporan oleh admin' 
                        AFTER `correction_note`";
                $this->conn->exec($sql);
                error_log("Column rejection_reason added to {$this->table_name} automatically");
            }


            $stmt = $this->conn->query("SHOW COLUMNS FROM {$this->table_name} LIKE 'admin_correction'");
            if ($stmt->rowCount() == 0) {

                $sql = "ALTER TABLE `{$this->table_name}` 
                        ADD COLUMN `admin_correction` enum('organik','anorganik','b3') DEFAULT NULL 
                        COMMENT 'Koreksi kategori oleh admin' 
                        AFTER `status`";
                $this->conn->exec($sql);
                error_log("Column admin_correction added to {$this->table_name} automatically");
            }


            $stmt = $this->conn->query("SHOW COLUMNS FROM {$this->table_name} LIKE 'admin_feedback'");
            if ($stmt->rowCount() == 0) {

                $sql = "ALTER TABLE `{$this->table_name}` 
                        ADD COLUMN `admin_feedback` text DEFAULT NULL 
                        COMMENT 'Catatan koreksi dari admin' 
                        AFTER `admin_correction`";
                $this->conn->exec($sql);
                error_log("Column admin_feedback added to {$this->table_name} automatically");
            }


            try {
                $sql = "ALTER TABLE `{$this->table_name}` 
                        MODIFY COLUMN `status` enum('pending','diproses','selesai','ditolak') DEFAULT 'pending'";
                $this->conn->exec($sql);
                error_log("Status enum updated to include 'ditolak'");
            } catch (PDOException $e) {

                error_log("Status enum might already include 'ditolak': " . $e->getMessage());
            }
        } catch (PDOException $e) {

            error_log("Error checking/creating columns in {$this->table_name}: " . $e->getMessage());
        }
    }


    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (user_id, guest_name, kategori, jenis_sampah, gambar, deskripsi, lokasi_latitude, lokasi_longitude, 
                   alamat_lokasi, whatsapp_number, confidence, engine_prediction, is_corrected, correction_note, tags, status, created_at) 
                  VALUES (:user_id, :guest_name, :kategori, :jenis_sampah, :gambar, :deskripsi, :lokasi_latitude, 
                          :lokasi_longitude, :alamat_lokasi, :whatsapp_number, :confidence, :engine_prediction, 
                          :is_corrected, :correction_note, :tags, :status, :created_at)";
        
        $stmt = $this->conn->prepare($query);


        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":guest_name", $this->guest_name);
        $stmt->bindParam(":kategori", $this->kategori);
        $stmt->bindParam(":jenis_sampah", $this->jenis_sampah);
        $stmt->bindParam(":gambar", $this->gambar);
        $stmt->bindParam(":deskripsi", $this->deskripsi);
        $stmt->bindParam(":lokasi_latitude", $this->lokasi_latitude);
        $stmt->bindParam(":lokasi_longitude", $this->lokasi_longitude);
        $stmt->bindParam(":alamat_lokasi", $this->alamat_lokasi);
        $stmt->bindParam(":whatsapp_number", $this->whatsapp_number);
        $stmt->bindParam(":confidence", $this->confidence);
        $stmt->bindParam(":engine_prediction", $this->engine_prediction);
        $stmt->bindParam(":is_corrected", $this->is_corrected);
        $stmt->bindParam(":correction_note", $this->correction_note);
        $stmt->bindParam(":tags", $this->tags);
        $stmt->bindParam(":status", $this->status);
        

        $timestamp = date('Y-m-d H:i:s');
        $stmt->bindParam(":created_at", $timestamp);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }


    public function getByUserId($user_id) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE user_id = :user_id 
                  ORDER BY created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    public function getAllReports() {
        $query = "SELECT r.*, u.nama as user_nama, u.username 
                  FROM " . $this->table_name . " r 
                  LEFT JOIN users u ON r.user_id = u.id 
                  ORDER BY r.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    public function updateStatus($id, $status) {
        $query = "UPDATE " . $this->table_name . " 
                  SET status = :status 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":status", $status);
        $stmt->bindParam(":id", $id);

        return $stmt->execute();
    }


    public function rejectReport($id, $rejection_reason = null) {
        $query = "UPDATE " . $this->table_name . " 
                  SET status = 'ditolak', rejection_reason = :rejection_reason 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":rejection_reason", $rejection_reason);
        $stmt->bindParam(":id", $id);

        return $stmt->execute();
    }


    public function updateAdminCorrection($id, $admin_correction, $admin_feedback) {

        $reportData = $this->getById($id);
        

        $query = "UPDATE " . $this->table_name . " 
                  SET kategori = :category,
                      admin_correction = :correction, 
                      admin_feedback = :feedback 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":category", $admin_correction); // Update actual category too
        $stmt->bindParam(":correction", $admin_correction);
        $stmt->bindParam(":feedback", $admin_feedback);
        $stmt->bindParam(":id", $id);

        if ($stmt->execute()) {

            if ($reportData && !empty($reportData['engine_prediction']) && $reportData['engine_prediction'] !== $admin_correction) {

                if (!class_exists('CorrectionManager')) {
                    require_once __DIR__ . '/CorrectionManager.php';
                }
                
                $cm = new CorrectionManager();
                $sourcePath = UPLOAD_DIR . $reportData['gambar'];
                

                $cm->saveCorrectedImage($sourcePath, $reportData['engine_prediction'], $admin_correction, $id);
            }
            return true;
        }
        return false;
    }


    public function getById($id) {
        $query = "SELECT r.*, u.nama as user_nama, u.username, u.nomor_hp as user_nomor_hp 
                  FROM " . $this->table_name . " r 
                  LEFT JOIN users u ON r.user_id = u.id 
                  WHERE r.id = :id 
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return null;
    }


    public function getStatistics() {
        $query = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN kategori = 'organik' THEN 1 ELSE 0 END) as organik,
                    SUM(CASE WHEN kategori = 'anorganik' THEN 1 ELSE 0 END) as anorganik,
                    SUM(CASE WHEN kategori = 'b3' THEN 1 ELSE 0 END) as b3,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'diproses' THEN 1 ELSE 0 END) as diproses,
                    SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) as selesai,
                    SUM(CASE WHEN status = 'ditolak' THEN 1 ELSE 0 END) as ditolak
                  FROM " . $this->table_name;
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }


    public function update($id) {
        $query = "UPDATE " . $this->table_name . " 
                  SET kategori = :kategori,
                      jenis_sampah = :jenis_sampah,
                      gambar = :gambar,
                      deskripsi = :deskripsi,
                      lokasi_latitude = :lokasi_latitude,
                      lokasi_longitude = :lokasi_longitude,
                      alamat_lokasi = :alamat_lokasi,
                      confidence = :confidence,
                      engine_prediction = :engine_prediction,
                      is_corrected = :is_corrected,
                      correction_note = :correction_note
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);


        $stmt->bindParam(":kategori", $this->kategori);
        $stmt->bindParam(":jenis_sampah", $this->jenis_sampah);
        $stmt->bindParam(":gambar", $this->gambar);
        $stmt->bindParam(":deskripsi", $this->deskripsi);
        $stmt->bindParam(":lokasi_latitude", $this->lokasi_latitude);
        $stmt->bindParam(":lokasi_longitude", $this->lokasi_longitude);
        $stmt->bindParam(":alamat_lokasi", $this->alamat_lokasi);
        $stmt->bindParam(":confidence", $this->confidence);
        $stmt->bindParam(":engine_prediction", $this->engine_prediction);
        $stmt->bindParam(":is_corrected", $this->is_corrected);
        $stmt->bindParam(":correction_note", $this->correction_note);
        $stmt->bindParam(":id", $id);

        return $stmt->execute();
    }


    public function delete($id) {

        $query = "SELECT gambar FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $image_path = UPLOAD_DIR . $row['gambar'];
            if(file_exists($image_path)) {
                unlink($image_path);
            }
        }


        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        return $stmt->execute();
    }

}
?>

