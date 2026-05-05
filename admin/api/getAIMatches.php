<?php
/**
 * Get AI Matches
 */

ini_set('display_errors', 0);
error_reporting(0);
ob_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../Database/Conn_db.php';

$match_id = isset($_GET['match_id']) ? (int)$_GET['match_id'] : 0;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

try {
    if ($match_id) {
        // Get single match with details
        $sql = "SELECT 
                    m.*,
                    mr.missing_name,
                    mr.photo as missing_photo,
                    mr.last_seen_location,
                    d.image_path as found_photo,
                    d.address as found_location,
                    u.fullname as verified_by_name
                FROM ai_matches m
                LEFT JOIN missing_reports mr ON m.report_id = mr.report_id
                LEFT JOIN detections d ON m.found_id = d.detection_id
                LEFT JOIN users u ON m.verified_by = u.user_id
                WHERE m.match_id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $match_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        ob_clean();
        header('Content-Type: application/json');
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
        // Get all matches with pagination
        $whereClause = '';
        $params = [];
        $types = '';
        
        if ($search) {
            $whereClause = "WHERE (mr.missing_name LIKE ? OR m.status LIKE ?)";
            $params = ["%$search%", "%$search%"];
            $types = 'ss';
        }
        
        // Count total records
        $countSql = "SELECT COUNT(*) as total FROM ai_matches m
                     LEFT JOIN missing_reports mr ON m.report_id = mr.report_id
                     LEFT JOIN detections d ON m.found_id = d.detection_id
                     $whereClause";
        $countStmt = $conn->prepare($countSql);
        if ($search) {
            $countStmt->bind_param($types, ...$params);
        }
        $countStmt->execute();
        $totalRecords = $countStmt->get_result()->fetch_assoc()['total'];
        $countStmt->close();
        
        // Get paginated records
        $sql = "SELECT 
                    m.*,
                    mr.missing_name,
                    d.image_path
                FROM ai_matches m
                LEFT JOIN missing_reports mr ON m.report_id = mr.report_id
                LEFT JOIN detections d ON m.found_id = d.detection_id
                $whereClause
                ORDER BY m.created_at DESC
                LIMIT ? OFFSET ?";
        
        $stmt = $conn->prepare($sql);
        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $matches = [];
        while ($row = $result->fetch_assoc()) {
            $matches[] = $row;
        }
        $stmt->close();
        
        $totalPages = ceil($totalRecords / $limit);
        
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'data' => $matches,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_records' => $totalRecords,
                'per_page' => $limit
            ]
        ]);
    }

} catch (Exception $e) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>

