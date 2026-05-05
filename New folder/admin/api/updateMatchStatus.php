<?php
/**
 * Update Match Status
 * Used by add-found-person.js
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../Database/Conn_db.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Please login to update match status'
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$_POST = $input ?? $_POST;

$match_id = isset($_POST['match_id']) ? (int)$_POST['match_id'] : 0;
$status = isset($_POST['status']) ? $_POST['status'] : '';

$valid_statuses = ['pending', 'confirmed', 'rejected'];

if (!$match_id || !in_array($status, $valid_statuses)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid match ID or status'
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    $stmt = $conn->prepare("UPDATE ai_matches SET status = ?, verified_by = ?, verified_at = NOW() WHERE match_id = ?");
    $stmt->bind_param('sii', $status, $user_id, $match_id);
    
    if ($stmt->execute()) {
        // If confirmed, update report and found person status
        if ($status === 'confirmed') {
            // Get match details
            $stmt2 = $conn->prepare("SELECT report_id, found_id FROM ai_matches WHERE match_id = ?");
            $stmt2->bind_param('i', $match_id);
            $stmt2->execute();
            $result = $stmt2->get_result();
            
            if ($row = $result->fetch_assoc()) {
                // Close the missing report
                $stmt3 = $conn->prepare("UPDATE missing_reports SET status = 'closed', closed_at = NOW() WHERE report_id = ?");
                $stmt3->bind_param('i', $row['report_id']);
                $stmt3->execute();
                $stmt3->close();
                
                // Update found person status
                $stmt4 = $conn->prepare("UPDATE found_persons SET match_status = 'matched' WHERE found_id = ?");
                $stmt4->bind_param('i', $row['found_id']);
                $stmt4->execute();
                $stmt4->close();
            }
            $stmt2->close();
        }
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Match status updated successfully'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to update status'
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

