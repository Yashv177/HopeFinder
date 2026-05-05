<?php
$filename = $_GET['file'] ?? '';
if (empty($filename) || !preg_match('/^[a-zA-Z0-9._ -]+$/', $filename)) {
    http_response_code(400);
    exit('Invalid file');
}

$path = 'uploads/missing/' . $filename;
if (file_exists($path)) {
    header('Content-Type: image/jpeg');
    header('Content-Length: ' . filesize($path));
    readfile($path);
} else {
    // Default placeholder
    $default = 'image/no-photo.jpg';
    if (file_exists($default)) {
        header('Content-Type: image/jpeg');
        readfile($default);
    }
}
?>

