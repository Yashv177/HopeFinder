<?php
/**
 * Mark Notification as Read API
 * Mark one or all notifications as read
 * 
 * POST /php/api/mark_notification_read.php
 * Request Body:
 *   - notif_id (optional): Specific notification ID
 *   - mark_all (optional): Mark all as read (true/false)
 */

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../Database/Conn_db.php';
require_once __DIR__ . '/auth_middleware.php';
require_once __DIR__ . '/database_helpers.php';

try {
    $payload = AuthMiddleware::checkAuthentication();
    $user_id = $payload['user_id'];
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (isset($input['mark_all']) && $input['mark_all'] === true) {
        // Mark all user's notifications as read
        $stmt = $conn->prepare("
            UPDATE notifications 
            SET status = 'read', updated_at = NOW() 
            WHERE user_id = ? AND status = 'unread'
        ");
        $stmt->bind_param("i", $user_id);
        
        if ($stmt->execute()) {
            $affected = $conn->affected_rows;
            $stmt->close();
            
            sendJSONResponse([
                'success' => true,
                'message' => 'All notifications marked as read.',
                'affected' => $affected
            ]);
        } else {
            $stmt->close();
            sendJSONResponse([
                'success' => false,
                'message' => 'Failed to mark notifications as read.'
            ], 400);
        }
    } elseif (isset($input['notif_id'])) {
        // Mark specific notification as read
        $notif_id = (int)$input['notif_id'];
        
        if (DatabaseHelpers::markNotificationAsRead($conn, $notif_id)) {
            sendJSONResponse([
                'success' => true,
                'message' => 'Notification marked as read.'
            ]);
        } else {
            sendJSONResponse([
                'success' => false,
                'message' => 'Failed to mark notification as read.'
            ], 400);
        }
    } else {
        sendJSONResponse([
            'success' => false,
            'message' => 'Invalid request parameters.'
        ], 400);
    }
    
} catch (Exception $e) {
    sendJSONResponse([
        'success' => false,
        'message' => 'Unauthorized access.'
    ], 401);
}
?>
