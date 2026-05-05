-- HopeFinder Real-Time Schema Updates
-- Run in phpMyAdmin/MySQL

-- Add found columns to missing_reports
ALTER TABLE missing_reports 
ADD COLUMN IF NOT EXISTS found_image VARCHAR(500) DEFAULT NULL AFTER photo,
ADD COLUMN IF NOT EXISTS found_time DATETIME DEFAULT NULL AFTER found_image,
ADD COLUMN IF NOT EXISTS found_location VARCHAR(500) DEFAULT NULL AFTER found_time,
ADD INDEX idx_status_active (status),
ADD INDEX idx_report_id (report_id);

-- Live detections table (for real-time polling)
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

-- Update sample data
UPDATE missing_reports SET status = 'searching' WHERE status = 'assigned' AND status != 'closed';

-- Verification queries
-- DESCRIBE missing_reports;
-- DESCRIBE live_detections;
-- SELECT * FROM missing_reports WHERE status = 'searching';

