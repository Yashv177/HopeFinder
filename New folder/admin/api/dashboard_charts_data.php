<?php
header('Content-Type: application/json');

error_reporting(0);
ini_set('display_errors', 0);

// DB connection
require_once '../../Database/Conn_db.php';

$data = [];

/* 🔹 STATUS */
$res = mysqli_query($conn, "SELECT status, COUNT(*) as total FROM missing_reports GROUP BY status");
$status = ["pending"=>0,"verified"=>0,"assigned"=>0,"found"=>0,"closed"=>0];

while($row = mysqli_fetch_assoc($res)){
  $status[$row['status']] = (int)$row['total'];
}
$data['status'] = $status;


/* 🔹 WEEKLY */
$res = mysqli_query($conn, "SELECT DAYNAME(created_at) as day, COUNT(*) as total FROM reports GROUP BY day");

$days = ["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"];
$weekly = array_fill_keys($days, 0);

while($row = mysqli_fetch_assoc($res)){
  $weekly[$row['day']] = (int)$row['total'];
}
$data['weekly'] = $weekly;


/* 🔹 TREND */
$res = mysqli_query($conn, "
SELECT DATE(created_at) as date, COUNT(*) as total 
FROM missing_reports 
WHERE status IN ('found','closed')
GROUP BY date
");

$trend = [];
$labels = [];

while($row = mysqli_fetch_assoc($res)){
  $trend[] = (int)$row['total'];
  $labels[] = $row['date'];
}

$data['trend'] = $trend ?: [0];
$data['trend_labels'] = $labels ?: ["No Data"];

// 🔹 MONTHLY REPORTS TREND
$res = mysqli_query($conn, "
SELECT MONTHNAME(created_at) as month, COUNT(*) as total
FROM reports
GROUP BY MONTH(created_at)
ORDER BY MONTH(created_at)
");

$months = [];
$counts = [];

while($row = mysqli_fetch_assoc($res)){
  $months[] = $row['month'];
  $counts[] = (int)$row['total'];
}

// fallback
if(empty($months)){
  $months = ["No Data"];
  $counts = [0];
}

$data['monthly_labels'] = $months;
$data['monthly_data'] = $counts;


/* 🔹 CARDS */
$data['totalMissing'] = mysqli_fetch_assoc(mysqli_query($conn,
"SELECT COUNT(*) as total FROM missing_reports WHERE status NOT IN ('found','closed')"
))['total'];

$data['totalFound'] = mysqli_fetch_assoc(mysqli_query($conn,
"SELECT COUNT(*) as total FROM missing_reports WHERE status IN ('pending','closed')"
))['total'];

$data['todayDetections'] = mysqli_fetch_assoc(mysqli_query($conn,
"SELECT COUNT(*) as total FROM detections WHERE DATE(timestamp)=CURDATE()"
))['total'];

$data['aiSuccess'] = mysqli_fetch_assoc(mysqli_query($conn,
"SELECT COUNT(*) as total FROM missing_reports WHERE status='found'"
))['total'];


/* 🔹 EFFICIENCY */
$total = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as total FROM reports"))['total'];
$approved = $data['aiSuccess'];

$data['efficiency'] = $total > 0 ? round(($approved/$total)*100) : 0;

echo json_encode($data);