x<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once '../../Database/Conn_db.php';

$report_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$report_id) { 
  echo json_encode(['status' => 'error', 'message' => 'Report ID required']); 
  exit; 
}

$sql = "SELECT mr.*, u.fullname as reporter_name 
        FROM missing_reports mr LEFT JOIN users u ON mr.user_id = u.user_id WHERE mr.report_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $report_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) { 
  echo json_encode(['status' => 'error', 'message' => 'Report not found']); 
  exit; 
}
$report = $result->fetch_assoc();

echo json_encode(['status' => 'success', 'data' => $report]);
$conn->close();
?>

