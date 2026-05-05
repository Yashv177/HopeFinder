<?php
/**
 * Update User Profile API
 * Update the logged-in user's profile information
 * 
 * POST /php/api/update_profile.php
 * Request Body:
 *   - fullname (optional)
 *   - phone (optional)
 *   - mobile_number (optional)
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
    
    if (empty($input)) {
        sendJSONResponse([
            'success' => false,
            'message' => 'No data provided.'
        ], 400);
    }
    
    if (DatabaseHelpers::updateUserProfile($conn, $user_id, $input)) {
        // Log the activity
        DatabaseHelpers::logActivity($conn, $user_id, 'profile_updated');
        
        // Fetch updated profile
        $profile = DatabaseHelpers::getUserProfile($conn, $user_id);
        
        sendJSONResponse([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'data' => $profile
        ]);
    } else {
        sendJSONResponse([
            'success' => false,
            'message' => 'Failed to update profile.'
        ], 400);
    }
    
} catch (Exception $e) {
    sendJSONResponse([
        'success' => false,
        'message' => 'Unauthorized access.'
    ], 401);
}
?>
