<?php
/**
 * Get All Found Persons
 * Used by add-found-person.js
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../Database/Conn_db.php';

try {
    $sql = "SELECT 
                fp.*,
                u.fullname as added_by_name
            FROM found_persons fp
            LEFT JOIN users u ON fp.user_id = u.user_id
            ORDER BY fp.created_at DESC";
    
    $result = $conn->query($sql);
    $persons = [];
    
    while ($row = $result->fetch_assoc()) {
        $persons[] = $row;
    }
    
    echo json_encode([
        'status' => 'success',
        'data' => $persons
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>

