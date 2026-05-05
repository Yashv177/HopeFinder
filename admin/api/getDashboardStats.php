<?php
/**
 * Get Dashboard Statistics - HopeFinder Admin
 * Cards: Total Missing, Found, Today Detections, AI Success
 */

ini_set('display_errors', 0);
error_reporting(0);
ob_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../Database/Conn_db.php';

function safeCount($conn, $table, $where = '') {
    try {
        $sql = "SELECT COUNT(*) as cnt FROM " . $table;
        if ($where) {
            $sql .= " WHERE " . $where;
        }
        $result = $conn->query($sql);
        if ($result && $row = $result->fetch_assoc()) {
            return (int)$row['cnt'];
        }
    } catch (Exception $e) {
        // Table might not exist, return 0
    }
    return 0;
}

try {
    $total_missing = safeCount($conn, 'missing_reports');
    $total_found = safeCount($conn, 'detections');
    $today_detections = safeCount($conn, 'detections', "DATE(timestamp) = CURDATE()");
    $ai_success = safeCount($conn, 'ai_matches', "status IN ('approved', 'confirmed')");

    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(array(
        "status" => "success",
        "total_missing" => $total_missing,
        "total_found" => $total_found,
        "today_detections" => $today_detections,
        "ai_success" => $ai_success
    ));

} catch (Exception $e) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(array(
        "status" => "error",
        "message" => $e->getMessage()
    ));
}

$conn->close();
?>
