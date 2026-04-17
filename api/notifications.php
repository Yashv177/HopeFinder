<?php
session_start();
require_once __DIR__ . '/../Database/Conn_db.php';
require_once __DIR__ . '/../php/auth_middleware.php';

header('Content-Type: application/json');

// AuthMiddleware::checkAuthentication(); // Disabled for demo - enable for production

$user_id = $_SESSION['user_id'] ?? 0;
$role = $_SESSION['role'] ?? '';
$action = $_POST['action'] ?? 'list';

function getNotifications($conn, $user_id, $role) {
    $notifications = [];
    
    $sql = "SELECT * FROM notifications WHERE 
            (user_id = ? OR target = 'all' OR (target = ? AND ? IN ('admin','police','user'))) 
            AND status = 'unread' 
            ORDER BY created_at DESC LIMIT 10";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $user_id, $role, $role);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $row['priority_color'] = $row['priority'] == 'high' ? 'danger' : ($row['priority'] == 'medium' ? 'warning' : 'success');
        $notifications[] = $row;
    }
    $stmt->close();
    return $notifications;
}

function getUnreadCount($conn, $user_id, $role) {
    $sql = "SELECT COUNT(*) as count FROM notifications WHERE 
            (user_id = ? OR target = 'all' OR (target = ? AND ? IN ('admin','police','user'))) 
            AND status = 'unread'";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $user_id, $role, $role);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int)$result['count'];
}

function markRead($conn, $notif_id) {
    $stmt = $conn->prepare("UPDATE notifications SET status = 'read' WHERE notif_id = ?");
    $stmt->bind_param("i", $notif_id);
    return $stmt->execute();
}

switch ($action) {
    case 'list':
        echo json_encode([
            'notifications' => getNotifications($conn, $user_id, $role),
            'unread_count' => getUnreadCount($conn, $user_id, $role)
        ]);
        break;
    
    case 'mark_read':
        $notif_id = (int)($_POST['notif_id'] ?? 0);
        if (markRead($conn, $notif_id)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false]);
        }
        break;
        
    case 'mark_all_read':
        $sql = "UPDATE notifications SET status = 'read' WHERE 
                (user_id = ? OR target = 'all' OR (target = ? AND ? IN ('admin','police','user'))) 
                AND status = 'unread'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iss", $user_id, $role, $role);
        echo json_encode(['success' => $stmt->execute()]);
        break;
        
    default:
        echo json_encode(['error' => 'Invalid action']);
}
?>

