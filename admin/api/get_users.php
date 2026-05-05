<?php
/**
 * Get Users with Pagination and Search
 * For Admin Dashboard
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once '../../Database/Conn_db.php';

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

try {
    $whereClause = "WHERE role = 'public'";
    $params = [];
    $types = '';
    
    if ($search) {
        $whereClause .= " AND (fullname LIKE ? OR email LIKE ?)";
        $params = ["%$search%", "%$search%"];
        $types = 'ss';
    }
    
    // Count total records
    $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM users $whereClause");
    if ($search) {
        $countStmt->bind_param($types, ...$params);
    }
    $countStmt->execute();
    $totalRecords = $countStmt->get_result()->fetch_assoc()['total'];
    $countStmt->close();
    
    // Get paginated records
    $stmt = $conn->prepare("
        SELECT user_id, fullname, email, created_at, 
               CASE WHEN status = 1 THEN 'Active' ELSE 'Inactive' END as status_label
        FROM users $whereClause 
        ORDER BY created_at DESC 
        LIMIT ? OFFSET ?
    ");
    
    $params[] = $limit;
    $params[] = $offset;
    $types .= 'ii';
    
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    $totalPages = ceil($totalRecords / $limit);
    
    echo json_encode([
        'status' => 'success',
        'data' => $users,
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

