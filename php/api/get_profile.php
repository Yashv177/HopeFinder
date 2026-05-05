<?php
/**
 * Get User Profile API
 * Fetch the logged-in user's profile information
 * 
 * GET /php/api/get_profile.php
 */

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../Database/Conn_db.php';
require_once __DIR__ . '/auth_middleware.php';
require_once __DIR__ . '/database_helpers.php';

try {
    $payload = AuthMiddleware::checkAuthentication();
    $user_id = $payload['user_id'];
    
    $profile = DatabaseHelpers::getUserProfile($conn, $user_id);
    
    if ($profile) {
        sendJSONResponse([
            'success' => true,
            'data' => $profile
        ]);
    } else {
        sendJSONResponse([
            'success' => false,
            'message' => 'User profile not found.'
        ], 404);
    }
    
} catch (Exception $e) {
    sendJSONResponse([
        'success' => false,
        'message' => 'Unauthorized access.'
    ], 401);
}
?>
