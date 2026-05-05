<?php
/**
 * Police Dashboard Stats API
 * Returns stats filtered by logged-in police officer
 * Filtering: report_assignments.police_id = police_id  OR  status = 'pending'
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../Database/Conn_db.php';
require_once __DIR__ . '/auth_middleware.php';

$payload = AuthMiddleware::checkRole('police');
$user_id = (int)$_SESSION['user_id'];

try {
    // Get police_id for the logged-in officer
    $stmt = $conn->prepare("SELECT police_id FROM police WHERE user_id = ? LIMIT 1");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $officer = $result->fetch_assoc();
    $stmt->close();

    if (!$officer) {
        echo json_encode(['success' => false, 'error' => 'Police officer record not found']);
        exit;
    }

    $police_id = (int)$officer['police_id'];

    // Build filter: assigned to this officer OR pending
    $filterSql = "
        mr.report_id IN (
            SELECT mr2.report_id FROM missing_reports mr2
            LEFT JOIN report_assignments ra ON mr2.report_id = ra.report_id AND ra.is_active = 1
            WHERE ra.police_id = ? OR mr2.status = 'pending'
        )
    ";
    $filterParam = $police_id;
    $filterType = 'i';

    // --- CARD STATS ---

    // total_reports
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM missing_reports mr WHERE $filterSql");
    $stmt->bind_param($filterType, $filterParam);
    $stmt->execute();
    $total_reports = (int)$stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    // pending_cases
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM missing_reports mr WHERE mr.status = 'pending' AND $filterSql");
    $stmt->bind_param($filterType, $filterParam);
    $stmt->execute();
    $pending_cases = (int)$stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    // verified_cases
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM missing_reports mr WHERE mr.status = 'verified' AND $filterSql");
    $stmt->bind_param($filterType, $filterParam);
    $stmt->execute();
    $verified_cases = (int)$stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    // found_cases
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM missing_reports mr WHERE mr.status = 'found' AND $filterSql");
    $stmt->bind_param($filterType, $filterParam);
    $stmt->execute();
    $found_cases = (int)$stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    // --- TREND DATA (last 7 days) ---
    $trend_data = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $label = date('M j', strtotime($date));

        $stmt = $conn->prepare("
            SELECT COUNT(*) as count FROM missing_reports mr
            WHERE DATE(mr.created_at) = ? AND $filterSql
        ");
        $stmt->bind_param('s' . $filterType, $date, $filterParam);
        $stmt->execute();
        $count = (int)$stmt->get_result()->fetch_assoc()['count'];
        $stmt->close();

        $trend_data[] = ['date' => $label, 'count' => $count];
    }

    // --- STATUS DATA (for pie chart) ---
    $status_data = ['pending' => 0, 'verified' => 0, 'found' => 0];

    $stmt = $conn->prepare("SELECT mr.status, COUNT(*) as count FROM missing_reports mr WHERE mr.status IN ('pending','verified','found') AND $filterSql GROUP BY mr.status");
    $stmt->bind_param($filterType, $filterParam);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        if (isset($status_data[$row['status']])) {
            $status_data[$row['status']] = (int)$row['count'];
        }
    }
    $stmt->close();

    // --- COMPARISON: Found vs Missing ---
    $comparison = ['found' => 0, 'missing' => 0];

    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM missing_reports mr WHERE mr.status = 'found' AND $filterSql");
    $stmt->bind_param($filterType, $filterParam);
    $stmt->execute();
    $comparison['found'] = (int)$stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM missing_reports mr WHERE mr.status != 'found' AND $filterSql");
    $stmt->bind_param($filterType, $filterParam);
    $stmt->execute();
    $comparison['missing'] = (int)$stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    // --- AI MATCHES: found cases with detections ---
    $ai_matches = [];
    $stmt = $conn->prepare("
        SELECT 
            mr.report_id,
            mr.missing_name,
            mr.last_seen_location,
            d.address,
            d.timestamp,
            d.confidence
        FROM missing_reports mr
        LEFT JOIN detections d ON mr.report_id = d.report_id
        WHERE mr.status = 'found' AND $filterSql
        ORDER BY d.timestamp DESC
        LIMIT 20
    ");
    $stmt->bind_param($filterType, $filterParam);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $ai_matches[] = [
            'report_id' => $row['report_id'],
            'missing_name' => $row['missing_name'],
            'address' => $row['address'] ?? $row['last_seen_location'],
            'timestamp' => $row['timestamp'],
            'confidence' => $row['confidence']
        ];
    }
    $stmt->close();

    echo json_encode([
        'success' => true,
        'stats' => [
            'total_reports' => $total_reports,
            'pending_cases' => $pending_cases,
            'verified_cases' => $verified_cases,
            'found_cases' => $found_cases
        ],
        'trend_data' => $trend_data,
        'status_data' => $status_data,
        'comparison' => $comparison,
        'ai_matches' => $ai_matches
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$conn->close();
