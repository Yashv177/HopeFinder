<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

require_once '../config/db.php';

$input = json_decode(file_get_contents('php://input'), true) ?: [];

$report_id = isset($input['report_id']) ? (int)$input['report_id'] : 0;
$image_path = isset($input['image_path']) ? trim($input['image_path']) : '';
$confidence = isset($input['confidence']) ? (float)$input['confidence'] : 0;
$timestamp_raw = isset($input['timestamp']) ? $input['timestamp'] : date('Y-m-d H:i:s');
$timestamp_value = strtotime($timestamp_raw);
$timestamp = $timestamp_value ? date('Y-m-d H:i:s', $timestamp_value) : date('Y-m-d H:i:s');
$confidence = max(0, min(100, $confidence));

// Production thresholds
$is_high_conf = $confidence >= 95;
$is_alert_conf = $confidence >= 90;

if (!$report_id || !$image_path || $confidence <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
    exit;
}

// Check for recent dedupe (5min cooldown per report)
$check_stmt = $conn->prepare("SELECT COUNT(*) as recent_count FROM ai_detections WHERE report_id = ? AND timestamp > DATE_SUB(NOW(), INTERVAL 5 MINUTE)");
$check_stmt->bind_param('i', $report_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result()->fetch_assoc();
$recent_count = (int)$check_result['recent_count'];
$check_stmt->close();

if ($recent_count > 0 && !$is_high_conf) {
    echo json_encode([
        'status' => 'duplicate', 
        'message' => 'Recent detection exists (cooldown)',
        'recent_count' => $recent_count
    ]);
    $conn->close();
    exit;
}

// High confidence: Mark as FOUND
if ($is_high_conf) {
    $update_stmt = $conn->prepare("UPDATE missing_reports SET status = 'found' WHERE report_id = ? AND status = 'assigned'");
    $update_stmt->bind_param('i', $report_id);
    $update_result = $update_stmt->execute();
    $update_stmt->close();
    
    $response_message = "Detection saved & status updated to FOUND (95%+ confidence)";
} else {
    $response_message = "Detection saved";
}

// Always log detection
$stmt = $conn->prepare("INSERT INTO ai_detections (report_id, image_path, confidence, timestamp) VALUES (?, ?, ?, ?)");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $conn->error]);
    $conn->close();
    exit;
}

$stmt->bind_param('isds', $report_id, $image_path, $confidence, $timestamp);

if ($stmt->execute()) {
    $high_conf_note = $is_high_conf ? ' (MARKED FOUND)' : '';
    $alert_note = $is_alert_conf ? ' (ALERT LEVEL)' : '';
    echo json_encode([
        'status' => 'success',
        'detection_id' => $conn->insert_id,
        'message' => $response_message . $high_conf_note . $alert_note,
        'confidence_level' => $is_high_conf ? 'found' : ($is_alert_conf ? 'alert' : 'log'),
        'confidence_pct' => round($confidence, 1)
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => $conn->error]);
}

$stmt->close();
$conn->close();
?>
