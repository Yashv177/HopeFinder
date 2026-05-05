<?php
include_once '../../Database/Conn_db.php';

$result = $conn->query("SELECT * FROM ai_matches ORDER BY id DESC LIMIT 10");

$data = [];

while($row = $result->fetch_assoc()){
    $data[] = $row;
}

echo json_encode($data);
?>