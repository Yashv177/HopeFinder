-- HopeFinder Database Schema Updates
-- Run these SQL statements in phpMyAdmin (or your database management tool)

-- =====================================================
-- ADD MISSING COLUMNS TO USERS TABLE
-- =====================================================

-- Add last_activity column if not exists
ALTER TABLE users ADD COLUMN last_activity TIMESTAMP NULL DEFAULT NULL AFTER mobile_number;

-- Add session_token column if not exists
ALTER TABLE users ADD COLUMN session_token VARCHAR(255) NULL DEFAULT NULL AFTER last_activity;

-- Add login_attempts column if not exists
ALTER TABLE users ADD COLUMN login_attempts INT DEFAULT 0 AFTER session_token;

-- Add locked_until column if not exists
ALTER TABLE users ADD COLUMN locked_until DATETIME NULL DEFAULT NULL AFTER login_attempts;

-- =====================================================
-- CREATE SESSIONS TABLE
-- =====================================================

CREATE TABLE IF NOT EXISTS sessions (
    session_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_token VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent TEXT DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_session_token (session_token),
    INDEX idx_user_id (user_id),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- CREATE LOGIN_LOGS TABLE
-- =====================================================

CREATE TABLE IF NOT EXISTS login_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    role VARCHAR(20) NOT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    device_info JSON DEFAULT NULL,
    latitude DECIMAL(10,8) DEFAULT NULL,
    longitude DECIMAL(11,8) DEFAULT NULL,
    session_token VARCHAR(255) DEFAULT NULL,
    login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    logout_time TIMESTAMP NULL DEFAULT NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_login_time (login_time),
    INDEX idx_session_token (session_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- VERIFICATION QUERIES
-- =====================================================

-- Check if columns were added to users table:
-- DESCRIBE users;

-- Check if tables were created:
-- SHOW TABLES;

