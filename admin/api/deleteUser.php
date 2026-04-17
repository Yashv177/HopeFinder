<?php
/**
 * Delete User
 * Used by Dashboard2.php
 */

header('Content-Type: text/plain');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../Database/Conn_db.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

$user_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if (!$user_id) {
    echo "error";
    exit;
}

try {
    // Start transaction
    $conn->begin_transaction();

    // Delete from users table
    $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ? AND role = 'public'");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $conn->commit();
        echo "success";
    } else {
        $conn->rollback();
        echo "error";
    }

    $stmt->close();

} catch (Exception $e) {
    $conn->rollback();
    echo "error";
}

$conn->close();
?>

