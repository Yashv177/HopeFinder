<?php
include '../Database/Conn_db.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
if (json_last_error() !== JSON_ERROR_NONE || !$input) {
    $input = $_POST;
}

$required_fields = ['fullname', 'email', 'mobile_number', 'password'];
foreach ($required_fields as $field) {
    if (empty(trim($input[$field] ?? ''))) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit();
    }
}

$fullname = trim($input['fullname']);
$email = trim($input['email']);
$mobile_number = trim($input['mobile_number']);
$password = trim($input['password']);

// Email validation
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit();
}

// Phone validation - exactly 10 digits
if (!preg_match('/^[0-9]{10}$/', $mobile_number)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Mobile number must be exactly 10 digits']);
    exit();
}

// Name validation - letters and spaces only, 2-50 chars
if (!preg_match('/^[a-zA-Z\s]{2,50}$/', $fullname)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Name must be 2-50 letters/spaces only']);
    exit();
}

// Password validation: 8+ chars, upper, lower, digit, special
if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || 
    !preg_match('/\d/', $password) || !preg_match('/[!@#$%^&*]/', $password)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Password: 8+ chars, 1 Upper, 1 Lower, 1 Number, 1 Special']);
    exit();
}

// Hash password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Defaults
$role = 'public';
$status = 'active';

// Check duplicate email
$check = $conn->prepare("SELECT id FROM users WHERE email = ?");
if (!$check) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database prepare error']);
    exit();
}
$check->bind_param("s", $email);
$check->execute();
$result = $check->get_result();
$check->close();

if ($result->num_rows > 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Email already registered!']);
    $result->free();
    exit();
}

// Insert user
$stmt = $conn->prepare("INSERT INTO users (fullname, email, mobile_number, password, role, status) VALUES (?, ?, ?, ?, ?, ?)");
if (!$stmt) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database prepare error']);
    exit();
}
$stmt->bind_param("ssssss", $fullname, $email, $mobile_number, $hashed_password, $role, $status);

if ($stmt->execute()) {
    $stmt->close();
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'message' => 'Account created successfully!', 
        'redirect' => '../UserD.php'
    ]);
} else {
    $stmt->close();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Registration failed. Try again.']);
}
?>

