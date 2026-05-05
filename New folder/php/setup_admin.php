<?php
require_once __DIR__ . '/../Database/Conn_db.php';

$fullname = htmlspecialchars($_GET['name'] ?? 'Super Admin');
$email = htmlspecialchars($_GET['email'] ?? 'admin@hopefinder.com');
$mobile = htmlspecialchars($_GET['mobile'] ?? '9876543210');
$password = password_hash('123456789', PASSWORD_DEFAULT);
$role = 'admin';

$stmt = $conn->prepare("INSERT IGNORE INTO users (fullname, email, mobile_number, password, role, status) VALUES (?, ?, ?, ?, ?, 'active')");
$stmt->bind_param("sssss", $fullname, $email, $mobile, $password, $role);

if ($stmt->execute()) {
    echo "✅ Admin created/verified successfully!<br>";
    echo "Email: $email<br>";
    echo "Password: 123456789<br>";
    echo "Login at: Login.html → Admin role";
} else {
    echo "⚠️ Admin already exists or error: " . $stmt->error;
}
$stmt->close();
?>
**Run once**: php/php/setup_admin.php
