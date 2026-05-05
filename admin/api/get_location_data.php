<?php
/**
 * Location-wise Missing Cases (Top 10)
 * Pie/Bar chart data
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../Database/Conn_db.php';

try {
    $stmt = $conn->prepare("
        SELECT 
            last_seen_location,
            COUNT(*) as case_count
        FROM missing_reports 
        GROUP BY last_seen_location 
        ORDER BY case_count DESC 
        LIMIT 10
    ");
    $stmt->execute();
    $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    echo json_encode([
        'status' => 'success',
        'data' => $data
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>

