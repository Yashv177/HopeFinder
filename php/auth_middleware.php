<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../Database/Conn_db.php';
require_once __DIR__ . '/jwt_helper.php';

class AuthMiddleware
{
    public static function checkAuthentication()
    {
        global $conn;

        if (!isset($_COOKIE['auth_token']) || empty($_COOKIE['auth_token'])) {
            self::redirectToLogin();
        }

        $token = $_COOKIE['auth_token'];
        $payload = JWT::decode($token);

        if (!$payload || !isset($payload['user_id']) || !isset($payload['role'])) {
            self::logout();
        }

        $stmt = $conn->prepare("SELECT user_id, expires_at, is_active FROM sessions WHERE session_token = ? LIMIT 1");
        if (!$stmt) {
            self::logout();
        }

        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();

        if (!$result || $result->num_rows === 0) {
            $stmt->close();
            self::logout();
        }

        $session = $result->fetch_assoc();
        $stmt->close();

        if ((int)$session['is_active'] !== 1) {
            self::logout();
        }

        if (strtotime($session['expires_at']) < time()) {
            self::logout();
        }

        $_SESSION['user_id'] = $payload['user_id'];
        $_SESSION['email'] = $payload['email'] ?? '';
        $_SESSION['fullname'] = $payload['fullname'] ?? '';
        $_SESSION['role'] = $payload['role'];
        $_SESSION['authenticated'] = true;

        $checkColumns = $conn->query("SHOW COLUMNS FROM users LIKE 'last_activity'");
        if ($checkColumns && $checkColumns->num_rows > 0) {
            $userId = (int)$payload['user_id'];
            $updateStmt = $conn->prepare("UPDATE users SET last_activity = NOW() WHERE user_id = ?");
            if ($updateStmt) {
                $updateStmt->bind_param("i", $userId);
                $updateStmt->execute();
                $updateStmt->close();
            }
        }

        return $payload;
    }

    public static function checkRole($required_role) {
        $payload = self::checkAuthentication();
        if (!isset($payload['role']) || $payload['role'] !== $required_role) {
            self::redirectToLogin();
        }
        return $payload;
    }

    public static function logout()
    {
        global $conn;

        if (isset($_COOKIE['auth_token']) && !empty($_COOKIE['auth_token'])) {
            $token = $_COOKIE['auth_token'];

            $stmt = $conn->prepare("UPDATE sessions SET is_active = 0 WHERE session_token = ?");
            if ($stmt) {
                $stmt->bind_param("s", $token);
                $stmt->execute();
                $stmt->close();
            }

            $stmt2 = $conn->prepare("UPDATE login_logs SET logout_time = NOW() WHERE session_token = ? AND logout_time IS NULL");
            if ($stmt2) {
                $stmt2->bind_param("s", $token);
                $stmt2->execute();
                $stmt2->close();
            }
        }

        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        setcookie('auth_token', '', time() - 3600, '/');

        header("Location: ../Login.html");
        exit();
    }

    private static function redirectToLogin()
    {
        header("Location: ../Login.html");
        exit();
    }

    public static function getDeviceInfo()
    {
        return [
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'ip_address' => self::getClientIP(),
            'browser' => self::getBrowser(),
            'os' => self::getOS()
        ];
    }

    private static function getClientIP()
    {
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    private static function getBrowser()
    {
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

        if (stripos($ua, 'Edg') !== false) return 'Edge';
        if (stripos($ua, 'Chrome') !== false) return 'Chrome';
        if (stripos($ua, 'Firefox') !== false) return 'Firefox';
        if (stripos($ua, 'Safari') !== false) return 'Safari';
        if (stripos($ua, 'Opera') !== false) return 'Opera';

        return 'Unknown';
    }

    private static function getOS()
    {
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

        if (stripos($ua, 'Windows') !== false) return 'Windows';
        if (stripos($ua, 'Mac') !== false) return 'Mac';
        if (stripos($ua, 'Linux') !== false) return 'Linux';
        if (stripos($ua, 'Android') !== false) return 'Android';
        if (stripos($ua, 'iPhone') !== false) return 'iOS';

        return 'Unknown';
    }
}