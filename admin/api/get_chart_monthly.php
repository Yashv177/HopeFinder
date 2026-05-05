<?php
/**
 * Monthly Missing vs Found Chart Data
 * Line chart data for dashboard
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../Database/Conn_db.php';

try {
    // Missing reports by month (last 12 months)
    $stmt = $conn->prepare("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as missing_count
        FROM missing_reports 
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
        GROUP BY month 
        ORDER BY month ASC
    ");
    $stmt->execute();
    $missing = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Found persons by month (last 12 months) - from detections table
    $stmt = $conn->prepare("
        SELECT 
            DATE_FORMAT(timestamp, '%Y-%m') as month,
            COUNT(*) as found_count
        FROM detections 
        WHERE timestamp >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
        GROUP BY month 
        ORDER BY month ASC
    ");
    $stmt->execute();
    $found = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    echo json_encode([
        'status' => 'success',
        'data' => [
            'missing' => $missing,
            'found' => $found
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>

