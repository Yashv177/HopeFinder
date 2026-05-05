<?php
header('Content-Type: application/json');
include_once '../../Database/Conn_db.php';

$session_id = $_GET['session'] ?? '';
$limit = (int)($_GET['limit'] ?? 10);

if (empty($session_id)) {
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("
    SELECT report_id, image_path, confidence, timestamp, location_lat, location_lng 
    FROM live_detections 
    WHERE session_id = ? 
    ORDER BY timestamp DESC 
    LIMIT ?
");
$stmt->bind_param("si", $session_id, $limit);
$stmt->execute();
$result = $stmt->get_result();

$detections = [];
while ($row = $result->fetch_assoc()) {
    $detections[] = $row;
}

echo json_encode($detections);
?>
