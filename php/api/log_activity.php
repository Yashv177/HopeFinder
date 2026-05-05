<?php
/**
 * Log Activity API
 * Log user actions for activity tracking
 * 
 * POST /php/api/log_activity.php
 * Request Body:
 *   - action (required): Action name
 *   - details (optional): Additional details
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
    
    if (!isset($input['action'])) {
        sendJSONResponse([
            'success' => false,
            'message' => 'Action is required.'
        ], 400);
    }
    
    $action = $input['action'];
    $details = isset($input['details']) ? $input['details'] : null;
    
    if (DatabaseHelpers::logActivity($conn, $user_id, $action, $details)) {
        sendJSONResponse([
            'success' => true,
            'message' => 'Activity logged successfully.'
        ]);
    } else {
        sendJSONResponse([
            'success' => false,
            'message' => 'Failed to log activity.'
        ], 400);
    }
    
} catch (Exception $e) {
    sendJSONResponse([
        'success' => false,
        'message' => 'Unauthorized access.'
    ], 401);
}
?>
