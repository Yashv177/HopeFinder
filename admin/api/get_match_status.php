<?php
/**
 * AI Match Status Distribution
 * Doughnut chart data
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../Database/Conn_db.php';

try {
    $stmt = $conn->prepare("
        SELECT 
            status,
            COUNT(*) as count
        FROM ai_matches 
        GROUP BY status
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

