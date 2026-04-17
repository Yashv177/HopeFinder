<?php
/**
 * Get AI Matches
 * Used by add-found-person.js
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../Database/Conn_db.php';

$match_id = isset($_GET['match_id']) ? (int)$_GET['match_id'] : 0;

try {
    if ($match_id) {
        // Get single match with details
        $sql = "SELECT 
                    m.*,
                    mr.missing_name,
                    mr.photo as missing_photo,
                    mr.last_seen_location,
                    fp.found_name,
                    fp.photo_path as found_photo,
                    fp.found_location,
                    u.fullname as verified_by_name
                FROM ai_matches m
                LEFT JOIN missing_reports mr ON m.report_id = mr.report_id
                LEFT JOIN found_persons fp ON m.found_id = fp.found_id
                LEFT JOIN users u ON m.verified_by = u.user_id
                WHERE m.match_id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $match_id);
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
                'message' => 'Match not found'
            ]);
        }
        $stmt->close();
    } else {
        // Get all matches
        $sql = "SELECT 
                    m.*,
                    mr.missing_name,
                    fp.found_name
                FROM ai_matches m
                LEFT JOIN missing_reports mr ON m.report_id = mr.report_id
                LEFT JOIN found_persons fp ON m.found_id = fp.found_id
                ORDER BY m.created_at DESC";
        
        $result = $conn->query($sql);
        $matches = [];
        
        while ($row = $result->fetch_assoc()) {
            $matches[] = $row;
        }
        
        echo json_encode([
            'status' => 'success',
            'data' => $matches
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>

