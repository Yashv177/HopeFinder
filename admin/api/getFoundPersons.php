<?php
/**
 * Get All Found Persons
 */

ini_set('display_errors', 0);
error_reporting(0);
ob_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../Database/Conn_db.php';

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

try {
    $whereClause = '';
    $params = [];
    $types = '';
    
    if ($search) {
        $whereClause = "WHERE (mr.missing_name LIKE ? OR d.address LIKE ?)";
        $params = ["%$search%", "%$search%"];
        $types = 'ss';
    }
    
    // Count total records
    $countSql = "SELECT COUNT(*) as total FROM detections d 
                 LEFT JOIN missing_reports mr ON d.report_id = mr.report_id 
                 LEFT JOIN users u ON mr.user_id = u.user_id 
                 $whereClause";
    $countStmt = $conn->prepare($countSql);
    if ($search) {
        $countStmt->bind_param($types, ...$params);
    }
    $countStmt->execute();
    $totalRecords = $countStmt->get_result()->fetch_assoc()['total'];
    $countStmt->close();
    
    // Get paginated records
    $sql = "SELECT 
                d.detection_id as found_id,
                mr.report_id,
                mr.missing_name as found_name,
                d.image_path as photo_path,
                d.address as found_location,
                d.timestamp as found_datetime,
                d.confidence,
                u.fullname as added_by_name
            FROM detections d
            LEFT JOIN missing_reports mr ON d.report_id = mr.report_id
            LEFT JOIN users u ON mr.user_id = u.user_id
            $whereClause
            ORDER BY d.timestamp DESC
            LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($sql);
    $params[] = $limit;
    $params[] = $offset;
    $types .= 'ii';
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $persons = [];
    while ($row = $result->fetch_assoc()) {
        $persons[] = $row;
    }
    $stmt->close();
    
    $totalPages = ceil($totalRecords / $limit);
    
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'data' => $persons,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_records' => $totalRecords,
            'per_page' => $limit
        ]
    ]);

} catch (Exception $e) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>

