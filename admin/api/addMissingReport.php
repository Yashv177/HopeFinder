<?php
/**
 * Add Missing Report
 * Used by add-missing-report.js
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
        'message' => 'Please login to submit a report'
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

$user_id = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);
$_POST = $input ?? $_POST;

$missing_name = isset($_POST['missing_name']) ? trim($_POST['missing_name']) : '';
$age = isset($_POST['age']) ? (int)$_POST['age'] : null;
$gender = isset($_POST['gender']) ? $_POST['gender'] : null;
$last_seen_location = isset($_POST['last_seen_location']) ? trim($_POST['last_seen_location']) : '';
$last_seen_datetime = isset($_POST['last_seen_datetime']) ? $_POST['last_seen_datetime'] : '';
$description = isset($_POST['description']) ? trim($_POST['description']) : '';
$contact_name = isset($_POST['contact_name']) ? trim($_POST['contact_name']) : '';
$contact_number = isset($_POST['contact_number']) ? trim($_POST['contact_number']) : '';
$contact_relation = isset($_POST['contact_relation']) ? trim($_POST['contact_relation']) : '';
$photo_data = isset($_POST['photo']) ? $_POST['photo'] : '';

$errors = [];
if (empty($missing_name)) $errors[] = 'Person name is required';
if (empty($last_seen_location)) $errors[] = 'Last seen location is required';
if (empty($last_seen_datetime)) $errors[] = 'Last seen date & time is required';

if (!empty($errors)) {
    echo json_encode([
        'status' => 'error',
        'message' => implode(', ', $errors)
    ]);
    exit;
}

$photo_path = null;
if (!empty($photo_data)) {
    // Handle base64 image
    $upload_dir = '../../uploads/missing_persons/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
    
    $image_parts = explode(";base64,", $photo_data);
    if (count($image_parts) == 2) {
        $image_type_aux = explode("image/", $image_parts[0]);
        $image_type = $image_type_aux[1] ?? 'jpg';
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($image_type, $allowed_types)) {
            $filename = 'missing_' . time() . '_' . uniqid() . '.' . $image_type;
            $file_path = $upload_dir . $filename;
            
            if (file_put_contents($file_path, base64_decode($image_parts[1]))) {
                $photo_path = 'uploads/missing_persons/' . $filename;
            }
        }
    }
}

if (!$photo_path) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Valid photo is required'
    ]);
    exit;
}

$status = 'pending';
$mysql_datetime = date('Y-m-d H:i:s', strtotime($last_seen_datetime));

$stmt = $conn->prepare("INSERT INTO missing_reports 
    (user_id, missing_name, contact_number, age, gender, last_seen_location, last_seen_datetime, description, photo, contact_name, contact_relation, status, created_at) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
$stmt->bind_param('isssssssssss', 
    $user_id, $missing_name, $contact_number, $age, $gender, 
    $last_seen_location, $mysql_datetime, $description, $photo_path, 
    $contact_name, $contact_relation, $status);

if ($stmt->execute()) {
    $report_id = $stmt->insert_id;
    echo json_encode([
        'status' => 'success',
        'message' => 'Report submitted successfully',
        'report_id' => $report_id
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to submit report: ' . $conn->error
    ]);
}

$stmt->close();
$conn->close();
?>

