<?php
/**
 * Get Activity Logs API
 * Fetch activity logs for the logged-in user or admin view
 * 
 * GET /php/api/get_activity_logs.php
 * Query Parameters:
 *   - limit (optional): Number of logs to fetch (default: 100)
 *   - user_id (optional): Filter by user ID (admin only)
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
    
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
    
    // Check if admin can view other users' logs
    $filter_user_id = $user_id;
    if (isset($_GET['user_id']) && $role === 'admin') {
        $filter_user_id = (int)$_GET['user_id'];
    }
    
    $logs = DatabaseHelpers::getActivityLogs($conn, $filter_user_id, $limit);
    
    sendJSONResponse([
        'success' => true,
        'data' => $logs,
        'count' => count($logs)
    ]);
    
} catch (Exception $e) {
    sendJSONResponse([
        'success' => false,
        'message' => 'Unauthorized access.'
    ], 401);
}
?>
