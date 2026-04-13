<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../config/config.php';
require_once '../config/Database.php';

$database = new Database();
$db = $database->getConnection();

try {

    $query = "SELECT 
        id,
        kategori,
        jenis_sampah,
        lokasi_latitude,
        lokasi_longitude,
        DATE_FORMAT(created_at, '%d %b %Y') as created_at
    FROM reports 
    WHERE status != 'pending'
    ORDER BY created_at DESC 
    LIMIT 100";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'reports' => $reports,
        'count' => count($reports)
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching reports',
        'error' => $e->getMessage()
    ]);
}
?>











