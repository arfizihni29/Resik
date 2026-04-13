<?php
require_once '../config/config.php';
require_once '../config/Database.php';

$database = new Database();
$db = $database->getConnection();

echo "<h2>Fixing Empty Waste Types...</h2>";


$query = "UPDATE laporan 
          SET jenis_sampah = CONCAT(UCASE(LEFT(kategori, 1)), SUBSTRING(kategori, 2)) 
          WHERE jenis_sampah IS NULL OR TRIM(jenis_sampah) = ''";

$stmt = $db->prepare($query);

if($stmt->execute()) {
    echo "<div style='color:green'>Successfully updated empty records!</div>";
    echo "Rows affected: " . $stmt->rowCount();
} else {
    echo "<div style='color:red'>Failed to update records.</div>";
    print_r($stmt->errorInfo());
}
?>
