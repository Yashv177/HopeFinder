<?php
/**
 * Get Report PDF
 * Used by reports-crud.js
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../Database/Conn_db.php';

$report_id = isset($_GET['report_id']) ? (int)$_GET['report_id'] : 0;

if (!$report_id) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Report ID is required'
    ]);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT missing_name, document_pdf FROM missing_reports WHERE report_id = ?");
    $stmt->bind_param('i', $report_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        if (!empty($row['document_pdf'])) {
            echo json_encode([
                'status' => 'success',
                'pdf_path' => $row['document_pdf'],
                'missing_name' => $row['missing_name']
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'No PDF available for this report'
            ]);
        }
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Report not found'
        ]);
    }
    
    $stmt->close();

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>

