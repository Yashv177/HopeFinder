<?php
/**
 * Update Police Officer Details
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

$input = json_decode(file_get_contents('php://input'), true);
$_POST = $input ?? $_POST;

$police_id = isset($_POST['police_id']) ? (int)$_POST['police_id'] : 0;
$fullname = isset($_POST['fullname']) ? trim($_POST['fullname']) : '';
$badge_number = isset($_POST['badge_number']) ? trim($_POST['badge_number']) : '';
$station_name = isset($_POST['station_name']) ? trim($_POST['station_name']) : '';
$district = isset($_POST['district']) ? trim($_POST['district']) : '';
$state = isset($_POST['state']) ? trim($_POST['state']) : '';

if (!$police_id) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Police ID is required'
    ]);
    exit;
}

$errors = [];
if (empty($fullname)) $errors[] = 'Full name is required';
if (empty($badge_number)) $errors[] = 'Badge number is required';
if (empty($station_name)) $errors[] = 'Station name is required';
if (empty($district)) $errors[] = 'District is required';
if (empty($state)) $errors[] = 'State is required';

if (!empty($errors)) {
    echo json_encode([
        'status' => 'error',
        'message' => implode(', ', $errors)
    ]);
    exit;
}

try {
    // Get user_id for this police
    $stmt = $conn->prepare("SELECT user_id FROM police WHERE police_id = ?");
    $stmt->bind_param('i', $police_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $user_id = $row['user_id'];
        
        // Update users table
        $stmt2 = $conn->prepare("UPDATE users SET fullname = ? WHERE user_id = ?");
        $stmt2->bind_param('si', $fullname, $user_id);
        $stmt2->execute();
        $stmt2->close();
        
        // Update police table
        $stmt3 = $conn->prepare("UPDATE police SET badge_number = ?, station_name = ?, district = ?, state = ?, fullname = ? WHERE police_id = ?");
        $stmt3->bind_param('sssssi', $badge_number, $station_name, $district, $state, $fullname, $police_id);
        $stmt3->execute();
        $stmt3->close();
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Police officer updated successfully'
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

