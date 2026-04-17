<?php
header('Content-Type: application/json');
include_once '../../Database/Conn_db.php';

$input = json_decode(file_get_contents('php://input'), true);

$report_id = (int)($input['report_id'] ?? 0);
$image_path = $input['image_path'] ?? '';
$confidence = (float)($input['confidence'] ?? 0);
$session_id = $input['session_id'] ?? '';
$lat = $input['lat'] ?? null;
$lng = $input['lng'] ?? null;

if ($report_id <= 0) {
    echo json_encode(['error' => 'Invalid report_id']);
    exit;
}

// Insert live detection
$stmt = $conn->prepare("
    INSERT INTO live_detections (session_id, report_id, image_path, confidence, location_lat, location_lng) 
    VALUES (?, ?, ?, ?, ?, ?)
");
$stmt->bind_param("sisidd", $session_id, $report_id, $image_path, $confidence, $lat, $lng);
$stmt->execute();

// Update missing_reports with found info (if high confidence >80%)
if ($confidence > 80) {
    $update_stmt = $conn->prepare("
        UPDATE missing_reports 
        SET found_image = ?, found_time = NOW(), found_location = CONCAT(?, ', ', ?) 
        WHERE report_id = ? AND status != 'closed'
    ");
    $update_stmt->bind_param("ssdsi", $image_path, $lat, $lng, $report_id);
    $update_stmt->execute();
}

echo json_encode(['status' => 'success', 'detection_id' => $conn->insert_id]);
?>
