<?php
/**
 * Get All Users
 * Used by Dashboard2.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../Database/Conn_db.php';

try {
    $sql = "SELECT 
                user_id,
                fullname,
                email,
                mobile_number,
                status,
                created_at
            FROM users 
            WHERE role = 'public'
            ORDER BY created_at DESC";
    
    $result = $conn->query($sql);
    $users = [];
    
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    
    // Return array directly (for Dashboard.js compatibility)
    echo json_encode($users);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>

