<?php
header('Content-Type: application/json');
include '../Database/Conn_db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $role = trim($_POST['role'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($role) || empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit();
    }

    // Email validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        exit();
    }

    // Check user
    $check = $conn->prepare("SELECT * FROM users WHERE email = ? AND role = ? AND status = 'active'");
    $check->bind_param("ss", $email, $role);
    $check->execute();
    $result = $check->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
echo json_encode([
            'success' => true, 
            'message' => 'Login successful!',
            'redirect' => $role === 'User' ? '../php/UserD.php' : ($role === 'Police' ? '../php/PoliceD.php' : '../php/AdminD.php')
        ]); 
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid credentials or account inactive']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>

