<?php
require_once '../../Database/Conn_db.php';

header('Content-Type: application/json');

$query = "
SELECT 
    mr.report_id,
    mr.missing_name,
    d.address,
    MAX(d.timestamp) as timestamp,
    d.confidence
FROM detections d
JOIN missing_reports mr ON mr.report_id = d.report_id
WHERE mr.status = 'found'
GROUP BY mr.report_id
ORDER BY timestamp DESC
";

$result = mysqli_query($conn, $query);

$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
}

echo json_encode($data);