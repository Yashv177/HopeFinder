-- Create Notifications Table for HopeFinder
CREATE TABLE IF NOT EXISTS notifications (
  notif_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  target ENUM('all','admin','police','user') DEFAULT 'all',
  message TEXT NOT NULL,
  type VARCHAR(50) NULL,
  link VARCHAR(255) NULL,
  priority ENUM('low','medium','high') DEFAULT 'low',
  status ENUM('unread','read') DEFAULT 'unread',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_user_status (user_id, status),
  INDEX idx_target_status (target, status),
  INDEX idx_created (created_at)
);

-- Sample data
INSERT INTO notifications (user_id, target, message, type, link, priority) VALUES
(1, 'police', 'New missing person report #R001 submitted', 'new_report', 'php/view-report.php?id=R001', 'high'),
(1, 'admin', 'New user registered: John Doe', 'new_user', NULL, 'low'),
(NULL, 'all', 'System maintenance scheduled', 'system', NULL, 'medium');

