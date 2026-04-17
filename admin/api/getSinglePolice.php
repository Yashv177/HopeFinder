<?php
/**
 * Get Single Police Officer Details
 * Used by Dashboard2.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../Database/Conn_db.php';

$police_id = isset($_GET['police_id']) ? (int)$_GET['police_id'] : 0;

if (!$police_id) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Police ID is required'
    ]);
    exit;
}

try {
    $sql = "SELECT 
                police_id,
                user_id,
                fullname,
                station_name,
                district,
                state,
                badge_number
            FROM police
            WHERE police_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $police_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Get email and status from users table
        $stmt2 = $conn->prepare("SELECT email, status FROM users WHERE user_id = ?");
        $stmt2->bind_param('i', $row['user_id']);
        $stmt2->execute();
        $userResult = $stmt2->get_result();
        $user = $userResult->fetch_assoc();
        $row['email'] = $user['email'] ?? '';
        $row['status'] = $user['status'] ?? 'active';
        $stmt2->close();
        
        echo json_encode([
            'status' => 'success',
            'data' => $row
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Police officer not found'
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

