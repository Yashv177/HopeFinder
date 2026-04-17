<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

require_once '../config/db.php';

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
$limit = max(1, min(100, $limit));

$result = $conn->query("
    SELECT detection_id, report_id, image_path, confidence, timestamp 
    FROM ai_detections 
    ORDER BY timestamp DESC 
    LIMIT $limit
");

if (!$result) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $conn->error]);
    $conn->close();
    exit;
}

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = [
        'id' => (int)$row['detection_id'],
        'report_id' => (int)$row['report_id'],
        'image_path' => $row['image_path'],
        'confidence' => (float)$row['confidence'],
        'timestamp' => $row['timestamp']
    ];
}

echo json_encode($data);
$conn->close();
?>
