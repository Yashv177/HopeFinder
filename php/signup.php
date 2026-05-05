<?php
include '../Database/Conn_db.php';

header('Content-Type: application/json');

// Allow only POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(['success' => false, 'message' => 'Only POST method allowed']);
    exit();
}

// Validate inputs
if (
    empty($_POST['fullname']) ||
    empty($_POST['email']) ||
    empty($_POST['mobile_number']) ||
    empty($_POST['password'])
) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit();
}

// Sanitize inputs
$fullname = trim($_POST['fullname']);
$email = trim($_POST['email']);
$mobile_number = trim($_POST['mobile_number']);
$password = trim($_POST['password']);

// Email validation
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit();
}

// Mobile validation
if (!preg_match('/^[0-9]{10}$/', $mobile_number)) {
    echo json_encode(['success' => false, 'message' => 'Mobile must be 10 digits']);
    exit();
}

// Name validation
if (!preg_match('/^[a-zA-Z\s]{2,50}$/', $fullname)) {
    echo json_encode(['success' => false, 'message' => 'Name must be 2-50 letters']);
    exit();
}

// 🔥 FIXED PASSWORD REGEX
if (
    strlen($password) < 8 ||
    !preg_match('/[A-Z]/', $password) ||
    !preg_match('/[a-z]/', $password) ||
    !preg_match('/\d/', $password) ||   // ✅ FIXED HERE
    !preg_match('/[!@#$%^&*]/', $password)
) {
    echo json_encode([
        'success' => false,
        'message' => 'Password must contain uppercase, lowercase, number & special character'
    ]);
    exit();
}

// Hash password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$role = 'public';
$status = 'active';


// Insert user
$stmt = $conn->prepare("INSERT INTO users (fullname, email, mobile_number, password, role, status) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssss", $fullname, $email, $mobile_number, $hashed_password, $role, $status);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Account created successfully',
        'redirect' => 'UserD.php'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $stmt->error
    ]);
}

$stmt->close();
$conn->close();
?>