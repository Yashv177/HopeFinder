<?php
/**
 * Get Single Found Person
 * Used by add-found-person.js
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../Database/Conn_db.php';

$found_id = isset($_GET['found_id']) ? (int)$_GET['found_id'] : 0;

if (!$found_id) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Found ID is required'
    ]);
    exit;
}

try {
    $sql = "SELECT 
                fp.*,
                u.fullname as added_by_name
            FROM found_persons fp
            LEFT JOIN users u ON fp.user_id = u.user_id
            WHERE fp.found_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $found_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo json_encode([
            'status' => 'success',
            'data' => $row
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Found person record not found'
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

