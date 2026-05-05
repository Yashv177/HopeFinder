<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once '../../Database/Conn_db.php';

$report_id = isset($_POST['report_id']) ? (int)$_POST['report_id'] : 0;
$police_id = isset($_POST['police_id']) ? (int)$_POST['police_id'] : 0;

if (!$report_id || !$police_id) { echo json_encode(['status' => 'error', 'message' => 'Report ID and Police ID required']); exit; }

$stmt = $conn->prepare("SELECT status FROM missing_reports WHERE report_id = ?");
$stmt->bind_param('i', $report_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    if ($row['status'] !== 'verified') { echo json_encode(['status' => 'error', 'message' => 'Only verified reports can be assigned']); exit; }
    
    $stmt2 = $conn->prepare("SELECT police_id FROM police WHERE police_id = ?");
    $stmt2->bind_param('i', $police_id);
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    
    if ($result2->num_rows === 0) { echo json_encode(['status' => 'error', 'message' => 'Police not found']); exit; }
    $stmt2->close();
    
    $stmt3 = $conn->prepare("SELECT assign_id FROM report_assignments WHERE report_id = ?");
    $stmt3->bind_param('i', $report_id);
    $stmt3->execute();
    $result3 = $stmt3->get_result();
    
    if ($result3->num_rows > 0) { echo json_encode(['status' => 'error', 'message' => 'Already assigned']); exit; }
    $stmt3->close();
    
    $stmt5 = $conn->prepare("INSERT INTO report_assignments (report_id, police_id, assigned_at) VALUES (?, ?, NOW())");
    $stmt5->bind_param('ii', $report_id, $police_id);
    $stmt5->execute();
    $stmt5->close();
    
    $new_status = 'assigned';
    $stmt6 = $conn->prepare("UPDATE missing_reports SET status = ? WHERE report_id = ?");
    $stmt6->bind_param('si', $new_status, $report_id);
    $stmt6->execute();
    $stmt6->close();
    
    echo json_encode(['status' => 'success', 'message' => 'Police assigned successfully']);
} else { echo json_encode(['status' => 'error', 'message' => 'Report not found']); }
$stmt->close();
$conn->close();
?>

