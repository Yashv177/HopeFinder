<?php
require_once __DIR__ . '/../../Database/Conn_db.php';
require_once __DIR__ . '/../../api/helpers/notification_helper.php';

header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

$target    = $data['target'] ?? '';
$recipient = trim($data['recipient'] ?? '');
$message   = trim($data['message'] ?? '');

// ❌ VALIDATION
if(empty($message)){
    echo json_encode(["error" => "Message required"]);
    exit;
}

// ===============================
// 🔴 ALL USERS
// ===============================
if($target === "all"){
    notifyAll($message);
    echo json_encode(["success" => true, "type" => "all"]);
    exit;
}

// ===============================
// 🔴 POLICE USERS
// ===============================
if($target === "police"){
    notifyPolice($message);
    echo json_encode(["success" => true, "type" => "police"]);
    exit;
}

// ===============================
// 🔴 SPECIFIC USER (ID OR EMAIL)
// ===============================
if($target === "public"){

    if(empty($recipient)){
        echo json_encode(["error" => "User ID or Email required"]);
        exit;
    }

    $user_id = null;

    // 🔹 CASE 1: USER ID
    if(is_numeric($recipient)){
        $user_id = intval($recipient);
    }

    // 🔹 CASE 2: EMAIL
    else{
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE LOWER(email)=LOWER(?) LIMIT 1");
        $stmt->bind_param("s", $recipient);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();

        if($res){
            $user_id = $res['user_id'];
        } else {
            echo json_encode(["error" => "User not found with this email"]);
            exit;
        }
    }

    // 🔥 FINAL INSERT
    if($user_id){
        createNotification($user_id, $message, "admin", "user");
        echo json_encode(["success" => true, "user_id" => $user_id]);
        exit;
    } else {
        echo json_encode(["error" => "Invalid user"]);
        exit;
    }
}

// ===============================
// ❌ INVALID TARGET
// ===============================
echo json_encode(["error" => "Invalid target"]);
exit;
?>