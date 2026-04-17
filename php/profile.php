<?php
/**
 * User Profile Page
 * Allows users to view and edit their profile
 */

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['fullname'] ?? 'User';
$user_email = $_SESSION['email'] ?? '';

require_once '../Database/Conn_db.php';

// Get user details
$user = null;
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $user = $row;
}
$stmt->close();

$message = '';
$messageType = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $fullname = isset($_POST['fullname']) ? trim($_POST['fullname']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $mobile_number = isset($_POST['mobile_number']) ? trim($_POST['mobile_number']) : '';
    
    if (!empty($fullname)) {
        $stmt = $conn->prepare("UPDATE users SET fullname = ?, phone = ?, mobile_number = ? WHERE user_id = ?");
        $stmt->bind_param('sssi', $fullname, $phone, $mobile_number, $user_id);
        if ($stmt->execute()) {
            $_SESSION['fullname'] = $fullname;
            $user_name = $fullname;
            $message = 'Profile updated successfully!';
            $messageType = 'success';
            // Refresh user data
            $user['fullname'] = $fullname;
            $user['phone'] = $phone;
            $user['mobile_number'] = $mobile_number;
        } else {
            $message = 'Failed to update profile.';
            $messageType = 'danger';
        }
        $stmt->close();
    } else {
        $message = 'Name is required.';
        $messageType = 'danger';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>My Profile - HopeFinder</title>
  <link rel="icon" href="../logo.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet"/>
  <link href="../Css/Dashboard.css" rel="stylesheet"/>
  <style>
    .profile-card {
      background: #1a1a2e;
      border: 1px solid rgba(255,255,255,0.1);
      border-radius: 16px;
      padding: 30px;
    }
    .profile-avatar {
      width: 120px;
      height: 120px;
      border-radius: 50%;
      object-fit: cover;
      border: 4px solid #667eea;
      margin-bottom: 20px;
    }
    .form-label {
      color: rgba(255,255,255,0.7);
      font-weight: 500;
    }
    .form-control {
      background: rgba(255,255,255,0.05);
      border: 1px solid rgba(255,255,255,0.1);
      border-radius: 10px;
      color: #fff;
      padding: 12px 16px;
    }
    .form-control:focus {
      background: rgba(255,255,255,0.08);
      border-color: #667eea;
      box-shadow: 0 0 0 4px rgba(102,126,234,0.1);
      color: #fff;
    }
    .info-item {
      padding: 15px 0;
      border-bottom: 1px solid rgba(255,255,255,0.05);
    }
    .info-item:last-child {
      border-bottom: none;
    }
    .info-label {
      color: rgba(255,255,255,0.5);
      font-size: 0.85rem;
      margin-bottom: 5px;
    }
    .info-value {
      color: #fff;
      font-size: 1rem;
    }
  </style>
</head>
<body>

  <!-- SIDEBAR -->
  <nav id="sidebar">
    <div class="sidebar-header">
      <i class="bi bi-search-heart" style="color: #667eea;"></i>
      <span class="sidebar-text">HopeFinder</span>
    </div>
    
    <div class="sidebar-profile">
      <img src="https://ui-avatars.com/api/?name=<?= urlencode($user_name) ?>&background=667eea&color=fff" class="rounded-circle">
      <div>
        <div><?= htmlspecialchars($user_name) ?></div>
        <span><i class="bi bi-circle-fill text-success" style="font-size: 8px;"></i> Online</span>
      </div>
    </div>
    
    <ul class="nav flex-column">
      <li><a href="UserD.php"><i class="bi bi-speedometer2"></i><span class="sidebar-text">Dashboard</span></a></li>
      <li><a href="add-missing-report.php"><i class="bi bi-person-plus"></i><span class="sidebar-text">Report Missing</span></a></li>
      <li><a href="search-missing.php"><i class="bi bi-search"></i><span class="sidebar-text">Search</span></a></li>
      <li><a href="my-reports.php"><i class="bi bi-folder-check"></i><span class="sidebar-text">My Reports</span></a></li>
      <li><a href="notifications.php"><i class="bi bi-bell-fill"></i><span class="sidebar-text">Notifications</span></a></li>
      <li><a href="profile.php" class="active"><i class="bi bi-person-circle"></i><span class="sidebar-text">Profile</span></a></li>
      <li><a href="Logout.php"><i class="bi bi-box-arrow-right"></i><span class="sidebar-text">Logout</span></a></li>
    </ul>
  </nav>

  <!-- MAIN CONTENT -->
  <main id="content">
    <div class="topbar">
      <button id="sidebarCollapse" class="sidebar-toggle"><i class="bi bi-list"></i></button>
      <div class="topbar-right">
        <div class="user-profile">
          <img src="https://ui-avatars.com/api/?name=<?= urlencode($user_name) ?>&background=667eea&color=fff" class="user-avatar" alt="User">
          <span class="user-name"><?= htmlspecialchars($user_name) ?></span>
        </div>
      </div>
    </div>

    <div class="dashboard-content">
      <h2 class="mb-4">My Profile</h2>
      
      <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
          <?= $message ?>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>
      
      <div class="row g-4">
        <div class="col-md-4">
          <div class="profile-card text-center">
            <img src="https://ui-avatars.com/api/?name=<?= urlencode($user_name) ?>&background=667eea&color=fff&size=240" 
                 class="profile-avatar" alt="Profile">
            <h4 class="text-white mb-1"><?= htmlspecialchars($user_name) ?></h4>
            <p class="text-muted mb-3"><?= htmlspecialchars($user['role'] ?? 'public') ?></p>
            <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Verified</span>
          </div>
        </div>
        
        <div class="col-md-8">
          <div class="profile-card">
            <h5 class="mb-4">Account Information</h5>
            
            <div class="info-item">
              <div class="info-label">Email</div>
              <div class="info-value"><?= htmlspecialchars($user['email'] ?? '') ?></div>
            </div>
            <div class="info-item">
              <div class="info-label">Role</div>
              <div class="info-value text-capitalize"><?= htmlspecialchars($user['role'] ?? 'Public User') ?></div>
            </div>
            <div class="info-item">
              <div class="info-label">Mobile Number</div>
              <div class="info-value"><?= htmlspecialchars($user['mobile_number'] ?? 'Not set') ?></div>
            </div>
            <div class="info-item">
              <div class="info-label">Member Since</div>
              <div class="info-value"><?= date('F d, Y', strtotime($user['created_at'])) ?></div>
            </div>
            <div class="info-item">
              <div class="info-label">Account Status</div>
              <div class="info-value"><span class="badge bg-success"><?= ucfirst($user['status'] ?? 'active') ?></span></div>
            </div>
          </div>
          
          <div class="profile-card mt-4">
            <h5 class="mb-4">Update Profile</h5>
            <form method="POST">
              <input type="hidden" name="update_profile" value="1">
              <div class="mb-3">
                <label class="form-label">Full Name</label>
                <input type="text" class="form-control" name="fullname" 
                       value="<?= htmlspecialchars($user['fullname'] ?? '') ?>" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Phone Number</label>
                <input type="tel" class="form-control" name="phone" 
                       value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
              </div>
              <div class="mb-3">
                <label class="form-label">Mobile Number</label>
                <input type="tel" class="form-control" name="mobile_number" 
                       placeholder="Enter mobile number"
                       value="<?= htmlspecialchars($user['mobile_number'] ?? '') ?>">
              </div>
              <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-lg me-2"></i>Save Changes
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../JavaScript/Dashboard.js"></script>
</body>
</html>

