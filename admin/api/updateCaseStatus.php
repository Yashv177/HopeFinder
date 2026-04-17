<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once '../../Database/Conn_db.php';

$input = json_decode(file_get_contents('php://input'), true);
$_POST = $input ?? $_POST;

$report_id = isset($_POST['report_id']) ? (int)$_POST['report_id'] : 0;
$status = isset($_POST['status']) ? $_POST['status'] : '';

$valid_statuses = ['pending', 'verified', 'assigned', 'closed'];

if (!$report_id || !in_array($status, $valid_statuses)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid report ID or status']);
    exit;
}

$stmt = $conn->prepare("UPDATE missing_reports SET status = ? WHERE report_id = ?");
$stmt->bind_param('si', $status, $report_id);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Case status updated']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to update status']);
}
$stmt->close();
$conn->close();
?>

