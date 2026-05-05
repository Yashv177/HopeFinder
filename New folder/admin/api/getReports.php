<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once '../../Database/Conn_db.php';

try {
    $sql = "SELECT 
                r.report_id,
                r.missing_name,
                r.age,
                r.gender,
                r.last_seen_location,
                r.last_seen_datetime,
                r.description,
                r.photo,
                r.status,
                r.created_at,
                u.fullname as reporter_name,
                u.email as reporter_email,
                pu.fullname as police_name,
                p.station_name as police_station
            FROM missing_reports r
            LEFT JOIN users u ON r.user_id = u.user_id
            LEFT JOIN report_assignments ra ON r.report_id = ra.report_id
            LEFT JOIN police p ON ra.police_id = p.police_id
            LEFT JOIN users pu ON p.user_id = pu.user_id
            ORDER BY r.created_at DESC";
    
    $result = $conn->query($sql);
    $reports = [];
    
    while ($row = $result->fetch_assoc()) {
        $reports[] = $row;
    }
    
ob_clean();
header('Content-Type: application/json');
echo json_encode(['status' => 'success', 'data' => $reports], JSON_UNESCAPED_SLASHES);


} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
?>

