<?php
header('Content-Type: application/json');
include_once '../../Database/Conn_db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$report_ids = $input['report_ids'] ?? [];
$session_id = uniqid('ai_');

if (empty($report_ids)) {
    echo json_encode(['error' => 'No report IDs provided']);
    exit;
}

// Log session
file_put_contents('ai_sessions.json', json_encode([
    'session_id' => $session_id,
    'report_ids' => $report_ids,
    'started_at' => date('Y-m-d H:i:s')
]) . "\n", FILE_APPEND | LOCK_EX);

// Proxy to Flask AI server
$flask_url = 'http://localhost:5001/start_ai';
$flask_data = json_encode(['report_ids' => $report_ids, 'session_id' => $session_id]);

$ch = curl_init($flask_url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $flask_data);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);

$flask_response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code >= 200 && $http_code < 300) {
    $flask_data_decoded = json_decode($flask_response, true);
    echo json_encode([
        'status' => 'started',
        'session_id' => $session_id,
        'flask_status' => $flask_data_decoded['status'] ?? 'ok',
        'targets_loaded' => $flask_data_decoded['targets_loaded'] ?? 0
    ]);
} else {
    echo json_encode([
        'status' => 'started_php',
        'session_id' => $session_id,
        'flask_error' => 'Flask proxy failed. Start: python python-ai/cctv_server_fixed.py',
        'http_code' => $http_code
    ]);
}
?>
