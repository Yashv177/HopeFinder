<?php
/**
 * Get Dashboard Statistics - HopeFinder Admin
 * Cards: Total Missing, Found, Today Detections, AI Success
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../Database/Conn_db.php';

try {
    // Total Missing Cases
    $stmt = $conn->prepare("SELECT COUNT(*) as total_missing FROM missing_reports");
    $stmt->execute();
    $total_missing = $stmt->get_result()->fetch_assoc()['total_missing'] ?? 0;
    $stmt->close();

    // Total Found Cases
    $stmt = $conn->prepare("SELECT COUNT(*) as total_found FROM found_persons");
    $stmt->execute();
    $total_found = $stmt->get_result()->fetch_assoc()['total_found'] ?? 0;
    $stmt->close();

    // Today's AI Detections
    $stmt = $conn->prepare("SELECT COUNT(*) as today_detections FROM detections WHERE DATE(timestamp) = CURDATE()");
    $stmt->execute();
    $today_detections = $stmt->get_result()->fetch_assoc()['today_detections'] ?? 0;
    $stmt->close();

    // AI Match Success
    $stmt = $conn->prepare("SELECT COUNT(*) as ai_success FROM ai_matches WHERE status IN ('approved', 'confirmed')");
    $stmt->execute();
    $ai_success = $stmt->get_result()->fetch_assoc()['ai_success'] ?? 0;
    $stmt->close();

    echo json_encode([
        "status" => "success",
        "total_missing" => (int)$total_missing,
        "total_found" => (int)$total_found,
        "today_detections" => (int)$today_detections,
        "ai_success" => (int)$ai_success
    ]);

} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}

$conn->close();
?>

