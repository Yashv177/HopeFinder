<?php
/**
 * Add New Police Officer
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

$fullname = isset($_POST['fullname']) ? trim($_POST['fullname']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$badge_number = isset($_POST['badge_number']) ? trim($_POST['badge_number']) : '';
$station_name = isset($_POST['station_name']) ? trim($_POST['station_name']) : '';
$district = isset($_POST['district']) ? trim($_POST['district']) : '';
$state = isset($_POST['state']) ? trim($_POST['state']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

$errors = [];
if (empty($fullname)) $errors[] = 'Full name is required';
if (empty($email)) $errors[] = 'Email is required';
if (empty($badge_number)) $errors[] = 'Badge number is required';
if (empty($station_name)) $errors[] = 'Station name is required';
if (empty($district)) $errors[] = 'District is required';
if (empty($state)) $errors[] = 'State is required';
if (empty($password) || strlen($password) < 8) $errors[] = 'Password must be at least 8 characters';

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Invalid email format';
}

if (!empty($errors)) {
    echo json_encode([
        'status' => 'error',
        'message' => implode(', ', $errors)
    ]);
    exit;
}

// Check if email already exists
$stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
$stmt->bind_param('s', $email);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Email already registered'
    ]);
    exit;
}

// Check if badge number already exists
$stmt = $conn->prepare("SELECT police_id FROM police WHERE badge_number = ?");
$stmt->bind_param('s', $badge_number);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Badge number already registered'
    ]);
    exit;
}

$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$status = 'active';
$role = 'police';

// Insert into users table first
$stmt = $conn->prepare("INSERT INTO users (fullname, email, password, role, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
$stmt->bind_param('sssss', $fullname, $email, $hashed_password, $role, $status);

if (!$stmt->execute()) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to create user: ' . $conn->error
    ]);
    exit;
}

$user_id = $stmt->insert_id;
$stmt->close();

// Insert into police table with all fields
$stmt = $conn->prepare("INSERT INTO police (user_id, fullname, station_name, district, state, badge_number) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param('isssss', $user_id, $fullname, $station_name, $district, $state, $badge_number);

if ($stmt->execute()) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Police officer added successfully'
    ]);
} else {
    // Rollback - delete the user we just created
    $conn->query("DELETE FROM users WHERE user_id = $user_id");
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to add police officer: ' . $conn->error
    ]);
}

$stmt->close();
$conn->close();
?>

