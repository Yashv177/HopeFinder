<?php
/**
 * Single Page User Dashboard
 * All features in one file with sections and modals
 */

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.html');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['fullname'] ?? 'User';

require_once '../Database/Conn_db.php';

// Handle form submission
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_report'])) {
    $missing_name = isset($_POST['missing_name']) ? trim($_POST['missing_name']) : '';
    $age = isset($_POST['age']) ? (int)$_POST['age'] : null;
    $gender = isset($_POST['gender']) ? $_POST['gender'] : null;
    $last_seen_location = isset($_POST['last_seen_location']) ? trim($_POST['last_seen_location']) : '';
    $last_seen_datetime = isset($_POST['last_seen_datetime']) ? $_POST['last_seen_datetime'] : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $contact_number = isset($_POST['contact_number']) ? trim($_POST['contact_number']) : '';
    
    $errors = [];
    if (empty($missing_name)) $errors[] = 'Person name is required';
    if (empty($last_seen_location)) $errors[] = 'Last seen location is required';
    if (empty($last_seen_datetime)) $errors[] = 'Last seen date & time is required';
    if (empty($contact_number)) $errors[] = 'Contact number is required';
    
    $photo_path = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/missing_persons/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        
        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $filename = 'missing_' . time() . '_' . uniqid() . '.' . $ext;
        $target_path = $upload_dir . $filename;
        
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_path)) {
            $photo_path = 'uploads/missing_persons/' . $filename;
        }
    } else {
        $errors[] = 'Photo upload is required';
    }
    
    if (empty($errors)) {
        $status = 'pending';
        
        // Convert datetime-local format (Y-m-d\TH:i) to MySQL DATETIME format (Y-m-d H:i:s)
        $mysql_datetime = date('Y-m-d H:i:s', strtotime($last_seen_datetime));
        
        $stmt = $conn->prepare("INSERT INTO missing_reports 
            (user_id, missing_name, contact_number, age, gender, last_seen_location, last_seen_datetime, description, photo, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param('isssssssss', 
            $user_id, $missing_name, $contact_number, $age, $gender, 
            $last_seen_location, $mysql_datetime, $description, $photo_path, $status);
        
        if ($stmt->execute()) {
            $report_id = $stmt->insert_id;
            $stmt->close();

            require_once '../api/helpers/notification_helper.php';

    sendNotification($user_id, "user", "Report submitted successfully");
    sendNotification(null, "admin", "New report added");
            
            // Post/Redirect/Get pattern to prevent resubmission on refresh
            header('Location: UserD.php?success=1&id=' . $report_id);
            exit;
        } else {
            $errors[] = 'Failed to submit report. Please try again.';
            $stmt->close();
        }
    }
    
    if (!empty($errors)) {
        $message = implode('<br>', $errors);
        $messageType = 'danger';
    }
}

// Handle success redirect
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $message = "Report submitted successfully! Your Report ID is #R" . ($_GET['id'] ?? '');
    $messageType = 'success';
}

// Get statistics
$stats = ['total' => 0, 'pending' => 0, 'verified' => 0, 'matched' => 0];
$result = $conn->query("SELECT COUNT(*) as total FROM missing_reports WHERE user_id = $user_id");
if ($row = $result->fetch_assoc()) $stats['total'] = $row['total'];
$result = $conn->query("SELECT COUNT(*) as total FROM missing_reports WHERE user_id = $user_id AND status = 'pending'");
if ($row = $result->fetch_assoc()) $stats['pending'] = $row['total'];
$result = $conn->query("SELECT COUNT(*) as total FROM missing_reports WHERE user_id = $user_id AND status IN ('verified', 'assigned')");
if ($row = $result->fetch_assoc()) $stats['verified'] = $row['total'];
$result = $conn->query("SELECT COUNT(*) as total FROM missing_reports WHERE user_id = $user_id AND status = 'closed'");
if ($row = $result->fetch_assoc()) $stats['matched'] = $row['total'];

// Get all user reports
$reports = [];
$result = $conn->query("SELECT * FROM missing_reports WHERE user_id = $user_id ORDER BY created_at DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $reports[] = $row;
    }
}

// Get notifications
$notifications = [];
$result = $conn->query("
    SELECT * FROM notifications 
    WHERE (target='user' AND user_id=$user_id)
       OR target='all'
    ORDER BY created_at DESC 
    LIMIT 10
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>HopeFinder — User Dashboard</title>
  <link rel="icon" href="../logo.png" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet"/>
  <link href="../assets/css/police-dashboard-modern.css" rel="stylesheet"/>
  <style>
    .dashboard-section { display: none; }
    .dashboard-section.active { display: block; }
    .form-card { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 16px; padding: 30px; }
.form-label { color: #e5e5e7 !important; font-weight: 500; margin-bottom: 8px; }
    .form-control, .form-select {
      background: #1f1f28 !important; 
      border: 1px solid #40404f !important;
      border-radius: 10px; 
      color: #ffffff !important; 
      padding: 12px 16px !important;
    }
    .form-control::placeholder { color: #a0a0a5 !important; }
    .form-control:focus, .form-select:focus {
      background: #1f1f28 !important; 
      border-color: #6366f1 !important; 
      box-shadow: 0 0 0 4px rgba(99,102,241,0.2) !important;
      color: #ffffff !important;
    }
    .photo-upload {
      border: 2px dashed var(--border-color); border-radius: 12px; padding: 40px; text-align: center; cursor: pointer; transition: 0.3s;
    }
    .photo-upload:hover { border-color: var(--primary); background: rgba(99,102,241,0.05); }
    .photo-upload.has-image { border-style: solid; border-color: var(--accent); }
    .photo-preview { max-width: 100%; max-height: 200px; border-radius: 8px; margin-top: 15px; display: none; }
    .photo-upload.has-image .photo-preview { display: block; }
    .section-title {
      color: var(--text-primary); font-size: 1.1rem; font-weight: 600; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid var(--border-color);
    }
    .report-photo { width: 50px; height: 50px; border-radius: 10px; object-fit: cover; }
    .match-card { background: linear-gradient(135deg, rgba(99,102,241,0.1), rgba(118,75,162,0.1)); border: 1px solid rgba(99,102,241,0.2); border-radius: 16px; padding: 20px; margin-bottom: 15px; }
    .match-photo { width: 80px; height: 80px; border-radius: 12px; object-fit: cover; }
    .timeline { position: relative; padding-left: 30px; }
    .timeline::before { content: ''; position: absolute; left: 8px; top: 0; bottom: 0; width: 2px; background: var(--border-color); }
    .timeline-item { position: relative; padding-bottom: 20px; }
    .timeline-icon {
      position: absolute; left: -30px; width: 20px; height: 20px; border-radius: 50%; background: var(--card-bg); border: 2px solid var(--border-color);
    }
    .timeline-item.completed .timeline-icon { background: var(--primary); border-color: var(--primary); }
    .notif-card { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 12px; padding: 15px; margin-bottom: 10px; }
  </style>
</head>
<body>
  <!-- SIDEBAR -->
  <nav id="sidebar">
    <div class="sidebar-header">
      <i class="bi bi-search-heart"></i>
      <div><strong class="sidebar-text">HopeFinder</strong><div>User Dashboard</div></div>
      <span class="online-dot"></span>
    </div>
    <div class="sidebar-profile">
      <img src="https://ui-avatars.com/api/?name=<?= urlencode($user_name) ?>&background=667eea&color=fff" class="rounded-circle">
      <div><div><?= htmlspecialchars($user_name) ?></div><span>● Online</span></div>
    </div>
    <ul class="nav">
      <li><a href="#" class="sidebar-action active" onclick="showSection('overview')"><span class="nav-icon"><i class="bi bi-speedometer2"></i></span><span class="sidebar-text">Dashboard</span></a></li>
      <li><a href="#" class="sidebar-action" onclick="showSection('add-report')"><span class="nav-icon"><i class="bi bi-person-plus"></i></span><span class="sidebar-text">Report Missing</span></a></li>
      <li><a href="#" class="sidebar-action" onclick="showSection('my-reports')"><span class="nav-icon"><i class="bi bi-folder-check"></i></span><span class="sidebar-text">My Reports</span></a></li>
      <li><a href="#" class="sidebar-action" onclick="showSection('matches')"><span class="nav-icon"><i class="bi bi-cpu"></i></span><span class="sidebar-text">AI Matches</span></a></li>
      <li><a href="#" class="sidebar-action" onclick="showSection('notifications')"><span class="nav-icon"><i class="bi bi-bell-fill"></i></span><span class="sidebar-text">Notifications</span></a></li>
    </ul>
    <div class="sidebar-logout">
      <a href="Logout.php"><span class="nav-icon"><i class="bi bi-box-arrow-right"></i></span><span class="sidebar-text">Logout</span></a>
    </div>
  </nav>

  <!-- MAIN CONTENT -->
  <main id="content">
    <div class="topbar">
      <div class="topbar-left">
        <button id="sidebarCollapse" class="sidebar-toggle"><span class="toggle-icon"><span></span></span></button>
        <div class="topbar-title"><h5>Welcome — <span><?= htmlspecialchars($user_name) ?></span></h5></div>
      </div>
      <div class="topbar-right">
        <div class="user-profile">
          <img src="https://ui-avatars.com/api/?name=<?= urlencode($user_name) ?>&background=667eea&color=fff" class="user-avatar">
          <span class="user-name"><?= htmlspecialchars($user_name) ?></span>
        </div>
      </div>
    </div>

    <!-- 1. OVERVIEW SECTION -->
    <section id="overview" class="dashboard-section active">
      <h2>Dashboard Overview</h2>
      <p>Track your reports and help find missing persons.</p>
      
      <?php if ($message && $messageType === 'success'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          <?= $message ?><button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>
      
      <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
          <div class="stat-card">
            <div class="stat-label">Total Reports</div>
            <div class="stat-value"><?= $stats['total'] ?></div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-card">
            <div class="stat-label">Pending</div>
            <div class="stat-value"><?= $stats['pending'] ?></div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-card">
            <div class="stat-label">Verified</div>
            <div class="stat-value"><?= $stats['verified'] ?></div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-card">
            <div class="stat-label">Found/Matched</div>
            <div class="stat-value"><?= $stats['matched'] ?></div>
          </div>
        </div>
      </div>
      
      <div class="row g-3">
        <div class="col-lg-8">
<div class="chart-container" style="background: #16161e; color: #ffffff;">
            <h5 style="color: #ffffff;"><i class="bi bi-clock me-2"></i>Recent Reports</h5>
            <?php if (empty($reports)): ?>
              <div class="text-center py-4 text-muted">
                <i class="bi bi-inbox fs-1"></i>
                <p class="mt-2">No reports yet. <a href="#" onclick="showSection('add-report')" style="color:var(--primary-light);">Submit your first report</a></p>
              </div>
            <?php else: ?>
              <div class="table-responsive">
                <table class="table" style="background: #1a1a24; color: #ffffff;">
                  <thead style="background: #2a2a3a; color: #ffffff;"><tr><th style="color: #ffffff;">Photo</th><th style="color: #ffffff;">ID</th><th style="color: #ffffff;">Name</th><th style="color: #ffffff;">Status</th><th style="color: #ffffff;">Date</th><th style="color: #ffffff;">Action</th></tr></thead>
                  <tbody>
                    <?php foreach (array_slice($reports, 0, 5) as $r): ?>
                      <tr>
                        <td><img src="<?= $r['photo'] ? '../' . htmlspecialchars($r['photo']) : 'https://via.placeholder.com/50' ?>" class="report-photo"></td>
                        <td><strong>#R<?= $r['report_id'] ?></strong></td>
                        <td><?= htmlspecialchars($r['missing_name']) ?></td>
                        <td><span class="badge bg-<?= $r['status'] == 'pending' ? 'warning' : ($r['status'] == 'verified' ? 'primary' : 'success') ?>"><?= ucfirst($r['status']) ?></span></td>
                        <td><?= date('M d, Y', strtotime($r['created_at'])) ?></td>
                        <td><button class="btn btn-sm btn-outline-info" onclick="viewReport(<?= $r['report_id'] ?>)"><i class="bi bi-eye"></i></button></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
          </div>
        </div>
        <div class="col-lg-4">
          <div class="chart-container">
            <h5><i class="bi bi-bell me-2"></i>Recent Notifications</h5>
            <?php if (empty($notifications)): ?>
              <p class="text-muted text-center py-3">No notifications</p>
            <?php else: ?>
              <?php foreach (array_slice($notifications, 0, 3) as $n): ?>
                <div class="notif-card">
                  <p class="mb-1"><?= htmlspecialchars($n['message']) ?></p>
                  <small class="text-muted"><?= date('M d, h:i A', strtotime($n['created_at'])) ?></small>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </section>

    <!-- 2. ADD REPORT SECTION -->
    <section id="add-report" class="dashboard-section">
      <h2>Report Missing Person</h2>
      <p>Provide details about the missing person.</p>
      
      <?php if ($message && $messageType === 'danger'): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <?= $message ?><button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>
      
      <div class="form-card">
        <form method="POST" enctype="multipart/form-data">
          <input type="hidden" name="add_report" value="1">
          <h5 class="section-title"><i class="bi bi-person me-2"></i>Missing Person Information</h5>
          <div class="row g-3 mb-4">
            <div class="col-md-6">
              <label class="form-label">Full Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="missing_name" placeholder="Enter name" required value="<?= htmlspecialchars($_POST['missing_name'] ?? '') ?>">
            </div>
            <div class="col-md-3">
              <label class="form-label">Age</label>
              <input type="number" class="form-control" name="age" placeholder="Age" value="<?= htmlspecialchars($_POST['age'] ?? '') ?>">
            </div>
            <div class="col-md-3">
              <label class="form-label">Gender</label>
              <select class="form-select" name="gender">
                <option value="">Select</option>
                <option value="male" <?= (isset($_POST['gender']) && $_POST['gender'] == 'male') ? 'selected' : '' ?>>Male</option>
                <option value="female" <?= (isset($_POST['gender']) && $_POST['gender'] == 'female') ? 'selected' : '' ?>>Female</option>
              </select>
            </div>
          </div>
          <div class="row g-3 mb-4">
            <div class="col-md-6">
              <label class="form-label">Last Seen Location <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="last_seen_location" placeholder="Address or place" required value="<?= htmlspecialchars($_POST['last_seen_location'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Last Seen Date & Time <span class="text-danger">*</span></label>
              <input type="datetime-local" class="form-control" name="last_seen_datetime" required value="<?= htmlspecialchars($_POST['last_seen_datetime'] ?? '') ?>">
            </div>
          </div>
          <div class="mb-4">
            <label class="form-label">Description</label>
            <textarea class="form-control" name="description" rows="3" placeholder="Appearance, clothing, distinguishing marks..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
          </div>
          
          <h5 class="section-title"><i class="bi bi-camera me-2"></i>Photo <span class="text-danger">*</span></h5>
          <div class="mb-4">
            <div class="photo-upload" id="photoUpload" onclick="document.getElementById('photo').click()">
              <input type="file" class="d-none" id="photo" name="photo" accept="image/*" required onchange="previewPhoto(this)">
              <i class="bi bi-cloud-upload fs-1 text-muted"></i>
              <p class="mt-2 mb-0">Click to upload photo</p>
              <img id="photoPreview" class="photo-preview">
            </div>
          </div>
          
          <h5 class="section-title"><i class="bi bi-phone me-2"></i>Contact Information</h5>
          <div class="row g-3 mb-4">
            <div class="col-md-6">
              <label class="form-label">Contact Number <span class="text-danger">*</span></label>
              <input type="tel" class="form-control" name="contact_number" placeholder="Contact phone number" required value="<?= htmlspecialchars($_POST['contact_number'] ?? '') ?>">
            </div>
          </div>
          
          <button type="submit" class="btn btn-primary"><i class="bi bi-send me-2"></i>Submit Report</button>
        </form>
      </div>
    </section>

    <!-- 3. MY REPORTS SECTION -->
    <section id="my-reports" class="dashboard-section">
      <h2>My Reports</h2>
      <p>View and track all your submitted reports.</p>
      
      <div class="form-card">
        <div class="row g-3 mb-4">
          <div class="col-md-6">
            <input type="text" class="form-control" id="reportSearch" placeholder="Search by name or location..." onkeyup="filterReports()">
          </div>
          <div class="col-md-3">
            <select class="form-select" id="statusFilter" onchange="filterReports()">
              <option value="">All Status</option>
              <option value="pending">Pending</option>
              <option value="verified">Verified</option>
              <option value="closed">Found/Matched</option>
            </select>
          </div>
        </div>
        
        <div class="table-responsive">
          <table class="table">
            <thead><tr><th>Photo</th><th>ID</th><th>Name</th><th>Location</th><th>Status</th><th>Date</th><th>Action</th></tr></thead>
            <tbody id="reportsTableBody">
              <?php foreach ($reports as $r): ?>
                <tr data-status="<?= $r['status'] ?>" data-name="<?= strtolower($r['missing_name']) ?>" data-location="<?= strtolower($r['last_seen_location']) ?>">
                  <td><img src="<?= $r['photo'] ? '../' . htmlspecialchars($r['photo']) : 'https://via.placeholder.com/50' ?>" class="report-photo"></td>
                  <td><strong>#R<?= $r['report_id'] ?></strong></td>
                  <td><?= htmlspecialchars($r['missing_name']) ?></td>
                  <td><?= htmlspecialchars($r['last_seen_location']) ?></td>
                  <td><span class="badge bg-<?= $r['status'] == 'pending' ? 'warning' : ($r['status'] == 'verified' ? 'primary' : 'success') ?>"><?= $r['status'] == 'closed' ? 'Found' : ucfirst($r['status']) ?></span></td>
                  <td><?= date('M d, Y', strtotime($r['created_at'])) ?></td>
                  <td><button class="btn btn-sm btn-outline-info" onclick="viewReport(<?= $r['report_id'] ?>)"><i class="bi bi-eye"></i></button></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <!-- 4. AI MATCHES SECTION -->
    <section id="matches" class="dashboard-section">
      <h2>AI Match Results</h2>
      <p>View potential matches found by our AI system.</p>
      
      <?php
      $matchedReports = [];
      foreach ($reports as $r) {
        if ($r['status'] == 'closed') $matchedReports[] = $r;
      }
      ?>
      
      <?php if (empty($matchedReports)): ?>
        <div class="form-card text-center py-5">
          <i class="bi bi-search fs-1 text-muted"></i>
          <h4 class="mt-3">No Matches Found</h4>
          <p class="text-muted">AI matching is in progress. Check back later.</p>
        </div>
      <?php else: ?>
        <?php foreach ($matchedReports as $r): ?>
          <div class="match-card">
            <div class="row g-3 align-items-center">
              <div class="col-md-8">
                <h5><?= htmlspecialchars($r['missing_name']) ?> <span class="badge bg-success">Found/Matched</span></h5>
                <p class="mb-1"><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($r['last_seen_location']) ?></p>
                <p class="mb-0 text-muted">Reported: <?= date('M d, Y', strtotime($r['created_at'])) ?></p>
              </div>
              <div class="col-md-4 text-end">
                <button class="btn btn-outline-primary" onclick="viewReport(<?= $r['report_id'] ?>)">
                  <i class="bi bi-eye me-1"></i>View Details
                </button>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </section>

    <!-- 5. NOTIFICATIONS SECTION -->
    <section id="notifications" class="dashboard-section">
      <h2>Notifications</h2>
      <p>Stay updated on your reports and matches.</p>
      
      <div class="form-card">
        <?php if (empty($notifications)): ?>
          <p class="text-muted text-center py-4">No notifications</p>
        <?php else: ?>
          <?php foreach ($notifications as $n): ?>
            <div class="notif-card">
              <p class="mb-1"><?= htmlspecialchars($n['message']) ?></p>
              <small class="text-muted"><?= date('M d, Y h:i A', strtotime($n['created_at'])) ?></small>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </section>
  </main>

  <!-- REPORT DETAIL MODAL -->
  <div class="modal fade" id="reportModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
      <div class="modal-content" style="background: #1a1a24; color: #ffffff;">
        <div class="modal-header">
          <h5 class="modal-title">Report Details</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body" id="reportModalBody">
          Loading...
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../assets/js/sidebar-toggle.js"></script>
  <script>
    function showSection(id) {
      document.querySelectorAll('.dashboard-section').forEach(s => s.classList.remove('active'));
      document.getElementById(id).classList.add('active');
      document.querySelectorAll('.sidebar-action').forEach(a => a.classList.remove('active'));
      event.currentTarget.classList.add('active');
    }
    
    function previewPhoto(input) {
      const area = document.getElementById('photoUpload');
      const preview = document.getElementById('photoPreview');
      if (input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
          preview.src = e.target.result;
          area.classList.add('has-image');
        }
        reader.readAsDataURL(input.files[0]);
      }
    }
    
    function filterReports() {
      const search = document.getElementById('reportSearch').value.toLowerCase();
      const status = document.getElementById('statusFilter').value;
      document.querySelectorAll('#reportsTableBody tr').forEach(row => {
        const matchesSearch = search === '' || row.dataset.name.includes(search) || row.dataset.location.includes(search);
        const matchesStatus = status === '' || row.dataset.status === status;
        row.style.display = (matchesSearch && matchesStatus) ? '' : 'none';
      });
    }
    
    function viewReport(id) {
      const modal = new bootstrap.Modal(document.getElementById('reportModal'));
      document.getElementById('reportModalBody').innerHTML = '<div class="text-center py-4"><div class="spinner-border"></div></div>';
      modal.show();
      
      // Fetch report details
      fetch('getReportDetails.php?id=' + id)
        .then(r => r.text())
        .then(html => {
          document.getElementById('reportModalBody').innerHTML = html;
        })
        .catch(() => {
          document.getElementById('reportModalBody').innerHTML = '<p class="text-danger">Error loading report</p>';
        });
    }
  </script>
</body>
</html>

