<?php
require_once '../config/db.php';
require_once '../../../api/helpers/notification_helper.php';


// Read JSON input
$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["error" => "Invalid JSON"]);
    exit;
}

// Extract data
$report_id  = $data['report_id'] ?? null;
$image_path = $data['image_path'] ?? '';
$confidence = $data['confidence'] ?? 0;
$timestamp  = $data['timestamp'] ?? date("Y-m-d H:i:s");

$lat = $data['latitude'] ?? null;
$lng = $data['longitude'] ?? null;

// Default address
$address = "Unknown";

// Reverse Geocoding
if ($lat !== null && $lng !== null) {

    $url = "https://nominatim.openstreetmap.org/reverse?format=json&lat=$lat&lon=$lng";

    $options = [
        "http" => [
            "header" => "User-Agent: HopeFinderApp/1.0\r\n"
        ]
    ];

    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);

    if ($response !== FALSE) {
        $geoData = json_decode($response, true);

        if (isset($geoData['address'])) {
            $addr = $geoData['address'];

            $city = $addr['city'] ?? $addr['town'] ?? $addr['village'] ?? '';
            $state = $addr['state'] ?? '';
            $country = $addr['country'] ?? '';

            $address = trim("$city, $state, $country", ", ");
        }
    }
}

// ==============================
// 1. SAVE DETECTION
// ==============================
$stmt = $conn->prepare("
    INSERT INTO detections 
    (report_id, image_path, confidence, latitude, longitude, address, timestamp)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "isdssss",
    $report_id,
    $image_path,
    $confidence,
    $lat,
    $lng,
    $address,
    $timestamp
);

$stmt->execute();

// ==============================
// 2. CHECK IF ALREADY FOUND
// ==============================

// 🔔 SEND NOTIFICATIONS
$q = $conn->prepare("SELECT user_id FROM missing_reports WHERE report_id = ?");
$q->bind_param("i", $report_id);
$q->execute();
$res = $q->get_result();
$user = $res->fetch_assoc();

if ($user) {
    $user_id = $user['user_id'];
    sendNotification($user_id, "user", "Good news! We may have found your missing person.");
}

// admin
sendNotification(null, "admin", "AI detected a match in system");

// ==============================
// STATUS UPDATE
// ==============================

$update = $conn->prepare("
    UPDATE missing_reports 
    SET status = 'found'
    WHERE report_id = ?
");

$update->bind_param("i", $report_id);
$update->execute();

// ==============================
// RESPONSE
// ==============================
echo json_encode([
    "status" => "success",
    "address" => $address
]);
?>