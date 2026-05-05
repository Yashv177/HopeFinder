<?php
header('Content-Type: application/json');

include_once '../../Database/Conn_db.php';

if ($conn->connect_error) {
    echo json_encode(['error' => 'DB connection failed']);
    exit;
}

$result = $conn->query("
    SELECT report_id, missing_name, photo 
    FROM missing_reports 
    WHERE status IN ('searching', 'assigned') 
    ORDER BY created_at DESC
");

$data = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            'id' => $row['report_id'],
            'name' => $row['missing_name'],
            'photo' => $row['photo'] ? basename($row['photo']) : 'no-photo.jpg'
        ];
    }
}

echo json_encode($data);
?>

