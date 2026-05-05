<?php
/**
 * Get Notifications API
 * Fetch notifications for the logged-in user
 * 
 * GET /php/api/get_notifications.php
 * Query Parameters:
 *   - limit (optional): Number of notifications to fetch (default: 50)
 *   - unread_only (optional): Get only unread (default: false)
 */

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../Database/Conn_db.php';
require_once __DIR__ . '/auth_middleware.php';
require_once __DIR__ . '/database_helpers.php';

try {
    $payload = AuthMiddleware::checkAuthentication();
    $user_id = $payload['user_id'];
    $role = $payload['role'];
    
    // Determine target based on role
    $target = ($role === 'admin') ? 'admin' : (($role === 'police') ? 'police' : 'user');
    
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $unread_only = isset($_GET['unread_only']) && $_GET['unread_only'] === 'true';
    
    $notifications = DatabaseHelpers::getNotifications($conn, $user_id, $target, $limit, $unread_only);
    
    sendJSONResponse([
        'success' => true,
        'data' => $notifications,
        'count' => count($notifications)
    ]);
    
} catch (Exception $e) {
    sendJSONResponse([
        'success' => false,
        'message' => 'Unauthorized access.'
    ], 401);
}
?>
