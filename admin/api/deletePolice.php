<?php
header('Content-Type: text/plain');
header('Access-Control-Allow-Origin: *');
require_once '../../Database/Conn_db.php';

$user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
if (!$user_id) { echo "error"; exit; }

try {
    $conn->begin_transaction();
    
    $stmt = $conn->prepare("SELECT police_id FROM police WHERE user_id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $police_id = $row['police_id'];
        $stmt2 = $conn->prepare("DELETE FROM report_assignments WHERE police_id = ?");
        $stmt2->bind_param('i', $police_id);
        $stmt2->execute();
        $stmt2->close();
        
        $stmt3 = $conn->prepare("DELETE FROM police WHERE user_id = ?");
        $stmt3->bind_param('i', $user_id);
        $stmt3->execute();
        $stmt3->close();
    }
    $stmt->close();

    $stmt4 = $conn->prepare("DELETE FROM users WHERE user_id = ? AND role = 'police'");
    $stmt4->bind_param('i', $user_id);
    $stmt4->execute();

    if ($stmt4->affected_rows > 0) { $conn->commit(); echo "success"; }
    else { $conn->rollback(); echo "error"; }
    $stmt4->close();

} catch (Exception $e) { $conn->rollback(); echo "error"; }
$conn->close();

