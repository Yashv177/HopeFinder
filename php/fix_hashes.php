<?php
include '../Database/Conn_db.php';

// Known plain passwords from DB
$known_plains = [
    'Yash#123' => password_hash('Yash#123', PASSWORD_DEFAULT),
    'yash123' => password_hash('yash123', PASSWORD_DEFAULT)
];

// Fix known plain (if any left) and MySQL hashes (start with *)
$count = 0;
foreach ($known_plains as $old_plain => $new_hash) {
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE password = ? OR password LIKE ?");
    $prefix = '*13DE90300B%'; // MySQL hash prefix for varshney etc
    $stmt->bind_param("sss", $new_hash, $old_plain, $prefix);
    if ($stmt->execute()) {
        $count += $stmt->affected_rows;
    }
}

$stmt = $conn->prepare("UPDATE users SET role = 'public' WHERE role IS NULL OR role = ''");
$stmt->execute();

echo "Fixed $count users. Check: SELECT email, LEFT(password,10) FROM users LIMIT 5;\n";
?>

