<?php
require_once __DIR__ . '/../../Database/Conn_db.php';

function sendNotification($user_id, $target, $message, $type="general", $link=null, $priority="low") {
    global $conn;

    $stmt = $conn->prepare("
        INSERT INTO notifications (user_id, target, message, type, link, priority) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param("isssss", $user_id, $target, $message, $type, $link, $priority);
    $stmt->execute();
}
?>