<?php
/**
 * Get Missing Reports with Pagination and Search
 * JOIN with users and police
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once '../../Database/Conn_db.php';

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

try {
    $whereClause = '';
    $params = [];
    $types = '';
    
    if ($search) {
        $whereClause = "WHERE (r.missing_name LIKE ? OR r.last_seen_location LIKE ? OR r.status LIKE ?)";
        $params = ["%$search%", "%$search%", "%$search%"];
        $types = 'sss';
    }
    
    // Count total
    $countStmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM missing_reports r
        LEFT JOIN users u ON r.user_id = u.user_id
        LEFT JOIN report_assignments ra ON r.report_id = ra.report_id
        LEFT JOIN police p ON ra.police_id = p.police_id 
        LEFT JOIN users pu ON p.user_id = pu.user_id
        $whereClause
    ");
    if ($search) {
        $countStmt->bind_param($types, ...$params);
    }
    $countStmt->execute();
    $totalRecords = $countStmt->get_result()->fetch_assoc()['total'];
    $countStmt->close();
    
    // Get records
    $stmt = $conn->prepare("
        SELECT 
            r.report_id, r.missing_name, 
            CONCAT(r.age, '/', r.gender) as age_gender,
            r.last_seen_location, 
            u.fullname as reporter_name,
            r.created_at, r.status,
            pu.fullname as assigned_officer,
            p.station_name as assigned_station
        FROM missing_reports r
        LEFT JOIN users u ON r.user_id = u.user_id
        LEFT JOIN report_assignments ra ON r.report_id = ra.report_id
        LEFT JOIN police p ON ra.police_id = p.police_id 
        LEFT JOIN users pu ON p.user_id = pu.user_id
        $whereClause
        ORDER BY r.created_at DESC 
        LIMIT ? OFFSET ?
    ");
    
    $params[] = $limit;
    $params[] = $offset;
    $types .= 'ii';
    
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $reports = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    $totalPages = ceil($totalRecords / $limit);
    
    echo json_encode([
        'status' => 'success',
        'data' => $reports,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_records' => $totalRecords,
            'per_page' => $limit
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>

