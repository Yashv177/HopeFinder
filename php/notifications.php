<?php
/**
 * Notifications Page
 * Shows notifications for the logged-in user
 */

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['fullname'] ?? 'User';

require_once '../Database/Conn_db.php';

// Get notifications
$notifications = [];
$sql = "SELECT * FROM notifications WHERE target IN ('all', 'user') ORDER BY created_at DESC LIMIT 50";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Notifications - HopeFinder</title>
  <link rel="icon" href="../logo.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet"/>
  <link href="../Css/Dashboard.css" rel="stylesheet"/>
  <style>
    .notif-card {
      background: #1a1a2e;
      border: 1px solid rgba(255,255,255,0.1);
      border-radius: 12px;
      padding: 20px;
      margin-bottom: 15px;
      transition: all 0.3s ease;
    }
    .notif-card:hover {
      background: rgba(255,255,255,0.03);
    }
    .notif-card.unread {
      border-left: 3px solid #667eea;
      background: rgba(102,126,234,0.05);
    }
    .notif-icon {
      width: 45px;
      height: 45px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.2rem;
    }
    .notif-icon.match { background: rgba(25,135,84,0.2); color: #198754; }
    .notif-icon.status { background: rgba(13,110,253,0.2); color: #0d6efd; }
    .notif-icon.alert { background: rgba(255,193,7,0.2); color: #ffc107; }
    .notif-icon.general { background: rgba(255,255,255,0.1); color: rgba(255,255,255,0.7); }
    .notif-time {
      font-size: 0.8rem;
      color: rgba(255,255,255,0.4);
    }
    .empty-state {
      text-align: center;
      padding: 60px 20px;
    }
    .empty-state i {
      font-size: 4rem;
      color: rgba(255,255,255,0.1);
      margin-bottom: 20px;
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
      <li><a href="notifications.php" class="active"><i class="bi bi-bell-fill"></i><span class="sidebar-text">Notifications</span></a></li>
      <li><a href="profile.php"><i class="bi bi-person-circle"></i><span class="sidebar-text">Profile</span></a></li>
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
      <h2 class="mb-4">Notifications</h2>
      
      <?php if (empty($notifications)): ?>
        <div class="empty-state">
          <i class="bi bi-bell-slash"></i>
          <h4 class="text-white">No Notifications</h4>
          <p class="text-muted">You don't have any notifications yet.</p>
        </div>
      <?php else: ?>
        <?php foreach ($notifications as $notif): ?>
          <?php
          // Determine icon type
          $message = strtolower($notif['message'] ?? '');
          if (strpos($message, 'match') !== false || strpos($message, 'found') !== false) {
              $iconClass = 'match';
              $icon = 'bi-heart-fill';
          } elseif (strpos($message, 'verified') !== false || strpos($message, 'status') !== false) {
              $iconClass = 'status';
              $icon = 'bi-check-circle';
          } elseif (strpos($message, 'alert') !== false || strpos($message, 'warning') !== false) {
              $iconClass = 'alert';
              $icon = 'bi-exclamation-triangle';
          } else {
              $iconClass = 'general';
              $icon = 'bi-bell';
          }
          ?>
          <div class="notif-card">
            <div class="d-flex gap-3">
              <div class="notif-icon <?= $iconClass ?>">
                <i class="bi <?= $icon ?>"></i>
              </div>
              <div class="flex-grow-1">
                <p class="mb-1 text-white"><?= htmlspecialchars($notif['message']) ?></p>
                <span class="notif-time"><?= date('M d, Y h:i A', strtotime($notif['created_at'])) ?></span>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../JavaScript/Dashboard.js"></script>
</body>
</html>

