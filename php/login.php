<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../Database/Conn_db.php';
require_once __DIR__ . '/jwt_helper.php';
require_once __DIR__ . '/auth_middleware.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit();
}

$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');
$role = trim($_POST['role'] ?? 'User');
$latitude = isset($_POST['latitude']) && $_POST['latitude'] !== '' ? (float)$_POST['latitude'] : null;
$longitude = isset($_POST['longitude']) && $_POST['longitude'] !== '' ? (float)$_POST['longitude'] : null;

if ($email === '' || $password === '') {
    echo json_encode([
        'success' => false,
        'message' => 'Email and password are required'
    ]);
    exit();
}

$dbRole = 'public';
if ($role === 'Police') {
    $dbRole = 'police';
} elseif ($role === 'Admin') {
    $dbRole = 'admin';
}

$stmt = $conn->prepare("SELECT user_id, email, password, fullname, role, login_attempts, locked_until FROM users WHERE email = ? AND role = ? LIMIT 1");
if (!$stmt) {
    echo json_encode([
        'success' => false,
        'message' => 'Database prepare failed: ' . $conn->error
    ]);
    exit();
}

$stmt->bind_param("ss", $email, $dbRole);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows !== 1) {
    $stmt->close();
    echo json_encode([
        'success' => false,
        'message' => 'No user found with this email and role'
    ]);
    exit();
}

$row = $result->fetch_assoc();
$stmt->close();

if (!empty($row['locked_until']) && strtotime($row['locked_until']) > time()) {
    echo json_encode([
        'success' => false,
        'message' => 'Account locked. Try again later.'
    ]);
    exit();
}

if (!password_verify($password, $row['password'])) {
    $attempts = ((int)($row['login_attempts'] ?? 0)) + 1;
    $lockedUntil = null;

    if ($attempts >= 5) {
        $lockedUntil = date('Y-m-d H:i:s', time() + 60);
    }

    $updateStmt = $conn->prepare("UPDATE users SET login_attempts = ?, locked_until = ? WHERE user_id = ?");
    if ($updateStmt) {
        $userId = (int)$row['user_id'];
        $updateStmt->bind_param("isi", $attempts, $lockedUntil, $userId);
        $updateStmt->execute();
        $updateStmt->close();
    }

    echo json_encode([
        'success' => false,
        'message' => $attempts >= 5 ? 'Account locked. Try again later.' : 'Invalid password'
    ]);
    exit();
}

$resetStmt = $conn->prepare("UPDATE users SET login_attempts = 0, locked_until = NULL WHERE user_id = ?");
if ($resetStmt) {
    $userId = (int)$row['user_id'];
    $resetStmt->bind_param("i", $userId);
    $resetStmt->execute();
    $resetStmt->close();
}

$payload = [
    'user_id' => (int)$row['user_id'],
    'email' => $row['email'],
    'fullname' => $row['fullname'],
    'role' => $dbRole,
    'iat' => time(),
    'exp' => time() + 3600
];

$jwtToken = JWT::encode($payload);
$expiresAt = date('Y-m-d H:i:s', time() + 3600);
$deviceInfo = AuthMiddleware::getDeviceInfo();
$deviceJson = json_encode($deviceInfo);

$deleteOld = $conn->prepare("DELETE FROM sessions WHERE user_id = ?");
if ($deleteOld) {
    $userId = (int)$row['user_id'];
    $deleteOld->bind_param("i", $userId);
    $deleteOld->execute();
    $deleteOld->close();
}

$sessionStmt = $conn->prepare("INSERT INTO sessions (user_id, session_token, expires_at, ip_address, user_agent, is_active) VALUES (?, ?, ?, ?, ?, 1)");
if (!$sessionStmt) {
    echo json_encode([
        'success' => false,
        'message' => 'Session insert prepare failed: ' . $conn->error
    ]);
    exit();
}

$userId = (int)$row['user_id'];
$ipAddress = $deviceInfo['ip_address'];
$userAgent = $deviceInfo['user_agent'];

$sessionStmt->bind_param("issss", $userId, $jwtToken, $expiresAt, $ipAddress, $userAgent);

if (!$sessionStmt->execute()) {
    $msg = $sessionStmt->error;
    $sessionStmt->close();
    echo json_encode([
        'success' => false,
        'message' => 'Session insert failed: ' . $msg
    ]);
    exit();
}
$sessionStmt->close();

$logStmt = $conn->prepare("INSERT INTO login_logs (user_id, role, ip_address, device_info, latitude, longitude, session_token) VALUES (?, ?, ?, ?, ?, ?, ?)");
if ($logStmt) {
    $roleVal = $dbRole;
    $latVal = $latitude;
    $lngVal = $longitude;
    $tokenVal = $jwtToken;
    $logStmt->bind_param("isssdds", $userId, $roleVal, $ipAddress, $deviceJson, $latVal, $lngVal, $tokenVal);
    $logStmt->execute();
    $logStmt->close();
}

$checkLastActivity = $conn->query("SHOW COLUMNS FROM users LIKE 'last_activity'");
$checkSessionToken = $conn->query("SHOW COLUMNS FROM users LIKE 'session_token'");

if ($checkLastActivity && $checkLastActivity->num_rows > 0 && $checkSessionToken && $checkSessionToken->num_rows > 0) {
    $updateUserStmt = $conn->prepare("UPDATE users SET last_activity = NOW(), session_token = ? WHERE user_id = ?");
    if ($updateUserStmt) {
        $updateUserStmt->bind_param("si", $jwtToken, $userId);
        $updateUserStmt->execute();
        $updateUserStmt->close();
    }
} elseif ($checkLastActivity && $checkLastActivity->num_rows > 0) {
    $updateUserStmt = $conn->prepare("UPDATE users SET last_activity = NOW() WHERE user_id = ?");
    if ($updateUserStmt) {
        $updateUserStmt->bind_param("i", $userId);
        $updateUserStmt->execute();
        $updateUserStmt->close();
    }
}

setcookie('auth_token', $jwtToken, time() + 3600, '/');

$_SESSION['user_id'] = $row['user_id'];
$_SESSION['fullname'] = $row['fullname'];
$_SESSION['email'] = $row['email'];
$_SESSION['role'] = $dbRole;
$_SESSION['authenticated'] = true;
$_SESSION['login_time'] = time();

$redirectUrl = 'php/UserD.php';
if ($dbRole === 'police') {
    $redirectUrl = 'php/PoliceD.php';
} elseif ($dbRole === 'admin') {
    $redirectUrl = 'php/Dashboard2.php';
}

echo json_encode([
    'success' => true,
    'redirect' => $redirectUrl,
    'user' => [
        'name' => $row['fullname'],
        'role' => ucfirst($dbRole)
    ]
]);
exit();