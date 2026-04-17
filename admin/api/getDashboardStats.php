<?php
/**
 * Get Dashboard Statistics
 * Used by PoliceD.php and Dashboard2.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../Database/Conn_db.php';

try {
    // Total users (role = 'public')
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'public'");
    $stmt->execute();
    $users = $stmt->get_result()->fetch_assoc()['count'] ?? 0;
    $stmt->close();

    // Total police
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'police'");
    $stmt->execute();
    $police = $stmt->get_result()->fetch_assoc()['count'] ?? 0;
    $stmt->close();

    // Total reports
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM missing_reports");
    $stmt->execute();
    $reports = $stmt->get_result()->fetch_assoc()['count'] ?? 0;
    $stmt->close();

    // Matched/found cases (status = 'closed')
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM missing_reports WHERE status = 'closed'");
    $stmt->execute();
    $matches = $stmt->get_result()->fetch_assoc()['count'] ?? 0;
    $stmt->close();

    // Pending verification
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM missing_reports WHERE status = 'pending'");
    $stmt->execute();
    $pending = $stmt->get_result()->fetch_assoc()['count'] ?? 0;
    $stmt->close();

    echo json_encode([
        'status' => 'success',
        'users' => (int)$users,
        'police' => (int)$police,
        'reports' => (int)$reports,
        'matches' => (int)$matches,
        'pending' => (int)$pending
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>

