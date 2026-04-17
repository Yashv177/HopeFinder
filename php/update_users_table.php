<?php
// Script to update users table with missing columns
include '../Database/Conn_db.php';

echo "Checking and adding missing columns to users table...<br>";

// Check and add last_activity column
$result = $conn->query("SHOW COLUMNS FROM users LIKE 'last_activity'");
if ($result->num_rows === 0) {
    $sql = "ALTER TABLE users ADD COLUMN last_activity TIMESTAMP NULL DEFAULT NULL AFTER mobile_number";
    if ($conn->query($sql)) {
        echo "Added last_activity column to users table.<br>";
    } else {
        echo "Error adding last_activity: " . $conn->error . "<br>";
    }
} else {
    echo "last_activity column already exists.<br>";
}

// Check and add session_token column
$result = $conn->query("SHOW COLUMNS FROM users LIKE 'session_token'");
if ($result->num_rows === 0) {
    $sql = "ALTER TABLE users ADD COLUMN session_token VARCHAR(255) NULL DEFAULT NULL AFTER last_activity";
    if ($conn->query($sql)) {
        echo "Added session_token column to users table.<br>";
    } else {
        echo "Error adding session_token: " . $conn->error . "<br>";
    }
} else {
    echo "session_token column already exists.<br>";
}

// Check and add login_attempts column
$result = $conn->query("SHOW COLUMNS FROM users LIKE 'login_attempts'");
if ($result->num_rows === 0) {
    $sql = "ALTER TABLE users ADD COLUMN login_attempts INT DEFAULT 0 AFTER session_token";
    if ($conn->query($sql)) {
        echo "Added login_attempts column to users table.<br>";
    } else {
        echo "Error adding login_attempts: " . $conn->error . "<br>";
    }
} else {
    echo "login_attempts column already exists.<br>";
}

// Check and add locked_until column
$result = $conn->query("SHOW COLUMNS FROM users LIKE 'locked_until'");
if ($result->num_rows === 0) {
    $sql = "ALTER TABLE users ADD COLUMN locked_until DATETIME NULL DEFAULT NULL AFTER login_attempts";
    if ($conn->query($sql)) {
        echo "Added locked_until column to users table.<br>";
    } else {
        echo "Error adding locked_until: " . $conn->error . "<br>";
    }
} else {
    echo "locked_until column already exists.<br>";
}

echo "<br>Users table update complete!";
?>

