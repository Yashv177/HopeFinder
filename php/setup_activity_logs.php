<?php
/**
 * Database Migration Script - Activity Logs Table Setup
 * This script creates the activity_logs table required by the Admin Dashboard
 * 
 * Run once: Visit http://localhost/HopeFinder/php/setup_activity_logs.php in your browser
 */

require_once 'Conn_db.php';

// Check if already completed
$checkTable = $conn->query("SHOW TABLES LIKE 'activity_logs'");

if ($checkTable && $checkTable->num_rows > 0) {
    die("✅ activity_logs table already exists. No action needed.");
}

// Create the table
$sql = "CREATE TABLE IF NOT EXISTS activity_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    device_info LONGTEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql) === TRUE) {
    echo "✅ SUCCESS: activity_logs table created successfully!\n\n";
    echo "Table structure:\n";
    $result = $conn->query("DESCRIBE activity_logs");
    echo "<pre>";
    while ($row = $result->fetch_assoc()) {
        echo $row['Field'] . " - " . $row['Type'] . ($row['Null'] === 'NO' ? ' NOT NULL' : '') . "\n";
    }
    echo "</pre>";
} else {
    echo "❌ ERROR: Failed to create table\n";
    echo "Error: " . $conn->error;
}

$conn->close();
?>
