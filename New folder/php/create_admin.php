*9<?php
/**
 * Create Admin User Script
 * Run this once to create the admin user
 */

include __DIR__ . '/../Database/Conn_db.php';

// Admin user data
$fullname = 'Yash Kumar Varshney';
$email = 'yashgla@gmail.com';
$mobile_number = '7788554499';
$password = password_hash('Yash1234', PASSWORD_DEFAULT);
$role = 'admin';
$status = 'active';

// Check if admin already exists
$check = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND role = ?");
$check->bind_param("ss", $email, $role);
$check->execute();
$result = $check->get_result();

if ($result->num_rows > 0) {
    echo "Admin user already exists!<br>";
    $row = $result->fetch_assoc();
    echo "User ID: " . $row['user_id'];
} else {
    // Insert admin user
    $stmt = $conn->prepare("INSERT INTO users (fullname, email, mobile_number, password, role, status) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $fullname, $email, $mobile_number, $password, $role, $status);
    
    if ($stmt->execute()) {
        echo "Admin user created successfully!<br>";
        echo "User ID: " . $stmt->insert_id . "<br>";
        echo "Name: $fullname<br>";
        echo "Email: $email<br>";
        echo "Mobile: $mobile_number<br>";
        echo "Role: $role<br>";
        $stmt->close();
    } else {
        echo "Error creating admin user: " . $stmt->error;
    }
}

$check->close();
?>

