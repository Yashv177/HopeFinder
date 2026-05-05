<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

require_once '../config/db.php';

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
$limit = max(1, min(100, $limit));

$sql = "
SELECT 
    d.detection_id AS detection_id, 
    d.report_id, 
    d.image_path, 
    d.confidence,
    ROUND(d.confidence * 100, 1) AS confidence_pct,

    CASE 
        WHEN d.confidence >= 0.85 THEN 'FOUND'
        WHEN d.confidence >= 0.60 THEN 'ALERT'
        ELSE 'LOW'
    END AS confidence_level,

    m.missing_name AS name,
    d.latitude,
    d.longitude,
    d.timestamp 

FROM detections d 
LEFT JOIN missing_reports m ON d.report_id = m.report_id
ORDER BY d.timestamp DESC 
LIMIT $limit
";

$result = $conn->query($sql);

if (!$result) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $conn->error
    ]);
    exit;
}

$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = [
        'id' => (int)$row['detection_id'],
        'report_id' => (int)$row['report_id'],
        'name' => $row['name'] ?? 'Unknown',
        'image_path' => $row['image_path'],
        'confidence' => (float)$row['confidence'],
        'confidence_pct' => (float)$row['confidence_pct'],
        'confidence_level' => $row['confidence_level'],
        'latitude' => (float)$row['latitude'],
        'longitude' => (float)$row['longitude'],
        'timestamp' => $row['timestamp']
    ];
}

echo json_encode($data);
$conn->close();
?>