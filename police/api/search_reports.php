<?php
require_once '../../Database/Conn_db.php';

$search = $_GET['search'] ?? '';

$query = "
SELECT * FROM missing_reports
WHERE 
report_id LIKE '%$search%' OR
missing_name LIKE '%$search%' OR
last_seen_location LIKE '%$search%'
";

$res = mysqli_query($conn, $query);

$data = [];
while($row = mysqli_fetch_assoc($res)) {
    $data[] = $row;
}

echo json_encode($data);