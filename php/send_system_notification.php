<?php
/**
 * Send System Notifications
 * Usage: php send_system_notification.php "New feature!" police high "dashboard.php"
 */

if ($argc < 2) {
    echo "Usage: php send_system_notification.php <message> [target=all] [priority=low] [link=]\n";
    exit(1);
}

require_once __DIR__ . '/../Database/Conn_db.php';
require_once __DIR__ . '/auth_middleware.php';

$message = $argv[1];
$target = $argv[2] ?? 'all';
$priority = $argv[3] ?? 'low';
$link = $argv[4] ?? null;

$stmt = $conn->prepare("INSERT INTO notifications (target, message, priority, link, type) VALUES (?, ?, ?, ?, 'system')");
$stmt->bind_param("ssss", $target, $message, $priority, $link);

if ($stmt->execute()) {
    echo "✅ Notification sent to $target (priority: $priority)\n";
} else {
    echo "❌ Error: " . $stmt->error . "\n";
}
?>

