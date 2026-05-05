<?php
/**
 * Database Helper Functions
 * Reusable functions for notifications, activity logs, and system operations
 */

require_once __DIR__ . '/../Database/Conn_db.php';

class DatabaseHelpers {
    
    /**
     * Create a notification in the database
     * @param mysqli $conn Database connection
     * @param int|null $user_id User ID (null for global notifications)
     * @param string $message Notification message
     * @param string $type Type: 'registration', 'report', 'match', 'verification', 'found'
     * @param string|null $link Optional link to navigate to
     * @param string $priority Priority level: 'low', 'medium', 'high'
     * @param string $target Target audience: 'all', 'admin', 'police', 'user'
     * @return bool Success status
     */
    public static function createNotification($conn, $user_id, $message, $type, $link = null, $priority = 'low', $target = 'all') {
        // Input sanitization
        $message = sanitizeInput($message);
        $type = sanitizeInput($type);
        $link = $link ? sanitizeInput($link) : null;
        
        $stmt = $conn->prepare("
            INSERT INTO notifications (user_id, target, message, type, link, priority, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, 'unread', NOW())
        ");
        
        if (!$stmt) {
            error_log("Prepare failed: " . $conn->error);
            return false;
        }
        
        $stmt->bind_param(
            "isssss",
            $user_id,
            $target,
            $message,
            $type,
            $link,
            $priority
        );
        
        if ($stmt->execute()) {
            $stmt->close();
            return true;
        } else {
            error_log("Notification insert failed: " . $stmt->error);
            $stmt->close();
            return false;
        }
    }
    
    /**
     * Get notifications for a user or role
     * @param mysqli $conn Database connection
     * @param int $user_id User ID
     * @param string $target Target type ('all', 'admin', 'police', 'user')
     * @param int $limit Limit results
     * @param bool $unread_only Get only unread
     * @return array Notifications array
     */
    public static function getNotifications($conn, $user_id, $target = 'user', $limit = 50, $unread_only = false) {
        $query = "
            SELECT * FROM notifications 
            WHERE (user_id = ? OR target = 'all' OR target = ?)
        ";
        
        if ($unread_only) {
            $query .= " AND status = 'unread'";
        }
        
        $query .= " ORDER BY created_at DESC LIMIT ?";
        
        $stmt = $conn->prepare($query);
        
        if (!$stmt) {
            error_log("Prepare failed: " . $conn->error);
            return [];
        }
        
        $stmt->bind_param("isi", $user_id, $target, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $notifications = [];
        while ($row = $result->fetch_assoc()) {
            $notifications[] = $row;
        }
        
        $stmt->close();
        return $notifications;
    }
    
    /**
     * Mark notification as read
     * @param mysqli $conn Database connection
     * @param int $notif_id Notification ID
     * @return bool Success status
     */
    public static function markNotificationAsRead($conn, $notif_id) {
        $stmt = $conn->prepare("UPDATE notifications SET status = 'read', updated_at = NOW() WHERE notif_id = ?");
        
        if (!$stmt) {
            error_log("Prepare failed: " . $conn->error);
            return false;
        }
        
        $stmt->bind_param("i", $notif_id);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    /**
     * Get unread notification count for a user
     * @param mysqli $conn Database connection
     * @param int $user_id User ID
     * @param string $target Target type
     * @return int Count of unread notifications
     */
    public static function getUnreadCount($conn, $user_id, $target = 'user') {
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count FROM notifications 
            WHERE (user_id = ? OR target = 'all' OR target = ?) 
            AND status = 'unread'
        ");
        
        if (!$stmt) {
            error_log("Prepare failed: " . $conn->error);
            return 0;
        }
        
        $stmt->bind_param("is", $user_id, $target);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return (int)$row['count'];
    }
    
    /**
     * Log user activity
     * @param mysqli $conn Database connection
     * @param int $user_id User ID
     * @param string $action Action performed
     * @param string|null $details Additional details
     * @return bool Success status
     */
    public static function logActivity($conn, $user_id, $action, $details = null) {
        // Get client IP
        $ip_address = self::getClientIP();
        
        // Input sanitization
        $action = sanitizeInput($action);
        $details = $details ? sanitizeInput($details) : null;
        
        // Check if activity_logs table exists
        $check = $conn->query("SHOW TABLES LIKE 'activity_logs'");
        if (!$check || $check->num_rows === 0) {
            error_log("activity_logs table does not exist");
            return false;
        }
        
        $stmt = $conn->prepare("
            INSERT INTO activity_logs (user_id, action, details, ip_address, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        
        if (!$stmt) {
            error_log("Prepare failed: " . $conn->error);
            return false;
        }
        
        $stmt->bind_param("isss", $user_id, $action, $details, $ip_address);
        
        if ($stmt->execute()) {
            $stmt->close();
            return true;
        } else {
            error_log("Activity log insert failed: " . $stmt->error);
            $stmt->close();
            return false;
        }
    }
    
    /**
     * Get activity logs
     * @param mysqli $conn Database connection
     * @param int|null $user_id Optional user ID filter
     * @param int $limit Limit results
     * @return array Activity logs
     */
    public static function getActivityLogs($conn, $user_id = null, $limit = 100) {
        $query = "SELECT * FROM activity_logs";
        
        if ($user_id) {
            $query .= " WHERE user_id = ?";
        }
        
        $query .= " ORDER BY created_at DESC LIMIT ?";
        
        $stmt = $conn->prepare($query);
        
        if (!$stmt) {
            error_log("Prepare failed: " . $conn->error);
            return [];
        }
        
        if ($user_id) {
            $stmt->bind_param("ii", $user_id, $limit);
        } else {
            $stmt->bind_param("i", $limit);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $logs = [];
        while ($row = $result->fetch_assoc()) {
            $logs[] = $row;
        }
        
        $stmt->close();
        return $logs;
    }
    
    /**
     * Get user profile information
     * @param mysqli $conn Database connection
     * @param int $user_id User ID
     * @return array|null User profile data
     */
    public static function getUserProfile($conn, $user_id) {
        $stmt = $conn->prepare("
            SELECT user_id, fullname, email, phone, mobile_number, role, created_at, last_activity
            FROM users 
            WHERE user_id = ?
        ");
        
        if (!$stmt) {
            error_log("Prepare failed: " . $conn->error);
            return null;
        }
        
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $profile = $result->fetch_assoc();
        $stmt->close();
        
        return $profile;
    }
    
    /**
     * Update user profile
     * @param mysqli $conn Database connection
     * @param int $user_id User ID
     * @param array $data Profile data to update
     * @return bool Success status
     */
    public static function updateUserProfile($conn, $user_id, $data) {
        $allowed_fields = ['fullname', 'phone', 'mobile_number'];
        $updates = [];
        $types = '';
        $values = [];
        
        foreach ($allowed_fields as $field) {
            if (isset($data[$field])) {
                $updates[] = "$field = ?";
                $types .= 's';
                $values[] = sanitizeInput($data[$field]);
            }
        }
        
        if (empty($updates)) {
            return false;
        }
        
        $query = "UPDATE users SET " . implode(', ', $updates) . " WHERE user_id = ?";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            error_log("Prepare failed: " . $conn->error);
            return false;
        }
        
        $types .= 'i';
        $values[] = $user_id;
        
        $stmt->bind_param($types, ...$values);
        
        if ($stmt->execute()) {
            $stmt->close();
            return true;
        } else {
            error_log("Profile update failed: " . $stmt->error);
            $stmt->close();
            return false;
        }
    }
    
    /**
     * Change user password
     * @param mysqli $conn Database connection
     * @param int $user_id User ID
     * @param string $old_password Old password
     * @param string $new_password New password
     * @return array Result array with status and message
     */
    public static function changePassword($conn, $user_id, $old_password, $new_password) {
        // Validate password strength
        if (strlen($new_password) < 8) {
            return [
                'success' => false,
                'message' => 'New password must be at least 8 characters long.'
            ];
        }
        
        // Get current password hash
        $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
        
        if (!$stmt) {
            error_log("Prepare failed: " . $conn->error);
            return [
                'success' => false,
                'message' => 'Database error occurred.'
            ];
        }
        
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        
        if ($result->num_rows === 0) {
            return [
                'success' => false,
                'message' => 'User not found.'
            ];
        }
        
        $row = $result->fetch_assoc();
        
        // Verify old password
        if (!password_verify($old_password, $row['password'])) {
            return [
                'success' => false,
                'message' => 'Current password is incorrect.'
            ];
        }
        
        // Hash new password
        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
        
        // Update password
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        
        if (!$stmt) {
            error_log("Prepare failed: " . $conn->error);
            return [
                'success' => false,
                'message' => 'Database error occurred.'
            ];
        }
        
        $stmt->bind_param("si", $hashed_password, $user_id);
        
        if ($stmt->execute()) {
            $stmt->close();
            
            // Log the password change activity
            self::logActivity($conn, $user_id, 'password_changed');
            
            return [
                'success' => true,
                'message' => 'Password changed successfully.'
            ];
        } else {
            error_log("Password update failed: " . $stmt->error);
            $stmt->close();
            return [
                'success' => false,
                'message' => 'Failed to update password.'
            ];
        }
    }
    
    /**
     * Get AI matches
     * @param mysqli $conn Database connection
     * @param int|null $report_id Optional filter by report ID
     * @param int $limit Limit results
     * @return array AI matches
     */
    public static function getAIMatches($conn, $report_id = null, $limit = 100) {
        $query = "SELECT * FROM ai_matches";
        
        if ($report_id) {
            $query .= " WHERE report_id = ?";
        }
        
        $query .= " ORDER BY match_percent DESC, created_at DESC LIMIT ?";
        
        $stmt = $conn->prepare($query);
        
        if (!$stmt) {
            error_log("Prepare failed: " . $conn->error);
            return [];
        }
        
        if ($report_id) {
            $stmt->bind_param("ii", $report_id, $limit);
        } else {
            $stmt->bind_param("i", $limit);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $matches = [];
        while ($row = $result->fetch_assoc()) {
            $matches[] = $row;
        }
        
        $stmt->close();
        return $matches;
    }
    
    /**
     * Get client IP address
     * @return string Client IP
     */
    private static function getClientIP() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        }
        return trim($ip);
    }
}

/**
 * Sanitize input to prevent SQL injection and XSS
 * @param string $input Input to sanitize
 * @return string Sanitized input
 */
function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Send JSON response
 * @param array $data Response data
 * @param int $statusCode HTTP status code
 */
function sendJSONResponse($data, $statusCode = 200) {
    header('Content-Type: application/json');
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}
?>
