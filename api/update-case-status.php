<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../Database/Conn_db.php';

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['case_id'])) {
    echo json_encode(['success' => false, 'error' => 'Missing case_id']);
    exit;
}

$report_id = $input['case_id'];
$status = $input['status'] ?? 'pending';

try {
    // Update missing_reports table using report_id
    $stmt = $conn->prepare("UPDATE missing_reports SET status = ? WHERE report_id = ?");
    $stmt->bind_param("si", $status, $report_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Case updated successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to update case']);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>

