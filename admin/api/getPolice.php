<?php
/**
 * Get All Police Officers
 * Used by Dashboard2.php and reportsModule.js
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../Database/Conn_db.php';

try {
    $sql = "SELECT 
                p.police_id,
                p.user_id,
                p.fullname,
                p.station_name,
                p.district,
                p.state,
                p.badge_number,
                u.email,
                u.status
            FROM police p
            LEFT JOIN users u ON p.user_id = u.user_id
            ORDER BY p.fullname ASC";
    
    $result = $conn->query($sql);
    $police = [];
    
    while ($row = $result->fetch_assoc()) {
        $police[] = $row;
    }
    
    // Return array directly (for Dashboard.js compatibility)
    echo json_encode($police);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>

