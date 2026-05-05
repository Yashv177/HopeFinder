<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../Database/Conn_db.php';

try {
    // Reports per day for last 7 days
    $reports_per_day = ['labels' => [], 'data' => []];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM missing_reports WHERE DATE(created_at) = ?");
        $stmt->bind_param("s", $date);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $count = $row['count'] ?? 0;
        $reports_per_day['labels'][] = date('M j', strtotime($date));
        $reports_per_day['data'][] = (int)$count;
    }

    // Status distribution
    $status_counts = ['labels' => [], 'data' => []];
    $statuses = ['pending', 'verified', 'assigned', 'in_progress', 'resolved', 'closed'];
    foreach ($statuses as $status) {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM missing_reports WHERE status = ?");
        $stmt->bind_param("s", $status);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $count = $row['count'] ?? 0;
        $status_counts['labels'][] = ucfirst(str_replace('_', ' ', $status));
        $status_counts['data'][] = (int)$count;
    }

    // Found vs Missing
    $stmt = $conn->prepare("SELECT COUNT(*) as missing FROM missing_reports");
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $missing = $row['missing'] ?? 0;
    
    // Check if found_persons table exists
    $stmt = $conn->prepare("SHOW TABLES LIKE 'found_persons'");
    $stmt->execute();
    $found_exists = $stmt->get_result()->num_rows > 0;
    
    if ($found_exists) {
        $stmt = $conn->prepare("SELECT COUNT(*) as found FROM found_persons");
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $found = $row['found'] ?? 0;
    } else {
        $found = 0;
    }

    echo json_encode([
        'success' => true,
        'charts' => [
            'reports_per_day' => $reports_per_day,
            'status_distribution' => $status_counts,
            'found_vs_missing' => [
                'missing' => (int)$missing,
                'found' => (int)$found
            ]
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

