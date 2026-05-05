<?php
require_once '../../Database/Conn_db.php';

$id = $_GET['found_id'] ?? '';

if (!$id) {
    echo "Invalid Found ID";
    exit;
}

// ===== FOUND PERSON (from detections) =====
$stmt = $conn->prepare("SELECT d.detection_id as found_id, d.image_path as photo_path, d.address as found_location, d.timestamp as found_datetime, d.confidence, d.report_id, mr.missing_name as found_name FROM detections d LEFT JOIN missing_reports mr ON d.report_id = mr.report_id WHERE d.detection_id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$person = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$person) {
    echo "Detection record not found";
    exit;
}

// ===== AI MATCHES (if any) =====
$matches = $conn->query("
    SELECT am.*, mr.missing_name, mr.report_id, mr.photo as missing_photo, mr.last_seen_location, mr.age as missing_age, mr.gender as missing_gender
    FROM ai_matches am
    LEFT JOIN missing_reports mr ON am.report_id = mr.report_id
    WHERE am.found_id = '$id'
    ORDER BY am.match_percent DESC
");
?>
<!DOCTYPE html>
<html>
<head>
    <title>HopeFinder - Found Person Report</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 30px; color: #333; }
        .header { text-align: center; border-bottom: 2px solid #000; margin-bottom: 20px; padding-bottom: 10px; }
        .header h1 { margin: 0; }
        .section { margin-bottom: 25px; }
        .section h3 { border-left: 5px solid #28a745; padding-left: 10px; }
        table { width: 100%; border-collapse: collapse; }
        td, th { padding: 8px; border-bottom: 1px solid #ddd; }
        .photo img { width: 200px; border: 1px solid #ccc; padding: 5px; }
        .compare { display: flex; gap: 20px; margin-top: 10px; }
        .compare img { width: 150px; border: 1px solid #ccc; padding: 5px; }
        .footer { position: fixed; bottom: 10px; left: 30px; right: 30px; font-size: 12px; display: flex; justify-content: space-between; border-top: 1px solid #ccc; padding-top: 5px; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-info { background: #d1ecf1; color: #0c5460; }
    </style>
</head>
<body onload="window.print()">
    <div class="header">
        <h1>HopeFinder - Found Person Report</h1>
        <small><?= date("d M Y, h:i A") ?></small>
    </div>

    <div class="section">
        <h3>Found Person Details</h3>
        <table>
            <tr><td><b>Found ID</b></td><td>#D<?= $person['found_id'] ?? '' ?></td></tr>
            <tr><td><b>Name</b></td><td><?= htmlspecialchars($person['found_name'] ?? 'Unknown') ?></td></tr>
            <tr><td><b>Found Location</b></td><td><?= htmlspecialchars($person['found_location'] ?? 'N/A') ?></td></tr>
            <tr><td><b>Date Found</b></td><td><?= isset($person['found_datetime']) ? date("d M Y, h:i A", strtotime($person['found_datetime'])) : 'N/A' ?></td></tr>
            <tr><td><b>Confidence Score</b></td><td><?= isset($person['confidence']) ? round($person['confidence'], 2) . '%' : 'N/A' ?></td></tr>
            <tr><td><b>Report ID</b></td><td><?= isset($person['report_id']) ? '#R' . $person['report_id'] : 'N/A' ?></td></tr>
        </table>

        <?php if (!empty($person['photo_path'])) { ?>
            <div class="photo">
                <b>Photo:</b><br>
                <?php
                $imgPath = $_SERVER['DOCUMENT_ROOT'] . "/HopeFinder/" . $person['photo_path'];
                if (file_exists($imgPath)) {
                    $type = pathinfo($imgPath, PATHINFO_EXTENSION);
                    $data = base64_encode(file_get_contents($imgPath));
                    echo '<img src="data:image/'.$type.';base64,'.$data.'" width="200">';
                } else {
                    echo "No Image Available";
                }
                ?>
            </div>
        <?php } ?>
    </div>

    <?php if ($matches && $matches->num_rows > 0) { ?>
    <div class="section">
        <h3>AI Match Suggestions</h3>
        <table>
            <tr>
                <th>#</th>
                <th>Match ID</th>
                <th>Missing Person</th>
                <th>Report ID</th>
                <th>Match %</th>
                <th>Status</th>
            </tr>
            <?php $i = 1; while($m = $matches->fetch_assoc()) { ?>
            <tr>
                <td><?= $i++ ?></td>
                <td>#M<?= $m['match_id'] ?></td>
                <td><?= htmlspecialchars($m['missing_name'] ?: 'Unknown') ?></td>
                <td>#R<?= $m['report_id'] ?></td>
                <td><?= $m['match_percent'] ?>%</td>
                <td><?= ucfirst($m['status'] ?: 'Pending') ?></td>
            </tr>
            <?php } ?>
        </table>
    </div>
    <?php } ?>

    <div class="footer">
        <div>HopeFinder System</div>
        <div>Confidential Report</div>
    </div>
</body>
</html>

