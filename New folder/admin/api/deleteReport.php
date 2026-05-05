<?php
/**
 * Delete Report
 * Used by Dashboard2.php and reportsModule.js
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../Database/Conn_db.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

$report_id = isset($_POST['report_id']) ? (int)$_POST['report_id'] : 0;

if (!$report_id) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Report ID is required'
    ]);
    exit;
}

try {
    // Start transaction
    $conn->begin_transaction();
    
    // Delete assignments first
    $stmt = $conn->prepare("DELETE FROM report_assignments WHERE report_id = ?");
    $stmt->bind_param('i', $report_id);
    $stmt->execute();
    $stmt->close();
    
    // Delete the report
    $stmt2 = $conn->prepare("DELETE FROM missing_reports WHERE report_id = ?");
    $stmt2->bind_param('i', $report_id);
    $stmt2->execute();
    
    if ($stmt2->affected_rows > 0) {
        $conn->commit();
        echo json_encode([
            'status' => 'success',
            'message' => 'Report deleted successfully'
        ]);
    } else {
        $conn->rollback();
        echo json_encode([
            'status' => 'error',
            'message' => 'Report not found'
        ]);
    }
    
    $stmt2->close();

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>

