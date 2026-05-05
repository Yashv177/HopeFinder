<?php
session_start(); // Admin/police can send without full auth for system notifications
require_once __DIR__ . '/../Database/Conn_db.php';

$user_id = (int)($_POST['user_id'] ?? 0);
$target = $_POST['target'] ?? 'all';
$message = trim($_POST['message'] ?? '');
$type = $_POST['type'] ?? null;
$link = $_POST['link'] ?? null;
$priority = $_POST['priority'] ?? 'low';

if (empty($message)) {
    http_response_code(400);
    echo json_encode(['error' => 'Message required']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO notifications (user_id, target, message, type, link, priority) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("isssss", $user_id, $target, $message, $type, $link, $priority);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'id' => $conn->insert_id]);
} else {
    echo json_encode(['error' => $stmt->error]);
}
$stmt->close();
?>

