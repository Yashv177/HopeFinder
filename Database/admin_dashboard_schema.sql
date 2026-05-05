-- HopeFinder Database Schema Updates
-- Run these queries to ensure all tables are properly structured

-- 1. Ensure login_logs table exists
CREATE TABLE IF NOT EXISTS login_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_token VARCHAR(500),
    action VARCHAR(100),
    ip_address VARCHAR(45),
    details TEXT,
    login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    logout_time DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Ensure notifications table has all required columns
ALTER TABLE notifications 
ADD COLUMN IF NOT EXISTS notif_id INT AUTO_INCREMENT UNIQUE FIRST,
ADD COLUMN IF NOT EXISTS user_id INT NULL AFTER notif_id,
ADD COLUMN IF NOT EXISTS target ENUM('all','admin','police','user') DEFAULT 'all' AFTER user_id,
ADD COLUMN IF NOT EXISTS message TEXT NOT NULL AFTER target,
ADD COLUMN IF NOT EXISTS type VARCHAR(50) NULL AFTER message,
ADD COLUMN IF NOT EXISTS link VARCHAR(255) NULL AFTER type,
ADD COLUMN IF NOT EXISTS priority ENUM('low','medium','high') DEFAULT 'low' AFTER link,
ADD COLUMN IF NOT EXISTS status ENUM('unread','read') DEFAULT 'unread' AFTER priority,
ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER status,
ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP AFTER created_at,
ADD INDEX IF NOT EXISTS idx_user_status (user_id, status),
ADD INDEX IF NOT EXISTS idx_target_status (target, status),
ADD INDEX IF NOT EXISTS idx_created (created_at);

-- 3. Ensure ai_matches table exists with proper structure
CREATE TABLE IF NOT EXISTS ai_matches (
    match_id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT NOT NULL,
    found_id INT NOT NULL,
    match_percent DECIMAL(5,2) NOT NULL,
    match_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    suggested_by VARCHAR(50),
    verified_by INT,
    verified_at DATETIME NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_report_id (report_id),
    INDEX idx_status (status),
    INDEX idx_match_percent (match_percent)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Add columns to missing_reports if they don't exist
ALTER TABLE missing_reports 
ADD COLUMN IF NOT EXISTS found_image VARCHAR(500) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS found_time DATETIME DEFAULT NULL,
ADD COLUMN IF NOT EXISTS found_location VARCHAR(500) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS last_activity DATETIME NULL;

-- 5. Ensure users table has required fields
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS last_activity DATETIME NULL,
ADD COLUMN IF NOT EXISTS phone VARCHAR(20) NULL,
ADD COLUMN IF NOT EXISTS mobile_number VARCHAR(20) NULL;

-- 6. Create live_detections table for real-time polling
CREATE TABLE IF NOT EXISTS live_detections (
    detection_id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(50) NOT NULL,
    report_id INT NOT NULL,
    image_path VARCHAR(500),
    confidence DECIMAL(5,2),
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    location_lat DECIMAL(10,8),
    location_lng DECIMAL(11,8),
    INDEX idx_session (session_id),
    INDEX idx_report (report_id),
    INDEX idx_timestamp (timestamp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Verification queries (run these to verify)
-- SELECT * FROM login_logs LIMIT 5;
-- SELECT * FROM notifications LIMIT 5;
-- SELECT * FROM ai_matches LIMIT 5;
-- DESCRIBE login_logs;
-- DESCRIBE notifications;
-- DESCRIBE ai_matches;
