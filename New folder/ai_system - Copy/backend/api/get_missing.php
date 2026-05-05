<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

require_once '../config/db.php';

$ids = $_GET['ids'] ?? [];

if (!empty($ids)) {
    // 🔥 Convert array safely
    if (is_array($ids)) {
        $ids = array_map('intval', $ids);
    } else {
        $ids = [intval($ids)];
    }

    $ids_str = implode(',', $ids);

    $query = "
        SELECT report_id, missing_name, photo 
        FROM missing_reports 
        WHERE report_id IN ($ids_str)
    ";
} else {
    $query = "
        SELECT report_id, missing_name, photo 
        FROM missing_reports 
        WHERE status IN ('assigned', 'searching') 
        AND photo IS NOT NULL AND photo != ''
        ORDER BY created_at DESC 
        LIMIT 20
    ";
}

$result = $conn->query($query);

if (!$result) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $conn->error]);
    $conn->close();
    exit;
}

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = [
        'id' => (int)$row['report_id'],
        'name' => $row['missing_name'],
        'photo' => $row['photo'] ?: null
    ];
}

echo json_encode($data);
$conn->close();
?>