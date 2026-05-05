<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(0);
ob_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../../Database/Conn_db.php';

$report_id = isset($_GET['report_id']) ? (int)$_GET['report_id'] : 0;
if (!$report_id) { echo json_encode(['status' => 'error', 'message' => 'Report ID required']); exit; }

$sql = "SELECT r.*, u.fullname as reporter_name, u.email as reporter_email 
        FROM missing_reports r LEFT JOIN users u ON r.user_id = u.user_id WHERE r.report_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $report_id);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

if ($result->num_rows === 0) { echo json_encode(['status' => 'error', 'message' => 'Report not found']); exit; }
$report = $result->fetch_assoc();

$assigned_police = null;
$sql2 = "SELECT u.fullname as police_name, p.badge_number, p.station_name, ra.assigned_at 
         FROM report_assignments ra LEFT JOIN police p ON ra.police_id = p.police_id 
         LEFT JOIN users u ON p.user_id = u.user_id WHERE ra.report_id = ?";
$stmt2 = $conn->prepare($sql2);
$stmt2->bind_param('i', $report_id);
$stmt2->execute();

$result2 = $stmt2->get_result();
$stmt2->close();

$assigned_police = null;
if ($row = $result2->fetch_assoc()) {
    $assigned_police = ['name' => $row['police_name'], 'badge' => $row['badge_number'], 
                        'station' => $row['station_name'], 'assigned_at' => $row['assigned_at']];
}


// Add detection fields - Fixed column names
$sql3 = "SELECT timestamp as found_time, address as found_location 
         FROM detections WHERE report_id = ? ORDER BY timestamp DESC LIMIT 1";
$stmt3 = $conn->prepare($sql3);
$stmt3->bind_param('i', $report_id);
$stmt3->execute();
$result3 = $stmt3->get_result();
$detection = $result3->fetch_assoc();
$stmt3->close();


ob_clean(); // Clear any output buffer
header('Content-Type: application/json');
ob_end_clean();
echo json_encode(['status' => 'success', 'data' => [

    'report_id' => $report['report_id'], 'missing_name' => $report['missing_name'],
    'age' => $report['age'], 'gender' => $report['gender'],
    'last_seen_location' => $report['last_seen_location'], 'last_seen_datetime' => $report['last_seen_datetime'],
    'description' => $report['description'], 'photo' => $report['photo'],
    'document_pdf' => $report['document_pdf'] ?? '',
    'contact_name' => $report['contact_name'] ?? '', 'contact_number' => $report['contact_number'] ?? '',
    'contact_relation' => $report['contact_relation'] ?? '', 'status' => $report['status'],
    'created_at' => $report['created_at'],
    'reporter' => ['name' => $report['reporter_name'], 'email' => $report['reporter_email']],
    'assigned_police' => $assigned_police,
    'detection' => $detection ?: null
]], JSON_UNESCAPED_SLASHES);


$conn->close();
?>

