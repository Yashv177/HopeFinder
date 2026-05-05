<?php
/**
 * Change Password API
 * Change the logged-in user's password securely
 * 
 * POST /php/api/change_password.php
 * Request Body:
 *   - old_password (required)
 *   - new_password (required)
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
    
    if (!isset($input['old_password']) || !isset($input['new_password'])) {
        sendJSONResponse([
            'success' => false,
            'message' => 'Old password and new password are required.'
        ], 400);
    }
    
    $old_password = $input['old_password'];
    $new_password = $input['new_password'];
    
    $result = DatabaseHelpers::changePassword($conn, $user_id, $old_password, $new_password);
    
    if ($result['success']) {
        sendJSONResponse([
            'success' => true,
            'message' => $result['message']
        ]);
    } else {
        sendJSONResponse([
            'success' => false,
            'message' => $result['message']
        ], 400);
    }
    
} catch (Exception $e) {
    sendJSONResponse([
        'success' => false,
        'message' => 'Unauthorized access.'
    ], 401);
}
?>
