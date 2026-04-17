<?php
/**
 * Get Selected Missing Persons for AI Targets
 * Usage: GET /admin/api/get_selected_targets.php?report_ids[]=1&report_ids[]=2&report_ids[]=5
 * Returns: [{report_id, missing_name, photo_path}, ...]
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../Database/Conn_db.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$report_ids = isset($_GET['report_ids']) ? $_GET['report_ids'] : [];
if (empty($report_ids) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $report_ids = $input['report_ids'] ?? [];
}

if (empty($report_ids)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'No report IDs provided'
    ]);
    exit;
}

// Sanitize IDs
$report_ids = array_map('intval', $report_ids);
if (empty($report_ids)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid report IDs'
    ]);
    exit;
}

try {
    $placeholders = str_repeat('?,', count($report_ids) - 1) . '?';
    $sql = "SELECT report_id, missing_name, photo AS photo_path 
            FROM missing_reports 
            WHERE report_id IN ($placeholders) 
            AND photo IS NOT NULL 
            AND status != 'closed'";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(str_repeat('i', count($report_ids)), ...$report_ids);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $targets = [];
    while ($row = $result->fetch_assoc()) {
        $targets[] = [
            'report_id' => (int)$row['report_id'],
            'missing_name' => $row['missing_name'],
            'photo_path' => $row['photo_path'] // e.g., 'uploads/missing_persons/xxx.jpg'
        ];
    }
    
