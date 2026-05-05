<?php
header("Content-Type: application/json");
include_once '../../Database/Conn_db.php';

$data = json_decode(file_get_contents("php://input"), true);

if(!$data){
    echo json_encode(["status"=>"error"]);
    exit;
}

$name = $data['name'];
$image = $data['image'];
$confidence = $data['confidence'];
$lat = $data['lat'];
$lng = $data['lng'];

// get report_id
$res = $conn->query("SELECT report_id FROM missing_reports WHERE missing_name='$name' LIMIT 1");

if($res->num_rows > 0){
    $row = $res->fetch_assoc();
    $report_id = $row['report_id'];

    $conn->query("
        INSERT INTO ai_matches (report_id, image, confidence, latitude, longitude, created_at)
        VALUES ('$report_id','$image','$confidence','$lat','$lng',NOW())
    ");

    echo json_encode(["status"=>"success"]);
}else{
    echo json_encode(["status"=>"not_found"]);
}
?>