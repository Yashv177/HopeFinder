<?php
/**
 * Get AI Matches API
 * Fetch AI match data for the dashboard
 * 
 * GET /php/api/get_ai_matches.php
 * Query Parameters:
 *   - report_id (optional): Filter by specific report ID
 *   - limit (optional): Number of matches to fetch (default: 100)
 */

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../Database/Conn_db.php';
require_once __DIR__ . '/auth_middleware.php';
require_once __DIR__ . '/database_helpers.php';

try {
    $payload = AuthMiddleware::checkAuthentication();
    $role = $payload['role'];
    
    // Only admin and police can view AI matches
    if ($role !== 'admin' && $role !== 'police') {
        sendJSONResponse([
            'success' => false,
            'message' => 'You do not have permission to view AI matches.'
        ], 403);
    }
    
    $report_id = isset($_GET['report_id']) ? (int)$_GET['report_id'] : null;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
    
    $matches = DatabaseHelpers::getAIMatches($conn, $report_id, $limit);
    
    sendJSONResponse([
        'success' => true,
        'data' => $matches,
        'count' => count($matches)
    ]);
    
} catch (Exception $e) {
    sendJSONResponse([
        'success' => false,
        'message' => 'Unauthorized access.'
    ], 401);
}
?>
