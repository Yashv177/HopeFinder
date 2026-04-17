<?php
include_once 'Database/Conn_db.php';

// Copy from old locations to new uploads/missing/
$old_dirs = ['ai_multi_test/missing/', 'New folder/missing/', 'uploads/missing_persons/'];
$new_dir = 'uploads/missing/';

foreach ($old_dirs as $old_dir) {
    if (is_dir($old_dir)) {
        $files = glob($old_dir . '*.{jpg,jpeg,png}', GLOB_BRACE);
        foreach ($files as $file) {
            $filename = basename($file);
            $new_path = $new_dir . $filename;
            if (copy($file, $new_path)) {
                echo "Copied $filename → $new_path<br>";
            }
        }
    }
}

// Link DB photos to new location (update photo paths)
$conn->query("UPDATE missing_reports SET photo = CONCAT('uploads/missing/', basename(photo)) WHERE photo IS NOT NULL");

echo "<br>✅ Migration complete! Check uploads/missing/ and dashboard/realtime.html";
?>

