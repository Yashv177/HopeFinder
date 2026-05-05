Mar 18, 2026
<?php
/**
 * Get Report Details for User Dashboard Modal
 * Returns HTML content for report details modal
 */

session_start();

if (!isset($_SESSION['user_id'])) {
    echo '<p class="text-danger">Session expired. Please login again.</p>';
    exit;
}

$user_id = $_SESSION['user_id'];

require_once '../Database/Conn_db.php';

$report_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($report_id <= 0) {
    echo '<p class="text-danger">Invalid report ID</p>';
    exit;
}

// Fetch report details (only for user's own reports)
$query = "
    SELECT
        r.*,
        u.fullname as reporter_name,
        u.email as reporter_email,
        p.police_id,
        p.fullname as police_name,
        p.badge_number as police_badge,
        p.station_name as police_station,
        p.district as police_district,
        p.state as police_state,
        a.assigned_at
    FROM missing_reports r
    LEFT JOIN users u ON r.user_id = u.user_id
    LEFT JOIN report_assignments a ON r.report_id = a.report_id
    LEFT JOIN police p ON a.police_id = p.police_id
    WHERE r.report_id = ? AND r.user_id = ?
";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 'ii', $report_id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result || mysqli_num_rows($result) === 0) {
    echo '<p class="text-danger">Report not found or access denied</p>';
    exit;
}

$report = mysqli_fetch_assoc($result);

// Format status badge
$statusBadge = '';
switch ($report['status']) {
    case 'pending':
        $statusBadge = '<span class="badge bg-warning text-dark">Pending Review</span>';
        break;
    case 'verified':
        $statusBadge = '<span class="badge bg-primary">Verified</span>';
        break;
    case 'assigned':
        $statusBadge = '<span class="badge bg-info">Assigned to Officer</span>';
        break;
    case 'closed':
        $statusBadge = '<span class="badge bg-success">Found/Matched</span>';
        break;
    default:
        $statusBadge = '<span class="badge bg-secondary">' . ucfirst($report['status']) . '</span>';
}

// Format dates
$createdDate = date('M d, Y', strtotime($report['created_at']));
$lastSeenDate = date('M d, Y h:i A', strtotime($report['last_seen_datetime']));

// Generate HTML response
?>
<div class="row">
    <div class="col-md-4 text-center mb-4">
        <img src="<?= $report['photo'] ? '../' . htmlspecialchars($report['photo']) : 'https://via.placeholder.com/300' ?>"
             class="img-fluid rounded shadow" style="max-width: 100%; max-height: 300px; object-fit: cover;" alt="Missing Person">
        <p class="mt-2 text-muted small">Report #R<?= $report['report_id'] ?></p>
    </div>

    <div class="col-md-8">
        <div class="d-flex justify-content-between align-items-start mb-3">
            <h4 class="mb-0"><?= htmlspecialchars($report['missing_name']) ?></h4>
            <?= $statusBadge ?>
        </div>

        <!-- Basic Information -->
        <div class="card mb-3" style="background: #1a1a24; border: 1px solid #40404f; color: #ffffff;">
            <div class="card-header" style="background: rgba(99,102,241,0.1); border-bottom: 1px solid rgba(99,102,241,0.2);">
                <h6 class="mb-0"><i class="bi bi-person me-2"></i>Missing Person Information</h6>
            </div>
        <div class="card-body" style="color: #ffffff;">
                <div class="row g-3">
                    <div class="col-md-6">
                        <strong style="color: #ffffff;">Age:</strong> <?= $report['age'] ? htmlspecialchars($report['age']) . ' years' : 'Not specified' ?>
                    </div>
                    <div class="col-md-6">
                        <strong>Gender:</strong> <?= $report['gender'] ? ucfirst(htmlspecialchars($report['gender'])) : 'Not specified' ?>
                    </div>
                    <div class="col-12">
                        <strong>Last Seen:</strong> <?= htmlspecialchars($report['last_seen_location']) ?> on <?= $lastSeenDate ?>
                    </div>
                    <?php if ($report['description']): ?>
                    <div class="col-12">
                        <strong style="color: #ffffff;">Description:</strong><br>
                        <span style="color: #e5e5e7;"><?= nl2br(htmlspecialchars($report['description'])) ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Contact Information -->
        <div class="card mb-3" style="background: #1a1a24; border: 1px solid #40404f; color: #ffffff;">
            <div class="card-header" style="background: rgba(34,197,94,0.1); border-bottom: 1px solid rgba(34,197,94,0.2);">
                <h6 class="mb-0"><i class="bi bi-phone me-2"></i>Contact Information</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <strong>Contact Number:</strong> <?= htmlspecialchars($report['contact_number']) ?>
                    </div>
                    <div class="col-md-6">
                        <strong>Reported On:</strong> <?= $createdDate ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Assigned Police (if any) -->
        <?php if ($report['police_id']): ?>
        <div class="card mb-3" style="background: rgba(255,193,7,0.05); border: 1px solid rgba(255,193,7,0.2);">
            <div class="card-header" style="background: rgba(255,193,7,0.1); border-bottom: 1px solid rgba(255,193,7,0.2);">
                <h6 class="mb-0"><i class="bi bi-shield me-2"></i>Assigned Police Officer</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <strong>Officer Name:</strong> <?= htmlspecialchars($report['police_name']) ?>
                    </div>
                    <div class="col-md-6">
                        <strong>Badge Number:</strong> <?= htmlspecialchars($report['police_badge']) ?>
                    </div>
                    <div class="col-md-6">
                        <strong>Station:</strong> <?= htmlspecialchars($report['police_station']) ?>
                    </div>
                    <div class="col-md-6">
                        <strong>Assigned On:</strong> <?= date('M d, Y', strtotime($report['assigned_at'])) ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Officer Remarks (if any) -->
        <?php if (!empty($report['officer_remarks'])): ?>
        <div class="card mb-3" style="background: rgba(220,53,69,0.05); border: 1px solid rgba(220,53,69,0.2);">
            <div class="card-header" style="background: rgba(220,53,69,0.1); border-bottom: 1px solid rgba(220,53,69,0.2);">
                <h6 class="mb-0"><i class="bi bi-chat-quote me-2"></i>Officer Remarks</h6>
            </div>
            <div class="card-body">
                <p class="mb-0 text-muted"><?= nl2br(htmlspecialchars($report['officer_remarks'])) ?></p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Status Timeline -->
        <div class="card" style="background: #1a1a24; color: #ffffff; border: 1px solid #40404f;">
<div class="card-header" style="background: #2a2a3a; color: #ffffff; border-bottom: 1px solid #40404f;">
                <h6 class="mb-0"><i class="bi bi-clock-history me-2"></i>Status Timeline</h6>
            </div>
            <div class="card-body">
                <div class="timeline">
                    <div class="timeline-item completed">
                        <div class="timeline-icon"></div>
                        <div class="timeline-content" style="color: #ffffff;">
                            <strong style="color: #ffffff;">Report Submitted</strong><br>
                            <small style="color: #e5e5e7;"><?= $createdDate ?></small>
                        </div>
                    </div>

                    <?php if (in_array($report['status'], ['verified', 'assigned', 'closed'])): ?>
                    <div class="timeline-item completed">
                        <div class="timeline-icon"></div>
                        <div class="timeline-content">
                            <strong>Report Verified</strong><br>
                            <small class="text-muted">Under police investigation</small>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($report['status'] === 'assigned'): ?>
                    <div class="timeline-item completed">
                        <div class="timeline-icon"></div>
                        <div class="timeline-content">
                            <strong>Assigned to Officer</strong><br>
                            <small class="text-muted">Police officer assigned to case</small>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($report['status'] === 'closed'): ?>
                    <div class="timeline-item completed">
                        <div class="timeline-icon"></div>
                        <div class="timeline-content">
                            <strong>Case Resolved</strong><br>
                            <small class="text-muted">Person found or matched</small>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
