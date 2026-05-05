<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

$input_data = json_decode(file_get_contents('php://input'), true);

// ✅ Correct Flask endpoint
$url = 'http://127.0.0.1:5001/stop_detection';

// cURL
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, '{}');
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);

$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

// Response
if ($httpcode === 200) {
    echo json_encode([
        'status' => 'success',
        'message' => 'AI stopped successfully',
        'response' => json_decode($response, true)
    ]);
} else {
    http_response_code(502);
    echo json_encode([
        'status' => 'error',
        'message' => 'AI server not responding',
        'code' => $httpcode,
        'error' => $curl_error
    ]);
}
?>