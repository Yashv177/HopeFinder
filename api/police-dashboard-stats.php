<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../Database/Conn_db.php';

try {
    // Total reports
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM missing_reports");
    $stmt->execute();
    $total_reports = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

    // Pending verification (status = 'pending')
    $stmt = $conn->prepare("SELECT COUNT(*) as pending FROM missing_reports WHERE status = 'pending'");
    $stmt->execute();
    $pending = $stmt->get_result()->fetch_assoc()['pending'] ?? 0;

    // Verified cases (status = 'verified')
    $stmt = $conn->prepare("SELECT COUNT(*) as verified FROM missing_reports WHERE status = 'verified'");
    $stmt->execute();
    $verified = $stmt->get_result()->fetch_assoc()['verified'] ?? 0;

    // Resolved cases (status = 'resolved' or 'closed')
    $stmt = $conn->prepare("SELECT COUNT(*) as resolved FROM missing_reports WHERE status IN ('resolved', 'closed')");
    $stmt->execute();
    $resolved = $stmt->get_result()->fetch_assoc()['resolved'] ?? 0;

    // Active investigations (status = 'assigned' or 'in_progress')
    $stmt = $conn->prepare("SELECT COUNT(*) as active FROM missing_reports WHERE status IN ('assigned', 'in_progress')");
    $stmt->execute();
    $active = $stmt->get_result()->fetch_assoc()['active'] ?? 0;

    echo json_encode([
        'success' => true,
        'stats' => [
            'total_reports' => (int)$total_reports,
            'pending_verification' => (int)$pending,
            'verified_cases' => (int)$verified,
            'resolved_cases' => (int)$resolved,
            'active_investigations' => (int)$active
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>

