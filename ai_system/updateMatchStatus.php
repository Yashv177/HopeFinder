<?php
/**
 * Update Match Status
 * POST match_id, status (confirmed/rejected/low_confidence)
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../Database/Conn_db.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}
$match_id = isset($input['match_id']) ? (int)$input['match_id'] : 0;
$status = $input['status'] ?? '';

if (!$match_id || !in_array($status, ['confirmed', 'rejected', 'low_confidence'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid parameters']);
    exit;
}

try {
    $stmt = $conn->prepare("UPDATE ai_matches SET status = ?, verified_by = ?, verified_at = NOW() WHERE match_id = ?");
    $stmt->bind_param('sii', $status, $user_id, $match_id);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        echo json_encode(['status' => 'success', 'message' => 'Match status updated']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Match not found']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
