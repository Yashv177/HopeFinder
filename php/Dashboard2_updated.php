<?php
require_once __DIR__ . '/../Database/Conn_db.php';
require_once __DIR__ . '/jwt_helper.php';
require_once __DIR__ . '/auth_middleware.php';
require_once __DIR__ . '/database_helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    $payload = AuthMiddleware::checkAuthentication();
    AuthMiddleware::checkRole('admin');
    $admin_name = htmlspecialchars($payload['fullname']);
    $admin_id = $payload['user_id'];
    $admin_email = $payload['email'];
} catch (Exception $e) {
    header('Location: ../Login.html');
    exit();
}

// Log dashboard access
DatabaseHelpers::logActivity($conn, $admin_id, 'dashboard_accessed');
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>HopeFinder — Admin Dashboard</title>

  <!-- Fav / Icons -->
  <link rel="icon" href="../logo.png" />

  <!-- Bootstrap CSS & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

  <!-- Your Custom CSS -->
  <link href="../Css/Dashboard2.css" rel="stylesheet" />

  <style>
    #sidebar {
      background: linear-gradient(180deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
    }

    .card-admin {
      border-radius: 16px;
      padding: 22px;
      color: #fff;
      position: relative;
      overflow: hidden;
      transition: all 0.3s ease;
    }
    
    .card-admin::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, transparent 100%);
      pointer-events: none;
    }
    
    .card-admin:hover {
      transform: translateY(-5px);
      box-shadow: 0 15px 35px rgba(0,0,0,0.3);
    }

    .card-admin .value {
      font-size: 2rem;
      font-weight: 700;
      position: relative;
      z-index: 1;
    }

    .card-admin .small {
      position: relative;
      z-index: 1;
      opacity: 0.9;
    }

    .notification-item {
      padding: 15px;
      background: rgba(255,255,255,0.05);
      border-radius: 10px;
      margin-bottom: 10px;
      border-left: 3px solid transparent;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .notification-item:hover {
      background: rgba(255,255,255,0.08);
    }

    .notification-item.unread {
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
      flex-shrink: 0;
    }

    .notif-icon.match { background: rgba(25,135,84,0.2); color: #198754; }
    .notif-icon.status { background: rgba(13,110,253,0.2); color: #0d6efd; }
    .notif-icon.alert { background: rgba(255,193,7,0.2); color: #ffc107; }
    .notif-icon.general { background: rgba(255,255,255,0.1); color: rgba(255,255,255,0.7); }

    #notificationBadge {
      position: absolute;
      top: -8px;
      right: -8px;
      background: #e94560;
      color: white;
      border-radius: 50%;
      width: 24px;
      height: 24px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 0.75rem;
      font-weight: bold;
    }

    #toastContainer {
      position: fixed;
      top: 20px;
      right: 20px;
      z-index: 9999;
      max-width: 400px;
    }

    .toast {
      margin-bottom: 10px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    }

    @media (max-width:991.98px) {
      #content {
        margin-left: 0 !important;
        max-width: 100vw !important;
      }
    }
  </style>
</head>

<body>

  <!-- SIDEBAR -->
  <nav id="sidebar" aria-label="Admin Sidebar">
    <div class="sidebar-header d-flex align-items-center gap-2">
      <i class="bi bi-shield-lock-fill" style="font-size:1.6rem;color:#9fd6ff;"></i>
      <div class="d-flex flex-column">
        <strong class="sidebar-text">HopeFinder</strong>
        <small class="text-muted" style="font-size:0.75rem;">Admin Panel</small>
      </div>
    </div>

    <ul class="nav flex-column mt-3">
      <li><a href="#admin-overview" class="active"><i class="bi bi-speedometer2"></i> <span class="sidebar-text">Dashboard</span></a></li>
      <li><a href="#ai-matches"><i class="bi bi-brain"></i> <span class="sidebar-text">AI Matches</span></a></li>
      <li><a href="#notifications-section"><i class="bi bi-bell"></i> <span class="sidebar-text">Notifications</span></a></li>
      <li><a href="#activity-logs"><i class="bi bi-journal-text"></i> <span class="sidebar-text">Activity Logs</span></a></li>
      <li><a href="#profile-section"><i class="bi bi-person-lines-fill"></i> <span class="sidebar-text">Profile</span></a></li>
      <li><a href="Logout.php" class="text-danger"><i class="bi bi-box-arrow-right"></i> <span class="sidebar-text">Logout</span></a></li>
    </ul>
  </nav>

  <!-- MAIN CONTENT -->
  <main id="content">
    <!-- TOPBAR -->
    <div class="topbar">
      <div class="topbar-left">
        <button id="sidebarCollapse" class="sidebar-toggle" aria-label="Toggle sidebar">
          <span class="toggle-icon"><span></span></span>
        </button>
        <div class="topbar-title">
          <h5>Welcome Admin — <span><?php echo $admin_name; ?></span></h5>
        </div>
      </div>
      <div class="topbar-right">
        <button class="notification-btn position-relative" data-bs-toggle="modal" data-bs-target="#notificationsModal" title="Notifications">
          <i class="bi bi-bell"></i>
          <span id="notificationBadge" style="display: none;">0</span>
        </button>
        <div class="user-profile">
          <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($admin_name); ?>&background=667eea&color=fff&size=34" class="user-avatar" alt="User">
          <span class="user-name"><?php echo $admin_name; ?></span>
        </div>
      </div>
    </div>

    <!-- Dashboard Overview -->
    <section id="admin-overview" class="py-4">
      <h2 class="fw-bold mb-1">Dashboard Overview</h2>
      <p class="text-muted">Real-time system status and activity.</p>

      <!-- Stat Cards -->
      <div class="row g-4 mb-5">
        <div class="col-lg-3 col-md-6">
          <div class="card-admin" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
            <div class="small fw-semibold mb-1">Unread Notifications</div>
            <div class="value mb-1" id="unreadNotifCount">0</div>
            <small><i class="bi bi-bell-fill"></i> New notifications</small>
          </div>
        </div>
        <div class="col-lg-3 col-md-6">
          <div class="card-admin" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
            <div class="small fw-semibold mb-1">Total Activity Logs</div>
            <div class="value mb-1" id="totalActivityLogs">0</div>
            <small><i class="bi bi-clock-history"></i> Today's activity</small>
          </div>
        </div>
        <div class="col-lg-3 col-md-6">
          <div class="card-admin" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
            <div class="small fw-semibold mb-1">Active Users</div>
            <div class="value mb-1" id="activeUsersCount">0</div>
            <small><i class="bi bi-person-check"></i> Online now</small>
          </div>
        </div>
        <div class="col-lg-3 col-md-6">
          <div class="card-admin" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
            <div class="small fw-semibold mb-1">AI Matches</div>
            <div class="value mb-1" id="aiMatchesCount">0</div>
            <small><i class="bi bi-robot"></i> Pending review</small>
          </div>
        </div>
      </div>
    </section>

    <hr />

    <!-- AI Matches Section -->
    <section id="ai-matches" class="py-4">
      <h4 class="mb-3">AI / Manual Matches</h4>
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead class="table-light">
            <tr>
              <th>Match ID</th>
              <th>Missing</th>
              <th>Found</th>
              <th>Match %</th>
              <th>Status</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody id="aiMatchesTable">
            <tr>
              <td colspan="6" class="text-center py-4 text-muted">
                <i class="bi bi-hourglass-split"></i> Loading AI matches...
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <hr />

    <!-- Notifications Section -->
    <section id="notifications-section" class="py-4">
      <h4 class="mb-3">Notifications Management</h4>
      
      <div class="row">
        <div class="col-md-8">
          <div class="card p-4" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);">
            <h6 class="mb-3">Recent Notifications</h6>
            <div id="recentNotificationsContainer" style="max-height: 400px; overflow-y: auto;">
              <p class="text-muted text-center py-4"><i class="bi bi-inbox"></i> No notifications yet</p>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card p-4" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);">
            <h6 class="mb-3">Send Notification</h6>
            <form id="sendNotificationForm">
              <div class="mb-3">
                <label class="form-label small">Target</label>
                <select id="notifTarget" class="form-select form-select-sm" required>
                  <option value="all">All Users</option>
                  <option value="admin">Admin Only</option>
                  <option value="police">Police Only</option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label small">Message</label>
                <textarea id="notifMessage" class="form-control form-control-sm" rows="4" placeholder="Enter message..." required></textarea>
              </div>
              <button type="submit" class="btn btn-primary btn-sm w-100">
                <i class="bi bi-send"></i> Send Notification
              </button>
            </form>
          </div>
        </div>
      </div>
    </section>

    <hr />

    <!-- Activity Logs Section -->
    <section id="activity-logs" class="py-4">
      <h4 class="mb-3">Activity Logs</h4>
      <div class="table-responsive">
        <table class="table table-sm align-middle">
          <thead class="table-light">
            <tr>
              <th>Time</th>
              <th>Action</th>
              <th>IP Address</th>
              <th>Details</th>
            </tr>
          </thead>
          <tbody id="activityLogsTable">
            <tr>
              <td colspan="4" class="text-center py-4 text-muted">
                <i class="bi bi-hourglass-split"></i> Loading activity logs...
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <hr />

    <!-- Profile Section -->
    <section id="profile-section" class="py-4">
      <h4 class="mb-3">Admin Profile & Settings</h4>
      
      <div class="row">
        <!-- Profile Card -->
        <div class="col-md-4">
          <div class="card p-4" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);">
            <div class="text-center">
              <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($admin_name); ?>&background=667eea&color=fff&size=80" class="rounded-circle mb-3" alt="Profile">
              <h6 id="profileName"><?php echo $admin_name; ?></h6>
              <p class="text-muted small mb-3" id="profileRole">Admin</p>
              <button class="btn btn-primary btn-sm w-100" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                <i class="bi bi-pencil"></i> Edit Profile
              </button>
            </div>
          </div>
        </div>

        <!-- Profile Details & Settings -->
        <div class="col-md-8">
          <div class="card p-4" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);">
            <h6 class="mb-3">Profile Information</h6>
            
            <div class="mb-3">
              <label class="form-label small">Full Name</label>
              <input type="text" class="form-control form-control-sm" id="profileFullname" disabled>
            </div>
            
            <div class="mb-3">
              <label class="form-label small">Email Address</label>
              <input type="email" class="form-control form-control-sm" id="profileEmail" disabled>
            </div>
            
            <div class="row mb-3">
              <div class="col-md-6">
                <label class="form-label small">Phone</label>
                <input type="text" class="form-control form-control-sm" id="profilePhone" disabled>
              </div>
              <div class="col-md-6">
                <label class="form-label small">Mobile</label>
                <input type="text" class="form-control form-control-sm" id="profileMobile" disabled>
              </div>
            </div>

            <div class="d-flex gap-2">
              <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                <i class="bi bi-pencil"></i> Edit Profile
              </button>
              <button class="btn btn-outline-warning btn-sm" data-bs-toggle="modal" data-bs-target="#passwordModal">
                <i class="bi bi-shield-lock"></i> Change Password
              </button>
              <a href="Logout.php" class="btn btn-outline-danger btn-sm">
                <i class="bi bi-box-arrow-right"></i> Logout
              </a>
            </div>
          </div>
        </div>
      </div>
    </section>

    <div style="height: 60px;"></div>
  </main>

  <!-- Notifications Modal -->
  <div class="modal fade" id="notificationsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header border-bottom">
          <h5 class="modal-title">Notifications</h5>
          <div class="d-flex gap-2">
            <button class="btn btn-sm btn-outline-secondary" id="markAllNotificationsBtn" title="Mark all as read">
              <i class="bi bi-check2-all"></i>
            </button>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
        </div>
        <div class="modal-body" style="max-height: 500px; overflow-y: auto;">
          <div id="notificationsContainer">
            <p class="text-center py-4 text-muted"><i class="bi bi-inbox"></i> No notifications</p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Edit Profile Modal -->
  <div class="modal fade" id="editProfileModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header border-bottom">
          <h5 class="modal-title">Edit Profile</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <form id="profileUpdateForm">
            <div class="mb-3">
              <label class="form-label">Full Name</label>
              <input type="text" id="profileFullname" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Phone</label>
              <input type="text" id="profilePhone" class="form-control">
            </div>
            <div class="mb-3">
              <label class="form-label">Mobile Number</label>
              <input type="text" id="profileMobile" class="form-control">
            </div>
            <button type="submit" class="btn btn-primary w-100">Save Changes</button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Password Change Modal -->
  <div class="modal fade" id="passwordModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header border-bottom">
          <h5 class="modal-title">Change Password</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <form id="passwordChangeForm">
            <div class="mb-3">
              <label class="form-label">Current Password</label>
              <input type="password" id="oldPassword" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">New Password</label>
              <input type="password" id="newPassword" class="form-control" minlength="8" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Confirm New Password</label>
              <input type="password" id="confirmPassword" class="form-control" minlength="8" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Change Password</button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Toast Container -->
  <div id="toastContainer"></div>

  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../JavaScript/admin_dashboard.js"></script>

  <script>
    // Auto-load dashboard data on page load
    document.addEventListener('DOMContentLoaded', () => {
      // Additional initialization can be done here
    });

    // Sidebar toggle
    document.getElementById('sidebarCollapse').addEventListener('click', () => {
      const sidebar = document.getElementById('sidebar');
      sidebar.classList.toggle('collapsed');
    });
  </script>
</body>
</html>
