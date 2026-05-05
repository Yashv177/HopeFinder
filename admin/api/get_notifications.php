<?php
require_once __DIR__ . '/../../Database/Conn_db.php';

$res = $conn->query("SELECT message, created_at 
                     FROM notifications 
                     ORDER BY created_at DESC 
                     LIMIT 10");

$data = [];
while($row = $res->fetch_assoc()){
    $data[] = $row;
}

echo json_encode($data);
?>