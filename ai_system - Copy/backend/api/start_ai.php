<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

$input_data = json_decode(file_get_contents('php://input'), true);

// 🔧 DEBUG & FIX: Remap report_ids → selected_ids for Flask
$debug_input = $input_data;
if (isset($input_data['report_ids'])) {
    $input_data['selected_ids'] = $input_data['report_ids'];
    error_log("🔍 PHP DEBUG start_ai.php - Remapped report_ids=" . json_encode($input_data['report_ids']) . " → selected_ids");
    unset($input_data['report_ids']);  // Clean up
} else {
    error_log("🔍 PHP DEBUG start_ai.php - No report_ids found in: " . json_encode($debug_input));
}
$selected_ids = $input_data['selected_ids'] ?? [];

$url = 'http://127.0.0.1:5001/start_detection';

error_log("PHP DEBUG - Sending to Flask: " . json_encode($input_data));

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($input_data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

if ($response !== false) {
    echo $response;
} else {
    echo json_encode([
        'status' => 'error',
        'message' => curl_error($ch)
    ]);
}
?>
