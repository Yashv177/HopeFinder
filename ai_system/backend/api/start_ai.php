<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 🔥 Handle preflight (CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// 🔥 Read input JSON
$input_data = json_decode(file_get_contents('php://input'), true);

// 🔥 Validate input
if (!$input_data) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid JSON input'
    ]);
    exit;
}

// 🔥 Convert report_ids → selected_ids
if (isset($input_data['report_ids'])) {
    $input_data['selected_ids'] = $input_data['report_ids'];
}

// 🔥 Ensure selected_ids exists
if (!isset($input_data['selected_ids']) || !is_array($input_data['selected_ids'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'No selected_ids provided'
    ]);
    exit;
}

// 🔥 GPS fallback
$input_data['latitude'] = $input_data['latitude'] ?? 0.0;
$input_data['longitude'] = $input_data['longitude'] ?? 0.0;

// 🔥 Debug log
error_log("🚀 START_AI REQUEST: " . json_encode($input_data));

// 🔥 Flask API URL
$url = 'http://127.0.0.1:5001/start_detection';

// 🔥 cURL setup
$ch = curl_init($url);

curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($input_data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

// 🔥 Execute request
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// 🔥 Error handling
if ($response === false) {
    $error = curl_error($ch);
    curl_close($ch);

    echo json_encode([
        'status' => 'error',
        'message' => 'Flask connection failed',
        'error' => $error
    ]);
    exit;
}

curl_close($ch);

// 🔥 Validate Flask response
$decoded = json_decode($response, true);

if ($http_code !== 200 || !$decoded) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid response from AI server',
        'response' => $response
    ]);
    exit;
}

// ✅ SUCCESS
echo json_encode([
    'status' => 'success',
    'message' => 'AI started successfully',
    'data' => $decoded
]);
?>