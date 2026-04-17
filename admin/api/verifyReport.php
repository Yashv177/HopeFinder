<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once '../../Database/Conn_db.php';

$report_id = isset($_POST['report_id']) ? (int)$_POST['report_id'] : 0;
if (!$report_id) { echo json_encode(['status' => 'error', 'message' => 'Report ID required']); exit; }

$stmt = $conn->prepare("SELECT status FROM missing_reports WHERE report_id = ?");
$stmt->bind_param('i', $report_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    if ($row['status'] !== 'pending') { echo json_encode(['status' => 'error', 'message' => 'Only pending reports can be verified']); exit; }
    
    $new_status = 'verified';
    $stmt2 = $conn->prepare("UPDATE missing_reports SET status = ? WHERE report_id = ?");
    $stmt2->bind_param('si', $new_status, $report_id);
    $stmt2->execute();
    $stmt2->close();
    
    echo json_encode(['status' => 'success', 'message' => 'Report verified successfully']);
} else { echo json_encode(['status' => 'error', 'message' => 'Report not found']); }
$stmt->close();
$conn->close();
?>

