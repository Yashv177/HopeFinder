public static function checkAuthentication() {
    global $conn;

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_COOKIE['auth_token'])) {
        self::redirectToLogin();
    }

    $token = $_COOKIE['auth_token'];
    $payload = JWT::decode($token);

    if (!$payload || !isset($payload['user_id'])) {
        self::logout();
    }

    $stmt = $conn->prepare("SELECT * FROM sessions WHERE session_token = ? AND is_active = 1 AND expires_at > NOW() LIMIT 1");
    if (!$stmt) {
        die("Session query prepare failed: " . $conn->error);
    }

    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if (!$result || $result->num_rows === 0) {
        self::logout();
    }

    $_SESSION['user_id'] = $payload['user_id'];
    $_SESSION['email'] = $payload['email'] ?? '';
    $_SESSION['fullname'] = $payload['fullname'] ?? ($_SESSION['fullname'] ?? '');
    $_SESSION['role'] = $payload['role'];
    $_SESSION['authenticated'] = true;

    $checkColumn = $conn->query("SHOW COLUMNS FROM users LIKE 'last_activity'");
    if ($checkColumn && $checkColumn->num_rows > 0) {
        $updateStmt = $conn->prepare("UPDATE users SET last_activity = NOW() WHERE user_id = ?");
        if ($updateStmt) {
            $updateStmt->bind_param("i", $payload['user_id']);
            $updateStmt->execute();
            $updateStmt->close();
        }
    }

    return $payload;
}