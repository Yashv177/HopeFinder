<?php
$servername = "localhost:3307";
$username = "root";
$password = "";
$database = "hopefinder";

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>

