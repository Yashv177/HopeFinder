<?php
/**
 * Add Missing Person Report - Modern UI
 */

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['fullname'] ?? 'User';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once '../Database/Conn_db.php';
    
    $missing_name = isset($_POST['missing_name']) ? trim($_POST['missing_name']) : '';
    $age = isset($_POST['age']) ? (int)$_POST['age'] : null;
    $gender = isset($_POST['gender']) ? $_POST['gender'] : null;
    $last_seen_location = isset($_POST['last_seen_location']) ? trim($_POST['last_seen_location']) : '';
    $last_seen_datetime = isset($_POST['last_seen_datetime']) ? $_POST['last_seen_datetime'] : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $contact_name = isset($_POST['contact_name']) ? trim($_POST['contact_name']) : '';
    $contact_number = isset($_POST['contact_number']) ? trim($_POST['contact_number']) : '';
    $contact_relation = isset($_POST['contact_relation']) ? trim($_POST['contact_relation']) : '';
    
    $errors = [];
    if (empty($missing_name)) $errors[] = 'Person name is required';
    if (empty($last_seen_location)) $errors[] = 'Last seen location is required';
    if (empty($last_seen_datetime)) $errors[] = 'Last seen date & time is required';
    
    $photo_path = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/missing_persons/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        
        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $filename = 'missing_' . time() . '_' . uniqid() . '.' . $ext;
        $target_path = $upload_dir . $filename;
        
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array(strtolower($ext), $allowed_types)) {
            $errors[] = 'Invalid photo format. Only JPG, PNG, GIF allowed.';
        } else if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_path)) {
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
            (user_id, missing_name, contact_number, age, gender, last_seen_location, last_seen_datetime, description, photo, contact_name, contact_relation, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param('isssssssssss', 
            $user_id, $missing_name, $contact_number, $age, $gender, 
            $last_seen_location, $mysql_datetime, $description, $photo_path, 
            $contact_name, $contact_relation, $status);
        
        if ($stmt->execute()) {
            $report_id = $stmt->insert_id;
            $message = "Report submitted successfully! Your Report ID is #R$report_id";
            $messageType = 'success';
            $_POST = [];
        } else {
            $errors[] = 'Failed to submit report. Please try again.';
        }
        $stmt->close();
    }
    
    if (!empty($errors)) {
        $message = implode('<br>', $errors);
        $messageType = 'danger';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>HopeFinder — Report Missing</title>
  <link rel="icon" href="../logo.png" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet"/>
  <link href="../assets/css/police-dashboard-modern.css" rel="stylesheet"/>
  <link href="../assets/css/sidebar-toggle.css" rel="stylesheet"/>
  <style>
    .form-card { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 16px; padding: 30px; }
    .form-label { color: var(--text-secondary); font-weight: 500; margin-bottom: 8px; }
    .form-control, .form-select {
      background: var(--bg-hover); border: 1px solid var(--border-color);
      border-radius: 10px; color: var(--text-primary); padding: 12px 16px;
    }
    .form-control:focus, .form-select:focus {
      background: var(--bg-hover); border-color: var(--primary); box-shadow: 0 0 0 4px rgba(99,102,241,0.1);
    }
    .photo-upload-area {
      border: 2px dashed var(--border-color); border-radius: 12px; padding: 40px;
      text-align: center; cursor: pointer; transition: all 0.3s;
    }
    .photo-upload-area:hover { border-color: var(--primary); background: rgba(99,102,241,0.05); }
    .photo-upload-area.has-image { border-style: solid; border-color: var(--accent); }
    .photo-preview { max-width: 100%; max-height: 200px; border-radius: 8px; margin-top: 15px; display: none; }
    .photo-upload-area.has-image .photo-preview { display: block; }
    .section-title {
      color: var(--text-primary); font-size: 1.1rem; font-weight: 600;
      margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid var(--border-color);
    }
  </style>
</head>
<body>
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
      <li><a href="UserD.php"><span class="nav-icon"><i class="bi bi-speedometer2"></i></span><span class="sidebar-text">Dashboard</span></a></li>
      <li><a href="add-missing-report.php" class="active"><span class="nav-icon"><i class="bi bi-person-plus"></i></span><span class="sidebar-text">Report Missing</span></a></li>
      <li><a href="search-missing.php"><span class="nav-icon"><i class="bi bi-search"></i></span><span class="sidebar-text">Search</span></a></li>
      <li><a href="my-reports.php"><span class="nav-icon"><i class="bi bi-folder-check"></i></span><span class="sidebar-text">My Reports</span></a></li>
      <li><a href="notifications.php"><span class="nav-icon"><i class="bi bi-bell-fill"></i></span><span class="sidebar-text">Notifications</span></a></li>
      <li><a href="profile.php"><span class="nav-icon"><i class="bi bi-person-circle"></i></span><span class="sidebar-text">Profile</span></a></li>
    </ul>
    <div class="sidebar-logout">
      <a href="Logout.php"><span class="nav-icon"><i class="bi bi-box-arrow-right"></i></span><span class="sidebar-text">Logout</span></a>
    </div>
  </nav>

  <main id="content">
    <div class="topbar">
      <div class="topbar-left">
        <button id="sidebarCollapse" class="sidebar-toggle"><span class="toggle-icon"><span></span></span></button>
        <div class="topbar-title"><h5>Report Missing Person</h5></div>
      </div>
      <div class="topbar-right">
        <div class="user-profile">
          <img src="https://ui-avatars.com/api/?name=<?= urlencode($user_name) ?>&background=667eea&color=fff" class="user-avatar">
          <span class="user-name"><?= htmlspecialchars($user_name) ?></span>
        </div>
      </div>
    </div>

    <section id="add-report">
      <h2>Report Missing Person</h2>
      <p>Provide details about the missing person to help us find them.</p>
      
      <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
          <?= $message ?><button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>
      
      <div class="form-card">
        <form method="POST" enctype="multipart/form-data">
          <h5 class="section-title"><i class="bi bi-person me-2"></i>Missing Person Information</h5>
          <div class="row g-3 mb-4">
            <div class="col-md-6">
              <label class="form-label">Full Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="missing_name" placeholder="Enter person's full name" required value="<?= htmlspecialchars($_POST['missing_name'] ?? '') ?>">
            </div>
            <div class="col-md-3">
              <label class="form-label">Age</label>
              <input type="number" class="form-control" name="age" placeholder="Age" min="0" max="150" value="<?= htmlspecialchars($_POST['age'] ?? '') ?>">
            </div>
            <div class="col-md-3">
              <label class="form-label">Gender</label>
              <select class="form-select" name="gender">
                <option value="">Select</option>
                <option value="male" <?= (isset($_POST['gender']) && $_POST['gender'] == 'male') ? 'selected' : '' ?>>Male</option>
                <option value="female" <?= (isset($_POST['gender']) && $_POST['gender'] == 'female') ? 'selected' : '' ?>>Female</option>
                <option value="other" <?= (isset($_POST['gender']) && $_POST['gender'] == 'other') ? 'selected' : '' ?>>Other</option>
              </select>
            </div>
          </div>
          <div class="row g-3 mb-4">
            <div class="col-md-6">
              <label class="form-label">Last Seen Location <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="last_seen_location" placeholder="Enter address or place" required value="<?= htmlspecialchars($_POST['last_seen_location'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Last Seen Date & Time <span class="text-danger">*</span></label>
              <input type="datetime-local" class="form-control" name="last_seen_datetime" required value="<?= htmlspecialchars($_POST['last_seen_datetime'] ?? '') ?>">
            </div>
          </div>
          <div class="mb-4">
            <label class="form-label">Description</label>
            <textarea class="form-control" name="description" rows="4" placeholder="Describe appearance, clothing, distinguishing marks..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
          </div>
          
          <h5 class="section-title"><i class="bi bi-camera me-2"></i>Photo <span class="text-danger">*</span></h5>
          <div class="mb-4">
            <div class="photo-upload-area" id="photoUploadArea" onclick="document.getElementById('photo').click()">
              <input type="file" class="d-none" id="photo" name="photo" accept="image/*" required onchange="previewPhoto(event)">
              <i class="bi bi-cloud-upload fs-1 text-muted"></i>
              <p class="mt-2 mb-0">Click to upload photo</p>
              <small class="text-muted">JPG, PNG, GIF (Max 5MB)</small>
              <img id="photoPreview" class="photo-preview">
            </div>
          </div>
          
          <h5 class="section-title"><i class="bi bi-phone me-2"></i>Contact Information</h5>
          <div class="row g-3 mb-4">
            <div class="col-md-4">
              <label class="form-label">Contact Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="contact_name" placeholder="Contact full name" required value="<?= htmlspecialchars($_POST['contact_name'] ?? $user_name) ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Contact Number <span class="text-danger">*</span></label>
              <input type="tel" class="form-control" name="contact_number" placeholder="Contact phone number" required value="<?= htmlspecialchars($_POST['contact_number'] ?? '') ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Relationship</label>
              <select class="form-select" name="contact_relation">
                <option value="">Select</option>
                <option value="Family" <?= (isset($_POST['contact_relation']) && $_POST['contact_relation'] == 'Family') ? 'selected' : '' ?>>Family</option>
                <option value="Friend" <?= (isset($_POST['contact_relation']) && $_POST['contact_relation'] == 'Friend') ? 'selected' : '' ?>>Friend</option>
                <option value="Other" <?= (isset($_POST['contact_relation']) && $_POST['contact_relation'] == 'Other') ? 'selected' : '' ?>>Other</option>
              </select>
            </div>
          </div>
          
          <div class="d-flex gap-3">
            <button type="submit" class="btn btn-primary"><i class="bi bi-send me-2"></i>Submit Report</button>
            <a href="UserD.php" class="btn btn-outline-secondary"><i class="bi bi-x-lg me-2"></i>Cancel</a>
          </div>
        </form>
      </div>
    </section>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../assets/js/sidebar-toggle.js"></script>
  <script>
    function previewPhoto(event) {
      const file = event.target.files[0];
      const preview = document.getElementById('photoPreview');
      const area = document.getElementById('photoUploadArea');
      if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
          preview.src = e.target.result;
          area.classList.add('has-image');
        }
        reader.readAsDataURL(file);
      }
    }
  </script>
</body>
</html>

