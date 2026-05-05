<?php
/**
 * AI System DB Config - Reuses HopeFinder connection
 * Compatible with localhost:3307 XAMPP MySQL
 */
$servername = "localhost:3307";
$username = "root";
$password = "";
$database = "hopefinder";

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("AI DB Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>

