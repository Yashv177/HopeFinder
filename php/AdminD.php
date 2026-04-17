<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>HopeFinder — Admin Dashboard</title>
  <link rel="icon" href="../logo.png" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet"/>
  <link href="../assets/css/police-dashboard-modern.css" rel="stylesheet"/>
  <!-- Sidebar Toggle Fix CSS -->
  <link href="../assets/css/sidebar-toggle.css" rel="stylesheet"/>
  <!-- Section Theme Fix CSS -->
  <link href="../assets/css/section-themes.css" rel="stylesheet"/>
  <!-- Action Buttons CSS -->
  <link href="../assets/css/action-buttons.css" rel="stylesheet"/>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <!-- Modern 3D Animated Charts -->
  <script src="../assets/js/modern-charts.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
  <!-- SIDEBAR -->
  <nav id="sidebar">
    <div class="sidebar-header">
      <i class="bi bi-shield-lock-fill"></i>
      <div>
        <strong class="sidebar-text">HopeFinder Admin</strong>
        <div>Administrator Dashboard</div>
      </div>
      <span class="online-dot"></span>
    </div>

    <!-- Quick Profile -->
    <div class="sidebar-profile">
      <img src="https://ui-avatars.com/api/?name=Administrator&background=dc2626&color=fff&size=40" class="rounded-circle">
      <div>
        <div>Administrator</div>
        <span>● Online</span>
      </div>
    </div>

    <!-- Navigation Links -->
    <ul class="nav">
      <li><a href="#admin-overview" class="sidebar-action active"><span class="nav-icon"><i class="bi bi-speedometer2"></i></span><span class="sidebar-text">Dashboard</span></a></li>
      <li><a href="#user-management" class="sidebar-action"><span class="nav-icon"><i class="bi bi-people-fill"></i></span><span class="sidebar-text">User Management</span></a></li>
      <li><a href="#system-logs" class="sidebar-action"><span class="nav-icon"><i class="bi bi-journal-text"></i></span><span class="sidebar-text">System Logs</span></a></li>
      <li><a href="#reports-analytics" class="sidebar-action"><span class="nav-icon"><i class="bi bi-bar-chart-line"></i></span><span class="sidebar-text">Reports Analytics</span></a></li>
      <li><a href="#security-settings" class="sidebar-action"><span class="nav-icon"><i class="bi bi-shield-check"></i></span><span class="sidebar-text">Security Settings</span></a></li>
    </ul>

    <!-- Bottom Actions (Profile & Logout) -->
    <div class="sidebar-logout">
      <a href="#admin-profile" class="sidebar-action">
        <span class="nav-icon"><i class="bi bi-person-circle"></i></span>
        <span class="sidebar-text">Profile</span>
      </a>
      <a href="Logout.php" class="sidebar-action">
        <span class="nav-icon"><i class="bi bi-box-arrow-right"></i></span>
        <span class="sidebar-text">Logout</span>
      </a>
    </div>
  </nav>

  <!-- MAIN CONTENT -->
  <main id="content">
    <!-- TOPBAR -->
    <div class="topbar">
      <div class="topbar-left">
        <button id="sidebarCollapse" class="sidebar-toggle" aria-label="Toggle sidebar" aria-expanded="true" aria-controls="sidebar">
          <span class="toggle-icon">
            <span></span>
          </span>
        </button>
        <div class="topbar-title">
          <h5>Welcome Administrator — <span>System Admin</span></h5>
          <small>Full System Access | Admin Panel</small>
        </div>
      </div>
      <div class="topbar-right">
        <input class="search-input" placeholder="Search users, reports, logs..." />
        <button class="notification-btn">
          <i class="bi bi-bell"></i>
          <span class="notification-badge">5</span>
        </button>
        <div class="user-profile">
          <img src="https://ui-avatars.com/api/?name=Administrator&background=dc2626&color=fff&size=34" class="user-avatar" alt="Admin">
          <span class="user-name">Administrator</span>
          <i class="bi bi-caret-down-fill user-dropdown"></i>
        </div>
      </div>
    </div>

    <!-- ADMIN OVERVIEW -->
    <section id="admin-overview">
      <h2>Administrator Dashboard</h2>
      <p>Complete system overview and administrative controls.</p>

      <!-- STAT CARDS -->
      <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
          <div class="stat-card">
            <div class="stat-label">Total Users</div>
            <div class="stat-value" id="total-users">1250</div>
            <small>registered users</small>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-card">
            <div class="stat-label">Active Sessions</div>
            <div class="stat-value" id="active-sessions">89</div>
            <small>current sessions</small>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-card">
            <div class="stat-label">Security Alerts</div>
            <div class="stat-value" id="security-alerts">3</div>
            <small>pending review</small>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-card">
            <div class="stat-label">System Health</div>
            <div class="stat-value" id="system-health">98%</div>
            <small>uptime</small>
          </div>
        </div>
      </div>

      <!-- SYSTEM STATUS -->
      <div class="row g-3 mb-3">
        <div class="col-lg-8">
          <div class="chart-container">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <div>
                <h5 style="margin:0;color:var(--text-primary);"><i class="bi bi-activity" style="color:var(--primary-light);margin-right:8px;"></i>System Activity</h5>
                <small style="color:var(--text-secondary);">User logins and system events</small>
              </div>
              <button class="btn btn-outline-secondary btn-sm" onclick="refreshSystemCharts()"><i class="bi bi-arrow-clockwise"></i></button>
            </div>
            <div class="chart-wrapper">
              <canvas id="systemActivityChart"></canvas>
            </div>
          </div>
        </div>

        <div class="col-lg-4">
          <div class="chart-container">
            <h6 style="margin:0;color:var(--text-primary);margin-bottom:12px;"><i class="bi bi-shield-exclamation" style="color:var(--warning);margin-right:8px;"></i>Security Overview</h6>
            <div class="d-flex justify-content-around" style="padding:12px 0;">
              <div class="text-center">
                <div style="font-size:1.25rem;font-weight:700;color:var(--success);">45</div>
                <small style="color:var(--text-muted);font-size:0.75rem;">Passed</small>
              </div>
              <div class="text-center">
                <div style="font-size:1.25rem;font-weight:700;color:var(--warning);">3</div>
                <small style="color:var(--text-muted);font-size:0.75rem;">Warnings</small>
              </div>
              <div class="text-center">
                <div style="font-size:1.25rem;font-weight:700;color:var(--danger);">0</div>
                <small style="color:var(--text-muted);font-size:0.75rem;">Critical</small>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- QUICK ACTIONS -->
      <div class="mb-3 d-flex flex-wrap gap-2">
        <a href="#user-management" class="btn btn-outline-primary"><i class="bi bi-people"></i> Manage Users</a>
        <a href="#system-logs" class="btn btn-outline-info"><i class="bi bi-journal"></i> View Logs</a>
        <a href="#security-settings" class="btn btn-outline-warning"><i class="bi bi-shield"></i> Security</a>
        <button class="btn btn-outline-danger" onclick="systemMaintenance()"><i class="bi bi-tools"></i> Maintenance</button>
      </div>
    </section>

    <!-- USER MANAGEMENT -->
    <section id="user-management">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <h4>User Management</h4>
        <div class="d-flex gap-2">
          <input type="text" id="userSearchInput" class="form-control form-control-sm" placeholder="Search users..." style="width:200px;" />
          <select id="userRoleFilter" class="form-select form-select-sm" style="width:120px;">
            <option value="">All Roles</option>
            <option value="admin">Admin</option>
            <option value="police">Police</option>
            <option value="public">Public</option>
          </select>
          <button class="btn btn-outline-secondary btn-sm" onclick="refreshUsers()">
            <i class="bi bi-arrow-clockwise"></i> Refresh
          </button>
        </div>
      </div>
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>User ID</th>
              <th>Name</th>
              <th>Email</th>
              <th>Role</th>
              <th>Status</th>
              <th>Last Login</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="usersTableBody">
            <!-- Users will be loaded dynamically -->
          </tbody>
        </table>
      </div>
    </section>

    <!-- SYSTEM LOGS -->
    <section id="system-logs">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <h4>System Logs</h4>
        <div class="d-flex gap-2">
          <select id="logTypeFilter" class="form-select form-select-sm" style="width:150px;">
            <option value="">All Types</option>
            <option value="login">Login</option>
            <option value="logout">Logout</option>
            <option value="security">Security</option>
            <option value="error">Error</option>
          </select>
          <input type="date" id="logDateFilter" class="form-control form-control-sm" style="width:150px;" />
          <button class="btn btn-outline-secondary btn-sm" onclick="refreshLogs()">
            <i class="bi bi-arrow-clockwise"></i> Refresh
          </button>
          <button class="btn btn-outline-primary btn-sm" onclick="exportLogs()">
            <i class="bi bi-download"></i> Export
          </button>
        </div>
      </div>
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>Timestamp</th>
              <th>Type</th>
              <th>User</th>
              <th>Action</th>
              <th>IP Address</th>
              <th>Details</th>
            </tr>
          </thead>
          <tbody id="logsTableBody">
            <!-- Logs will be loaded dynamically -->
          </tbody>
        </table>
      </div>
    </section>

    <!-- SECURITY SETTINGS -->
    <section id="security-settings">
      <h4>Security Settings</h4>
      <div class="row">
        <div class="col-md-6">
          <div class="card">
            <div class="card-header">
              <h6>Authentication Settings</h6>
            </div>
            <div class="card-body">
              <div class="mb-3">
                <label class="form-label">Session Timeout (minutes)</label>
                <input type="number" class="form-control" value="60" min="5" max="480" />
              </div>
              <div class="mb-3">
                <label class="form-label">Max Login Attempts</label>
                <input type="number" class="form-control" value="5" min="3" max="10" />
              </div>
              <div class="mb-3">
                <label class="form-label">Account Lock Duration (minutes)</label>
                <input type="number" class="form-control" value="15" min="5" max="1440" />
              </div>
              <button class="btn btn-primary" onclick="updateSecuritySettings()">Update Settings</button>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="card">
            <div class="card-header">
              <h6>Security Actions</h6>
            </div>
            <div class="card-body">
              <button class="btn btn-outline-warning mb-2 w-100" onclick="forceLogoutAll()">Force Logout All Users</button>
              <button class="btn btn-outline-danger mb-2 w-100" onclick="clearOldSessions()">Clear Expired Sessions</button>
              <button class="btn btn-outline-info mb-2 w-100" onclick="generateSecurityReport()">Generate Security Report</button>
              <button class="btn btn-outline-secondary w-100" onclick="backupSystemData()">Backup System Data</button>
            </div>
          </div>
        </div>
      </div>
    </section>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../assets/js/police-dashboard-realtime.js"></script>
  <!-- Sidebar Toggle Fix JS -->
  <script src="../assets/js/sidebar-toggle.js"></script>
</body>
</html>

<?php
include 'auth_middleware.php';
AuthMiddleware::checkAuthentication();
AuthMiddleware::checkRole('admin');
?>

