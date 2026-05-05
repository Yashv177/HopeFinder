-- Create Additional Tables for HopeFinder
-- Run these SQL statements in phpMyAdmin

-- =====================================================
-- CREATE FOUND_PERSONS TABLE
-- =====================================================

CREATE TABLE IF NOT EXISTS found_persons (
    found_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    found_name VARCHAR(255) DEFAULT NULL,
    found_location VARCHAR(500) DEFAULT NULL,
    found_datetime DATETIME DEFAULT NULL,
    description TEXT DEFAULT NULL,
    photo_path VARCHAR(500) DEFAULT NULL,
    contact_info VARCHAR(255) DEFAULT NULL,
    match_status ENUM('unmatched', 'matched', 'confirmed') DEFAULT 'unmatched',
    ai_matched_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_match_status (match_status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- CREATE AI_MATCHES TABLE
-- =====================================================

CREATE TABLE IF NOT EXISTS ai_matches (
    match_id INT AUTO_INCREMENT PRIMARY KEY,
    found_id INT NOT NULL,
    report_id INT NOT NULL,
    match_percentage DECIMAL(5,2) DEFAULT NULL,
    suggested_by ENUM('AI', 'manual') DEFAULT 'AI',
    status ENUM('pending', 'confirmed', 'rejected') DEFAULT 'pending',
    verified_by INT DEFAULT NULL,
    verified_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_found_id (found_id),
    INDEX idx_report_id (report_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- CREATE REPORT_ASSIGNMENTS TABLE
-- =====================================================

CREATE TABLE IF NOT EXISTS report_assignments (
    assignment_id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT NOT NULL,
    police_id INT NOT NULL,
    assigned_at DATETIME DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_report_id (report_id),
    INDEX idx_police_id (police_id),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- ADD STATUS COLUMNS TO MISSING_REPORTS TABLE
-- =====================================================

ALTER TABLE missing_reports 
ADD COLUMN IF NOT EXISTS officer_remarks TEXT DEFAULT NULL AFTER description,
ADD COLUMN IF NOT EXISTS verified_at DATETIME DEFAULT NULL AFTER status,
ADD COLUMN IF NOT EXISTS closed_at DATETIME DEFAULT NULL AFTER verified_at,
ADD COLUMN IF NOT EXISTS updated_at DATETIME DEFAULT NULL AFTER closed_at;

-- =====================================================
-- ADD COLUMNS TO POLICE TABLE (if missing)
-- =====================================================

ALTER TABLE police 
ADD COLUMN IF NOT EXISTS created_at DATETIME DEFAULT NULL AFTER state;

-- =====================================================
-- SAMPLE DATA FOR TESTING
-- =====================================================

-- Insert sample found person (for testing)
INSERT INTO found_persons (user_id, found_name, found_location, found_datetime, description, photo_path, contact_info, match_status) 
VALUES (1, 'Unknown Person Found', 'Patna Bus Stand', NOW(), 'Found wandering near bus stand, unable to communicate', NULL, 'Contact police station', 'unmatched');

-- Insert sample AI match (for testing)
INSERT INTO ai_matches (found_id, report_id, match_percentage, suggested_by, status) 
VALUES (1, 1, 87.50, 'AI', 'pending');

-- Assign a police officer to a report (for testing)
INSERT INTO report_assignments (report_id, police_id, assigned_at, is_active) 
VALUES (1, 1, NOW(), 1);

-- =====================================================
-- VERIFICATION QUERIES
-- =====================================================

-- Check if tables were created:
-- SHOW TABLES;

-- Check new columns in missing_reports:
-- DESCRIBE missing_reports;

-- Check new columns in police:
-- DESCRIBE police;

