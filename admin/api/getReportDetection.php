<?php
/**
 * Get Detection Data for Report
 * Links detection table to report_id
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once '../../Database/Conn_db.php';

$report_id = isset($_GET['report_id']) ? (int)$_GET['report_id'] : 0;
if (!$report_id) {
    echo json_encode(['status' => 'error', 'message' => 'Report ID required']);
    exit;
}

try {
// Get detection data matching real schema: timestamp, latitude, longitude, address, image_path
    $stmt = $conn->prepare("
        SELECT timestamp, latitude, longitude, address, image_path, confidence 
        FROM detections 
        WHERE report_id = ? 
        ORDER BY timestamp DESC 
        LIMIT 1
    ");
    $stmt->bind_param('i', $report_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo json_encode([
            'status' => 'success',
            'data' => $row,
            'detection_time' => $row['detection_time'],
            'detection_location' => $row['detection_location']
        ]);
    } else {
        echo json_encode([
            'status' => 'no_data',
            'message' => 'No detection data available',
            'data' => null
        ]);
    }
    
    $stmt->close();
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
