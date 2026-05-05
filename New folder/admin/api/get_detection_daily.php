<?php
/**
 * Daily AI Detection Activity (Last 7 days)
 * Line chart for dashboard
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../Database/Conn_db.php';

try {
    $stmt = $conn->prepare("
        SELECT 
            DATE(timestamp) as day,
            COUNT(*) as detection_count
        FROM detections 
        WHERE timestamp >= CURDATE() - INTERVAL 7 DAY
        GROUP BY day 
        ORDER BY day ASC
    ");
    $stmt->execute();
    $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    echo json_encode([
        "status" => "success",
        "data" => $data
    ]);

} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}

$conn->close();
?>

