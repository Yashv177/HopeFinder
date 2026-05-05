<?php
require_once '../../Database/Conn_db.php';
session_start();

header('Content-Type: application/json');

// Get logged-in user
$user_id = $_SESSION['user_id'] ?? 0;

// Get police_id
$police = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT police_id FROM police WHERE user_id = '$user_id'
"));

$police_id = $police['police_id'] ?? 0;

// TOTAL REPORTS
$total = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(DISTINCT mr.report_id) as total
    FROM missing_reports mr
    LEFT JOIN report_assignments ra ON mr.report_id = ra.report_id
    WHERE ra.police_id = '$police_id' OR mr.status = 'pending'
"))['total'];

// PENDING
$pending = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) as total FROM missing_reports WHERE status = 'pending'
"))['total'];

// VERIFIED
$verified = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) as total FROM missing_reports WHERE status = 'verified'
"))['total'];

// FOUND
$found = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) as total FROM missing_reports WHERE status = 'found'
"))['total'];

// TREND (7 DAYS)
$trend = [];
$res = mysqli_query($conn, "
    SELECT DATE(created_at) as date, COUNT(*) as count
    FROM missing_reports
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at)
");
while($row = mysqli_fetch_assoc($res)) $trend[] = $row;

// STATUS DATA
$status_data = [
    "pending" => $pending,
    "verified" => $verified,
    "found" => $found
];

// COMPARISON
$comparison = [
    "found" => $found,
    "missing" => $total - $found
];

echo json_encode([
    "total_reports" => $total,
    "pending_cases" => $pending,
    "verified_cases" => $verified,
    "found_cases" => $found,
    "trend_data" => $trend,
    "status_data" => $status_data,
    "comparison" => $comparison
]);