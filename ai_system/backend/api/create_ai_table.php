<?php
/**
 * One-time: Create ai_detections table
 * Run once: http://localhost/HopeFinder/ai_system/backend/api/create_ai_table.php
 */
header('Content-Type: application/json');
require_once '../config/db.php';

$sql = "CREATE TABLE IF NOT EXISTS detections (
  detection_id INT AUTO_INCREMENT PRIMARY KEY,
  report_id INT,
  confidence DECIMAL(5,2),
  latitude DECIMAL(10,6),
  longitude DECIMAL(10,6),
  image_path VARCHAR(255),
  timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_report (report_id),
  INDEX idx_timestamp (timestamp),
  INDEX idx_lat_lng (latitude, longitude)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql) === TRUE) {
    echo json_encode(['status' => 'success', 'message' => 'detections table created/verified (GPS-enabled)']);
} else {
    echo json_encode(['status' => 'error', 'message' => $conn->error]);
}
$conn->close();
?>

