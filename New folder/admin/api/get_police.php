<?php
/**
 * Get Police/Authorities with Pagination and Search
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once '../../Database/Conn_db.php';

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

try {
    $whereClause = "WHERE u.role = 'police'";
    $params = [];
    $types = '';
    
    if ($search) {
        $whereClause .= " AND (u.fullname LIKE ? OR u.email LIKE ? OR p.station_name LIKE ?)";
        $params = ["%$search%", "%$search%", "%$search%"];
        $types = 'sss';
    }
    
    // Count total
    $countStmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM users u 
        LEFT JOIN police p ON u.user_id = p.user_id 
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
        SELECT u.user_id, u.fullname, u.email, 
               CONCAT(p.station_name, ', ', p.district) as location,
               CASE WHEN u.status = 1 THEN 'Active' ELSE 'Inactive' END as status_label,
               p.badge_number
        FROM users u 
        LEFT JOIN police p ON u.user_id = p.user_id 
        $whereClause
        ORDER BY u.created_at DESC 
        LIMIT ? OFFSET ?
    ");
    
    $params[] = $limit;
    $params[] = $offset;
    $types .= 'ii';
    
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $police = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    $totalPages = ceil($totalRecords / $limit);
    
    echo json_encode([
        'status' => 'success',
        'data' => $police,
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

