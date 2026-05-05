<?php
/**
 * Add Found Person Record
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
        'message' => 'Please login to add a found person record'
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

$user_id = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);
$_POST = $input ?? $_POST;

$found_name = isset($_POST['found_name']) ? trim($_POST['found_name']) : '';
$found_location = isset($_POST['found_location']) ? trim($_POST['found_location']) : '';
$found_datetime = isset($_POST['found_datetime']) ? $_POST['found_datetime'] : '';
$description = isset($_POST['description']) ? trim($_POST['description']) : '';
$contact_info = isset($_POST['contact_info']) ? trim($_POST['contact_info']) : '';
$photo_data = isset($_POST['photo']) ? $_POST['photo'] : '';

$errors = [];
if (empty($found_location)) $errors[] = 'Found location is required';
if (empty($found_datetime)) $errors[] = 'Found date & time is required';

if (!empty($errors)) {
    echo json_encode([
        'status' => 'error',
        'message' => implode(', ', $errors)
    ]);
    exit;
}

$photo_path = null;
if (!empty($photo_data)) {
    $upload_dir = '../../uploads/found_persons/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
    
    $image_parts = explode(";base64,", $photo_data);
    if (count($image_parts) == 2) {
        $image_type_aux = explode("image/", $image_parts[0]);
        $image_type = $image_type_aux[1] ?? 'jpg';
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($image_type, $allowed_types)) {
            $filename = 'found_' . time() . '_' . uniqid() . '.' . $image_type;
            $file_path = $upload_dir . $filename;
            
            if (file_put_contents($file_path, base64_decode($image_parts[1]))) {
                $photo_path = 'uploads/found_persons/' . $filename;
            }
        }
    }
}

$mysql_datetime = date('Y-m-d H:i:s', strtotime($found_datetime));

// Insert into detections table instead (treat manual found persons as detections)
// Get a report to associate with (or use NULL if standalone)
$report_id = isset($_POST['report_id']) ? (int)$_POST['report_id'] : null;

$stmt = $conn->prepare("INSERT INTO detections 
    (report_id, image_path, confidence, address, timestamp) 
    VALUES (?, ?, 100, ?, ?)");
$stmt->bind_param('isss', 
    $report_id, $photo_path, $found_location, $mysql_datetime);

if ($stmt->execute()) {
    $found_id = $stmt->insert_id;
    echo json_encode([
        'status' => 'success',
        'message' => 'Found person record added successfully',
        'found_id' => $found_id
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to add record: ' . $conn->error
    ]);
}

$stmt->close();
$conn->close();
?>

