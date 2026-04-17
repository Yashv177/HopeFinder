<?php
/**
 * User Signup API Endpoint
 * Handles POST requests for new user registration with validation
 */

// Include database connection
include '../Database/Conn_db.php';

// Handle only POST requests
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Only POST method allowed']);
    exit();
}

if (!isset($_POST['fullname']) || !isset($_POST['email']) || !isset($_POST['mobile_number']) || !isset($_POST['password']) ||
    empty(trim($_POST['fullname'])) || empty(trim($_POST['email'])) || empty(trim($_POST['mobile_number'])) || empty(trim($_POST['password']))) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit();
}

$fullname = trim($_POST['fullname']);
$email = trim($_POST['email']);
$mobile_number = trim($_POST['mobile_number']);
$password = trim($_POST['password']);

// Validation
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit();
}

if (!preg_match('/^[0-9]{10}$/', $mobile_number)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Mobile number must be exactly 10 digits']);
    exit();
}

if (!preg_match('/^[a-zA-Z\\s]{2,50}$/', $fullname)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Name must be 2-50 letters only']);
    exit();
}

if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || 
    !preg_match('/\\\\d/', $password) || !preg_match('/[!@#$%^&*]/', $password)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Password must have 8+ chars, 1 Upper, 1 Lower, 1 Number, 1 Special']);
    exit();
}

// Hash password and set defaults
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$role = 'public';
$status = 'active';

// Check if email exists
$check = $conn->prepare("SELECT id FROM users WHERE email = ?");
$check->bind_param("s", $email);
$check->execute();
$result = $check->get_result();
$check->close();

if ($result->num_rows > 0) {
    $result->free();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Email already registered!']);
    exit();
}
$result->free();

// Insert new user
$stmt = $conn->prepare("INSERT INTO users (fullname, email, mobile_number, password, role, status) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssss", $fullname, $email, $mobile_number, $hashed_password, $role, $status);

if ($stmt->execute()) {
    $stmt->close();
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'message' => 'Account created successfully!', 
        'redirect' => '../UserD.php'
    ]);
    exit();
} else {
    $stmt->close();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error during registration']);
    exit();
}
?>

