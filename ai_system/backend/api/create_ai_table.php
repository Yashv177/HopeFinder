<?php
/**
 * One-time: Create ai_detections table
 * Run once: http://localhost/HopeFinder/ai_system/backend/api/create_ai_table.php
 */
header('Content-Type: application/json');
require_once '../config/db.php';

$sql = "CREATE TABLE IF NOT EXISTS ai_detections (
  detection_id INT AUTO_INCREMENT PRIMARY KEY,
  report_id INT,
  image_path VARCHAR(255),
  confidence DECIMAL(5,2),
  timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_report (report_id),
  INDEX idx_timestamp (timestamp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql) === TRUE) {
    echo json_encode(['status' => 'success', 'message' => 'ai_detections table created/verified']);
} else {
    echo json_encode(['status' => 'error', 'message' => $conn->error]);
}
$conn->close();
?>

