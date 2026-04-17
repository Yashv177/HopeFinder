<?php
/**
 * Toggle Police Status (Block/Activate)
 * Used by Dashboard2.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../Database/Conn_db.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

$user_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if (!$user_id) {
    echo json_encode([
        'status' => 'error',
        'message' => 'User ID is required'
    ]);
    exit;
}

try {
    // Get current status
    $stmt = $conn->prepare("SELECT status FROM users WHERE user_id = ? AND role = 'police'");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $current_status = $row['status'];
        $new_status = ($current_status === 'active') ? 'blocked' : 'active';
        
        $stmt2 = $conn->prepare("UPDATE users SET status = ? WHERE user_id = ?");
        $stmt2->bind_param('si', $new_status, $user_id);
        $stmt2->execute();
        $stmt2->close();
        
        // Return plain status for Dashboard.js compatibility
        echo $new_status;
    } else {
        echo "error";
    }

    $stmt->close();

} catch (Exception $e) {
    echo "error";
}

$conn->close();
?>

