<?php
/**
 * Get Single Found Person
 */

ini_set('display_errors', 0);
error_reporting(0);
ob_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../Database/Conn_db.php';

$found_id = isset($_GET['found_id']) ? (int)$_GET['found_id'] : 0;

if (!$found_id) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Found ID is required'
    ]);
    exit;
}

try {
    $sql = "SELECT 
                d.detection_id as found_id,
                d.report_id,
                mr.missing_name,
                d.image_path as photo_path,
                d.address as found_location,
                d.timestamp as found_datetime,
                d.confidence,
                u.fullname as added_by_name
            FROM detections d
            LEFT JOIN missing_reports mr ON d.report_id = mr.report_id
            LEFT JOIN users u ON mr.user_id = u.user_id
            WHERE d.detection_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $found_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    ob_clean();
    header('Content-Type: application/json');
    if ($row = $result->fetch_assoc()) {
        echo json_encode([
            'status' => 'success',
            'data' => $row
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Detection record not found'
        ]);
    }
    
    $stmt->close();

} catch (Exception $e) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>

