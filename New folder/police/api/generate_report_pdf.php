<?php
require_once '../../Database/Conn_db.php';

$id = $_GET['report_id'] ?? '';

if (!$id) {
    echo "Invalid Report ID";
    exit;
}

// ===== MISSING REPORT =====
$report = mysqli_fetch_assoc(mysqli_query($conn, "
SELECT * FROM missing_reports WHERE report_id = '$id'
"));

// ===== POLICE DETAILS =====
$police = mysqli_fetch_assoc(mysqli_query($conn, "
SELECT p.*
FROM report_assignments ra
JOIN police p ON ra.police_id = p.police_id
WHERE ra.report_id = '$id'
"));

// ===== DETECTIONS =====
$detections = mysqli_query($conn, "
SELECT * FROM detections 
WHERE report_id = '$id'
ORDER BY timestamp DESC
");
?>

<!DOCTYPE html>
<html>

<head>
    <title>HopeFinder Report</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 30px;
            color: #333;
        }

        /* HEADER */
        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            margin-bottom: 20px;
            padding-bottom: 10px;
        }

        .header h1 {
            margin: 0;
        }

        /* SECTION */
        .section {
            margin-bottom: 25px;
        }

        .section h3 {
            border-left: 5px solid #007bff;
            padding-left: 10px;
        }

        /* TABLE */
        table {
            width: 100%;
            border-collapse: collapse;
        }

        td,
        th {
            padding: 8px;
            border-bottom: 1px solid #ddd;
        }

        /* IMAGE */
        .photo img {
            width: 200px;
            border: 1px solid #ccc;
            padding: 5px;
        }

        /* SIDE BY SIDE IMAGE */
        .compare {
            display: flex;
            gap: 20px;
            margin-top: 10px;
        }

        .compare img {
            width: 150px;
            border: 1px solid #ccc;
            padding: 5px;
        }

        /* DETECTION TABLE */
        .det-table th {
            background: #f5f5f5;
        }

        /* FOOTER */
        .footer {
            position: fixed;
            bottom: 10px;
            left: 30px;
            right: 30px;
            font-size: 12px;
            display: flex;
            justify-content: space-between;
            border-top: 1px solid #ccc;
            padding-top: 5px;
        }
    </style>

</head>

<body onload="window.print()">

    <!-- HEADER -->
    <div class="header">
        <h1>HopeFinder - Missing Person Report</h1>
        <small><?= date("d M Y, h:i A") ?></small>
    </div>

    <!-- MISSING DETAILS -->
    <div class="section">
        <h3>Missing Person Details</h3>
        <table>
            <tr>
                <td><b>Report ID</b></td>
                <td>#<?= $report['report_id'] ?></td>
            </tr>
            <tr>
                <td><b>Name</b></td>
                <td><?= $report['missing_name'] ?></td>
            </tr>
            <tr>
                <td><b>Age</b></td>
                <td><?= $report['age'] ?></td>
            </tr>
            <tr>
                <td><b>Gender</b></td>
                <td><?= $report['gender'] ?></td>
            </tr>
            <tr>
                <td><b>Last Seen</b></td>
                <td><?= $report['last_seen_location'] ?></td>
            </tr>
            <tr>
                <td><b>Status</b></td>
                <td><?= strtoupper($report['status']) ?></td>
            </tr>
            <tr>
                <td><b>Description</b></td>
                <td><?= $report['description'] ?></td>
            </tr>
        </table>

        <?php if (!empty($report['photo'])) { ?>
            <div class="photo">
                <b>Missing Photo:</b><br>
                <?php
                $imgPath = $_SERVER['DOCUMENT_ROOT'] . "/HopeFinder/" . $report['photo'];

                if (!empty($report['photo']) && file_exists($imgPath)) {
                    $type = pathinfo($imgPath, PATHINFO_EXTENSION);
                    $data = base64_encode(file_get_contents($imgPath));
                ?>
                    <img src="data:image/<?= $type ?>;base64,<?= $data ?>" width="200">
                <?php } else {
                    echo "No Image";
                } ?>
            </div>
        <?php } ?>
    </div>

    <!-- POLICE DETAILS -->
    <div class="section">
        <h3>Assigned Police Details</h3>

        <?php if ($police) { ?>
            <table>
                <tr>
                    <td><b>Officer Name</b></td>
                    <td><?= $police['police_name'] ?? 'N/A' ?></td>
                </tr>
                <tr>
                    <td><b>Contact</b></td>
                    <td><?= $police['phone'] ?? 'N/A' ?></td>
                </tr>
                <tr>
                    <td><b>Badge No</b></td>
                    <td><?= $police['badge_number'] ?? 'N/A' ?></td>
                </tr>
                <tr>
                    <td><b>Station</b></td>
                    <td><?= $police['station_name'] ?? 'N/A' ?></td>
                </tr>
            </table>
        <?php } else { ?>
            <p>No police assigned</p>
        <?php } ?>
    </div>

    <!-- DETECTIONS -->
    <div class="section">
        <h3>Detection / Found Locations</h3>

        <table class="det-table">
<tr>
<th>#</th>
<th>Location</th>
<th>Date & Time</th>
<th>Confidence</th>
<th>Image</th>
</tr>

<?php
$i = 1;
while($d = mysqli_fetch_assoc($detections)) {
?>
<tr>

<td><?= $i++ ?></td>

<td><?= $d['address'] ?? 'N/A' ?></td>

<td><?= date("d M Y, h:i A", strtotime($d['timestamp'])) ?></td>

<td><?= $d['confidence'] ?>%</td>

<td>
<?php
$imgName = $d['image'] ?? $d['image_path'] ?? '';
$imgPath = $_SERVER['DOCUMENT_ROOT']."/HopeFinder/".$imgName;

if(!empty($imgName) && file_exists($imgPath)){
    $type = pathinfo($imgPath, PATHINFO_EXTENSION);
    $data = base64_encode(file_get_contents($imgPath));
    echo '<img src="data:image/'.$type.';base64,'.$data.'" width="80">';
} else {
    echo "N/A";
}
?>
</td>

</tr>
<?php
}
?>
</table>
    </div>

    <!-- FOOTER -->
    <div class="footer">
        <div>HopeFinder System</div>
        <div>Confidential Report</div>
    </div>

</body>

</html>