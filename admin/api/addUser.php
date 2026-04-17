<?php
/**
 * Add New User
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
$password = isset($_POST['password']) ? $_POST['password'] : '';

$errors = [];
if (empty($fullname)) $errors[] = 'Full name is required';
if (empty($email)) $errors[] = 'Email is required';
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

$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$status = 'active';
$role = 'user';

$stmt = $conn->prepare("INSERT INTO users (fullname, email, password, role, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
$stmt->bind_param('sssss', $fullname, $email, $hashed_password, $role, $status);

if ($stmt->execute()) {
    echo json_encode([
        'status' => 'success',
        'message' => 'User created successfully'
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to create user: ' . $conn->error
    ]);
}

$stmt->close();
$conn->close();
?>

