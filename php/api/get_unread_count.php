<?php
/**
 * Get Unread Notification Count API
 * Get count of unread notifications
 * 
 * GET /php/api/get_unread_count.php
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
    
    $target = ($role === 'admin') ? 'admin' : (($role === 'police') ? 'police' : 'user');
    
    $count = DatabaseHelpers::getUnreadCount($conn, $user_id, $target);
    
    sendJSONResponse([
        'success' => true,
        'unread_count' => $count
    ]);
    
} catch (Exception $e) {
    sendJSONResponse([
        'success' => false,
        'message' => 'Unauthorized access.'
    ], 401);
}
?>
