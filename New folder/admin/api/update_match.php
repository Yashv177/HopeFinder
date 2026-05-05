<?php
$data = json_decode(file_get_contents("php://input"), true);

include_once '../../Database/Conn_db.php';

$report_id = $data['report_id'];
$found_id = $data['found_id'];
$match = $data['match_percent'];

// Check existing
$check = $conn->prepare("
SELECT match_percent FROM matches 
WHERE report_id=? AND found_id=?
ORDER BY match_id DESC LIMIT 1
");
$check->bind_param("ii",$report_id,$found_id);
$check->execute();
$res = $check->get_result();

if($row = $res->fetch_assoc()){
    
    if($match <= $row['match_percent']){
        echo json_encode(["status"=>"ignored_low_score"]);
        exit;
    }
}

// Insert better match
$stmt = $conn->prepare("
INSERT INTO matches (report_id,found_id,match_percent,verified_by,status) 
VALUES (?,?,?,'AI','pending')
");
$stmt->bind_param("iii",$report_id,$found_id,$match);
$stmt->execute();

echo json_encode(["status"=>"saved"]);
?>