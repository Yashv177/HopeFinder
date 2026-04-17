<?php
include_once 'jwt_helper.php';
include_once '../Database/Conn_db.php';

class AuthMiddleware {
    public static function checkAuthentication() {
        global $conn;

        if (!isset($_COOKIE['auth_token']) || empty($_COOKIE['auth_token'])) {
            self::redirectToLogin();
        }

        $token = $_COOKIE['auth_token'];
        $payload = JWT::decode($token);

        if (!$payload || !isset($payload['exp']) || $payload['exp'] < time()) {
            self::logout();
        }

        $stmt = $conn->prepare("SELECT user_id FROM sessions WHERE session_token = ? AND is_active = 1 AND expires_at > NOW() LIMIT 1");
        if (!$stmt) {
            self::redirectToLogin();
        }

        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            self::logout();
        }

        $checkColumns = $conn->query("SHOW COLUMNS FROM users LIKE 'last_activity'");
        if ($checkColumns && $checkColumns->num_rows > 0) {
            $stmt2 = $conn->prepare("UPDATE users SET last_activity = NOW() WHERE user_id = ?");
            if ($stmt2) {
                $stmt2->bind_param("i", $payload['user_id']);
                $stmt2->execute();
                $stmt2->close();
            }
        }

        $_SESSION['user_id'] = $payload['user_id'];
        $_SESSION['email'] = $payload['email'] ?? '';
        $_SESSION['fullname'] = $payload['fullname'] ?? '';
        $_SESSION['role'] = $payload['role'];
        $_SESSION['authenticated'] = true;

        return $payload;
    }

    public static function checkRole($required_role) {
        $payload = self::checkAuthentication();
        if (($payload['role'] ?? '') !== $required_role) {
            header("Location: ../unauthorized.php");
            exit();
        }
    }

    public static function logout() {
        global $conn;

        if (isset($_COOKIE['auth_token'])) {
            $token = $_COOKIE['auth_token'];

            $stmt = $conn->prepare("UPDATE sessions SET is_active = 0 WHERE session_token = ?");
            if ($stmt) {
                $stmt->bind_param("s", $token);
                $stmt->execute();
                $stmt->close();
            }

            $stmt = $conn->prepare("UPDATE login_logs SET logout_time = NOW() WHERE session_token = ? AND logout_time IS NULL");
            if ($stmt) {
                $stmt->bind_param("s", $token);
                $stmt->execute();
                $stmt->close();
            }
        }

        session_unset();
        session_destroy();

        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

        setcookie('auth_token', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);

        header("Location: ../Login.html");
        exit();
    }

    private static function redirectToLogin() {
        header("Location: ../Login.html");
        exit();
    }

    public static function getDeviceInfo() {
        return [
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'ip_address' => self::getClientIP(),
            'browser' => self::getBrowser(),
            'os' => self::getOS()
        ];
    }

    private static function getClientIP() {
        $ip_keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    private static function getBrowser() {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $browsers = ['Chrome', 'Firefox', 'Safari', 'Edge', 'Opera'];

        foreach ($browsers as $browser) {
            if (stripos($user_agent, $browser) !== false) {
                return $browser;
            }
        }
        return 'Unknown';
    }

    private static function getOS() {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $os = [
            'Windows' => 'Windows',
            'Mac' => 'Mac',
            'Linux' => 'Linux',
            'Android' => 'Android',
            'iOS' => 'iPhone'
        ];

        foreach ($os as $os_name => $pattern) {
            if (stripos($user_agent, $pattern) !== false) {
                return $os_name;
            }
        }
        return 'Unknown';
    }
}