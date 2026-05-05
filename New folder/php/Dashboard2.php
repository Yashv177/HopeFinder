<?php
require_once __DIR__ . '/../Database/Conn_db.php';
require_once __DIR__ . '/jwt_helper.php';
require_once __DIR__ . '/auth_middleware.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    $payload = AuthMiddleware::checkAuthentication();
    AuthMiddleware::checkRole('admin');
    $admin_name = htmlspecialchars($payload['fullname']);
} catch (Exception $e) {
    header('Location: ../Login.html');
    exit();
}
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
  <!-- Chart.js for Interactive Charts -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

  <!-- Your Custom CSS (reuse existing) -->
  <link href="../Css/Dashboard2.css" rel="stylesheet" />

  <style>
    /* small overrides for admin look with modern theme */
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

    .admin-badge {
      font-size: 0.75rem;
      background: linear-gradient(135deg, #e94560, #ff6b6b);
      color: #fff;
      padding: 5px 12px;
      border-radius: 20px;
      font-weight: 600;
      box-shadow: 0 4px 15px rgba(233, 69, 96, 0.4);
    }

    .table-actions i {
      cursor: pointer;
      margin-right: 12px;
      transition: all 0.3s ease;
    }
    
    .table-actions i:hover {
      transform: scale(1.2);
    }

    .search-input {
      max-width: 360px;
    }

    /* quick responsive adjustments */
    @media (max-width:991.98px) {
      #content {
        margin-left: 0 !important;
        max-width: 100vw !important;
      }
    }
    
    /* Section Title Styling */
    section h2, section h4 {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }
    
    /* Custom Scrollbar */
    ::-webkit-scrollbar {
      width: 8px;
    }
    
    ::-webkit-scrollbar-track {
      background: #f1f1f1;
      border-radius: 10px;
    }
    
    ::-webkit-scrollbar-thumb {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      border-radius: 10px;
    }
    
    ::-webkit-scrollbar-thumb:hover {
      background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
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
      <span class="online-dot" title="Online"></span>
    </div>

    <ul class="nav flex-column mt-3">
      <li><a href="#admin-overview" class="active"><i class="bi bi-speedometer2"></i> <span class="sidebar-text">Dashboard</span></a></li>
      <li><a href="#manage-users"><i class="bi bi-people-fill"></i> <span class="sidebar-text">Manage Users</span></a></li>
      <li><a href="#manage-police"><i class="bi bi-person-badge-fill"></i> <span class="sidebar-text">Manage Police</span></a></li>
      <li><a href="#manage-reports"><i class="bi bi-file-earmark-text"></i> <span class="sidebar-text">Missing Reports</span></a></li>
      <li><a href="#found-records"><i class="bi bi-file-earmark-check"></i> <span class="sidebar-text">Found Persons</span></a></li>
<li><a href=""><i class="bi bi-cpu"></i> <span class="sidebar-text">AI Match</span></a></li>
class="sidebar-text">Notifications</span></a></li>
      <li><a href="#logs"><i class="bi bi-journal-text"></i> <span class="sidebar-text">System Logs</span></a></li>
      <li><a href="#profile-admin"><i class="bi bi-person-lines-fill"></i> <span class="sidebar-text">Profile</span></a></li>
      <li><a href="../logout.php"><i class="bi bi-box-arrow-right"></i> <span class="sidebar-text">Logout</span></a></li>
    </ul>
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
          <h5>Welcome Officer — <span><?php echo $admin_name; ?></span></h5>
        </div>
      </div>
      <div class="topbar-right">
        <input class="search-input" placeholder="Search report ID / name / location" />
        <button class="notification-btn">
          <i class="bi bi-bell"></i>
          <span class="notification-badge">6</span>
        </button>
        <div class="user-profile">
          <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($admin_name); ?>&background=667eea&color=fff&size=34" class="user-avatar" alt="User">
          <span class="user-name"><?php echo $admin_name; ?></span>
          <i class="bi bi-caret-down-fill user-dropdown"></i>
        </div>
      </div>
    </div>

    <!-- PAGE: Overview -->
    <section id="admin-overview" class="py-4">
      <h2 class="fw-bold">Welcome, <?php echo $admin_name; ?></h2>
      <p class="text-muted">Overview of platform activity & quick actions.</p>

      <!-- STAT CARDS with Modern Gradients -->
<div class="row g-4 mb-5" id="dashboardCards">
        <!-- Dynamic cards will be populated by JS -->
        <div class="col-lg-3 col-md-6">
          <div class="card-admin position-relative overflow-hidden" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 20px; backdrop-filter: blur(10px);">
            <div class="position-absolute top-0 end-0 p-2">
              <i class="bi bi-people-fill text-white-50" style="font-size: 2rem; opacity: 0.3;"></i>
            </div>
            <div class="small fw-semibold mb-1">Total Missing Cases</div>
            <div class="value mb-1" id="totalMissing">0</div>
            <small><i class="bi bi-arrow-up-circle"></i> Real-time</small>
          </div>
        </div>
        <div class="col-lg-3 col-md-6">
          <div class="card-admin position-relative overflow-hidden" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); border-radius: 20px; backdrop-filter: blur(10px);">
            <div class="position-absolute top-0 end-0 p-2">
              <i class="bi bi-check-circle-fill text-white-50" style="font-size: 2rem; opacity: 0.3;"></i>
            </div>
            <div class="small fw-semibold mb-1">Total Found Cases</div>
            <div class="value mb-1" id="totalFound">0</div>
            <small><i class="bi bi-check-lg"></i> Resolved</small>
          </div>
        </div>
        <div class="col-lg-3 col-md-6">
          <div class="card-admin position-relative overflow-hidden" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); border-radius: 20px; backdrop-filter: blur(10px);">
            <div class="position-absolute top-0 end-0 p-2">
              <i class="bi bi-camera-video-fill text-white-50" style="font-size: 2rem; opacity: 0.3;"></i>
            </div>
            <div class="small fw-semibold mb-1">Today's AI Detections</div>
            <div class="value mb-1" id="todayDetections">0</div>
            <small><i class="bi bi-clock"></i> Today</small>
          </div>
        </div>
        <div class="col-lg-3 col-md-6">
          <div class="card-admin position-relative overflow-hidden" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); border-radius: 20px; backdrop-filter: blur(10px);">
            <div class="position-absolute top-0 end-0 p-2">
              <i class="bi bi-star-fill text-white-50" style="font-size: 2rem; opacity: 0.3;"></i>
            </div>
            <div class="small fw-semibold mb-1">AI Match Success</div>
            <div class="value mb-1" id="aiSuccess">0</div>
            <small><i class="bi bi-trophy"></i> Approved</small>
          </div>
        </div>
      </div>

      <!-- Loading spinner for cards -->
      <div id="cardsLoading" class="text-center py-4 d-none">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="mb-4">
        <a href="#manage-users" class="btn btn-outline-primary me-2"><i class="bi bi-people"></i> Manage Users</a>
        <a href="#manage-reports" class="btn btn-outline-success me-2"><i class="bi bi-file-earmark-text"></i> View Reports</a>
        <a href="#ai-matches" class="btn btn-outline-info me-2"><i class="bi bi-brain"></i> AI Matches</a>
        <a href="#logs" class="btn btn-outline-secondary"><i class="bi bi-journal-text"></i> System Logs</a>
      </div>
        
        <!-- Case Resolution Bar Chart -->
        <div class="col-md-4">
          <div class="viz-card">
            <div class="viz-card-header">
              <i class="bi bi-check-circle me-2"></i>Case Resolution
            </div>
            <div class="viz-card-body">
              <div class="chart-container">
                <canvas id="resolutionTrendChart"></canvas>
              </div>
              <div class="mt-3 text-center">
                <div class="live-indicator"></div>
                <span class="text-success fw-bold">206</span>
                <small class="text-muted"> cases resolved this week</small>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Additional Charts Row -->
      <div class="row mb-4">
        <!-- Status Distribution Doughnut Chart -->
        <div class="col-md-4">
          <div class="viz-card">
            <div class="viz-card-header">
              <i class="bi bi-pie-chart me-2"></i>Status Distribution
            </div>
            <div class="viz-card-body">
              <div class="chart-container" style="height: 250px;">
                <canvas id="statusDistributionChart"></canvas>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Weekly Activity Radar Chart -->
        <div class="col-md-4">
          <div class="viz-card">
            <div class="viz-card-header">
              <i class="bi bi-activity me-2"></i>Weekly Activity
            </div>
            <div class="viz-card-body">
              <div class="chart-container" style="height: 250px;">
                <canvas id="weeklyActivityChart"></canvas>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Efficiency Gauge -->
        <div class="col-md-4">
          <div class="viz-card">
            <div class="viz-card-header">
              <i class="bi bi-speedometer2 me-2"></i>System Efficiency
            </div>
            <div class="viz-card-body text-center">
              <div id="gaugeChart" class="d-flex justify-content-center mb-3"></div>
              <div class="row text-center">
                <div class="col-6">
                  <div class="stat-card-3d">
                    <div class="counter-animate" data-count="312">0</div>
                    <small class="text-muted">Police Active</small>
                  </div>
                </div>
                <div class="col-6">
                  <div class="stat-card-3d">
                    <div class="counter-animate" data-count="1024">0</div>
                    <small class="text-muted">Matches Found</small>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <hr />

    <!-- PAGE: Manage Users -->
    <section id="manage-users" class="py-4">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <h4 class="mb-0">Manage Users</h4>
        <div>
          <input id="userSearch" class="form-control form-control-sm d-inline-block me-2" placeholder="Search by name / email" style="width:260px" />
          <button class="btn btn-sm btn-primary" onclick="openAddUserModal()"><i class="bi bi-person-plus"></i> Add User</button>
        </div>
      </div>

      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead class="table-light">
            <tr>
              <th>User ID</th>
              <th>Name</th>
              <th>Email</th>
              <th>Registered On</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>

          <tbody id="usersTable">
            <!-- JS injects rows here -->
          </tbody>
        </table>
        <div id="usersPagination" class="d-flex justify-content-center mt-3"></div>

      </div>
    </section>

    <hr />

    <!-- PAGE: Manage Police -->
    <section id="manage-police" class="py-4">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <h4 class="mb-0">Manage Police / Authorities</h4>
        <button class="btn btn-sm btn-success" onclick="openAddPoliceModal()"><i class="bi bi-person-plus-fill"></i> Add Officer</button>
      </div>

      <div class="table-responsive">
       <table class="table table-hover">
  <thead>
    <tr>
      <th>ID</th>
      <th>Name</th>
      <th>Email</th>
      <th>Location</th>
      <th>Status</th>
      <th>Action</th>
    </tr>
  </thead>
  <tbody id="policeTable"></tbody>
</table>

      </div>
      <div id="policePagination" class="d-flex justify-content-center mt-3"></div>

    <hr />

    <!-- PAGE: Missing Reports -->
    <section id="manage-reports" class="py-4">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <h4 class="mb-0">Missing Reports</h4>

          <button class="btn btn-sm btn-outline-primary" onclick="bulkApprove()"><i class="bi bi-check2-circle"></i> Approve Selected</button>
        </div>
      </div>

      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead class="table-light">
<tr>
              <th>Report ID</th>
              <th>Missing Name</th>
              <th>Age/Gender</th>
              <th>Last Seen Location</th>
              <th>Reported By</th>
              <th>Date</th>
              <th>Status</th>
              <th>Assigned To</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody id="reportsTable">
            <!-- JS injects rows here -->
          </tbody>
        </table>
        <div id="reportsPagination" class="d-flex justify-content-center mt-3"></div>
      </div>
    </section>

    <hr />

    <!-- PAGE: Found Persons -->
    <section id="found-records" class="py-4">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <h4 class="mb-0">Found Persons Records</h4>
        <button class="btn btn-sm btn-outline-success" onclick="openFoundModal()"><i class="bi bi-plus-lg"></i> Add Record</button>
      </div>

      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead class="table-light">
            <tr>
              <th>Found ID</th>
              <th>Added By</th>
              <th>Location</th>
              <th>Date</th>
              <th>Match Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody id="foundTable">
            <tr>
              <td>#F051</td>
              <td>Patna Police</td>
              <td>Patna Bus Stand</td>
              <td>2025-10-25</td>
              <td><span class="badge bg-info">Unmatched</span></td>
              <td>
                <i class="bi bi-eye text-primary" title="View"></i>
                <i class="bi bi-link-45deg text-success" title="Link to Report" onclick="linkFound('F051')"></i>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <hr />

    <!-- PAGE: AI Matches -->
    <section id="ai-matches" class="py-4">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <h4 class="mb-0">AI / Manual Matches</h4>
        <div>
          <input id="matchSearch" class="form-control form-control-sm d-inline-block me-2" placeholder="Search matches..." style="width:260px" />
          <button class="btn btn-sm btn-outline-secondary" onclick="exportMatches()"><i class="bi bi-download"></i> Export CSV</button>
        </div>
      </div>

      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead class="table-light">
            <tr>
              <th>Match ID</th>
              <th>Missing</th>
              <th>Found</th>
              <th>Match %</th>
              <th>Verified By</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody id="matchesTable">
            <tr>
              <td>#M9001</td>
              <td>Sunil Verma</td>
              <td>#F051</td>
              <td>87%</td>
              <td>AI</td>
              <td><span class="badge bg-warning text-dark">Pending</span></td>
              <td>
                <i class="bi bi-check2-square text-success" title="Approve" onclick="approveMatch('M9001')"></i>
                <i class="bi bi-x-square text-danger" title="Reject" onclick="rejectMatch('M9001')"></i>
                <i class="bi bi-download text-muted" title="Export"></i>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <hr />

    <!-- PAGE: Notifications -->
    <section id="notifications-admin" class="py-4">
      <h4>Notifications</h4>
      <p class="text-muted">Create system-wide or targeted notifications.</p>

      <div class="row">
        <div class="col-md-6">
          <div class="card p-3">
            <h6>Send Notification</h6>
            <form id="notifyForm" onsubmit="return false;">
              <div class="mb-2">
                <select id="notifyTarget" class="form-select form-select-sm">
                  <option value="all">All Users</option>
                  <option value="police">Police / Authorities</option>
                  <option value="user">Specific User</option>
                </select>
              </div>
              <div class="mb-2">
                <input id="notifyRecipient" class="form-control form-control-sm" placeholder="user id or email (if specific)" />
              </div>
              <div class="mb-2">
                <textarea id="notifyMessage" class="form-control form-control-sm" rows="3" placeholder="Message..."></textarea>
              </div>
              <button class="btn btn-sm btn-primary">Send Notification</button>
            </form>
          </div>
        </div>

        <div class="col-md-6">
          <div class="card p-3">
            <h6>Recent Notifications</h6>
            <ul class="list-group list-group-flush" id="recentNotifs">
              <li class="list-group-item">[2025-11-12] New match suggested for #R1023</li>
              <li class="list-group-item">[2025-11-11] Police added record #F050</li>
            </ul>
          </div>
        </div>
      </div>
    </section>

    <hr />

    <!-- PAGE: System Logs -->
    <section id="logs" class="py-4">
      <h4>Activity Logs</h4>
      <p class="text-muted">Audit trail of important actions.</p>

      <div class="table-responsive">
        <table class="table table-sm">
          <thead class="table-light">
            <tr>
              <th>Log ID</th>
              <th>User</th>
              <th>Action</th>
              <th>Timestamp</th>
              <th>IP</th>
            </tr>
          </thead>
          <tbody id="logsTable">
            <tr>
              <td>#L0001</td>
              <td>Admin</td>
              <td>Added police #P0092</td>
              <td>2025-11-10 10:05</td>
              <td>103.23.44.12</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <hr />

    <!-- PAGE: Profile -->
    <section id="profile-admin" class="py-4">
      <h4>Admin Profile</h4>
      <div class="row">
        <div class="col-md-4">
          <div class="card p-3">
            <img src="https://via.placeholder.com/120" class="rounded-circle mb-3" alt="profile">
            <h6>Yash Varshney</h6>
            <p class="text-muted mb-1">Super Admin</p>
            <p class="small text-muted">yash@example.com</p>
            <button class="btn btn-sm btn-outline-secondary" onclick="openEditProfile()">Edit Profile</button>
          </div>
        </div>

        <div class="col-md-8">
          <div class="card p-3">
            <h6>Profile Details</h6>
            <form id="adminProfileForm" onsubmit="return false;">
              <div class="row g-2">
                <div class="col-md-6">
                  <label class="form-label small">Full Name</label>
                  <input class="form-control form-control-sm" value="Yash Varshney" />
                </div>
                <div class="col-md-6">
                  <label class="form-label small">Email</label>
                  <input class="form-control form-control-sm" value="yash@example.com" />
                </div>
                <div class="col-md-6">
                  <label class="form-label small">Phone</label>
                  <input class="form-control form-control-sm" value="+91-XXXXXXXXXX" />
                </div>
                <div class="col-md-6">
                  <label class="form-label small">Role</label>
                  <input class="form-control form-control-sm" value="Super Admin" readonly />
                </div>
                <div class="col-12 mt-2">
                  <button class="btn btn-sm btn-primary">Save Changes</button>
                  <button class="btn btn-sm btn-outline-danger ms-2">Reset Password</button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </section>

    <div style="height:60px"></div>
  </main>

  <!-- Modals: Add User / Add Police / View Report / Assign Police (skeletons) -->
  <div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h6 class="modal-title">Confirm Action</h6>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body" id="confirmMessage">
          Are you sure?
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-danger" id="confirmYes">Yes</button>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="alertModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h6 class="modal-title">Alert</h6>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body" id="alertMessage">
          Message
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h6 class="modal-title">Add New User</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
           <form id="addUserForm">
          <input class="form-control mb-2" name="fullname" placeholder="Full name" required>
          <input class="form-control mb-2" name="email" placeholder="Email" type="email" required>
          <input class="form-control mb-2" name="password" placeholder="Password (min 8 chars)" type="password" minlength="8" required>

          <div class="text-end">
            <button type="submit" class="btn btn-sm btn-primary">
              Create
            </button>
          </div>
        </form>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="addPoliceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h6 class="modal-title">Add Police / Officer</h6>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <form id="addPoliceForm">
            <div class="mb-3">
              <label class="form-label small">Full Name <span class="text-danger">*</span></label>
              <input class="form-control form-control-sm" name="fullname" placeholder="Enter full name" required>
            </div>
            <div class="mb-3">
              <label class="form-label small">Email <span class="text-danger">*</span></label>
              <input class="form-control form-control-sm" name="email" type="email" placeholder="Enter email address" required>
            </div>
            <div class="mb-3">
              <label class="form-label small">Badge Number <span class="text-danger">*</span></label>
              <input class="form-control form-control-sm" name="badge_number" placeholder="e.g., P-12345" required>
            </div>
            <div class="mb-3">
              <label class="form-label small">Station Name <span class="text-danger">*</span></label>
              <input class="form-control form-control-sm" name="station_name" placeholder="Enter station name" required>
            </div>
            <div class="row mb-3">
              <div class="col-6">
                <label class="form-label small">District <span class="text-danger">*</span></label>
                <input class="form-control form-control-sm" name="district" placeholder="District" required>
              </div>
              <div class="col-6">
                <label class="form-label small">State <span class="text-danger">*</span></label>
                <input class="form-control form-control-sm" name="state" placeholder="State" required>
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label small">Password <span class="text-danger">*</span> (min 8 chars)</label>
              <input class="form-control form-control-sm" name="password" type="password" placeholder="Minimum 8 characters" minlength="8" required>
            </div>
            <div class="text-end">
              <button type="submit" class="btn btn-sm btn-success">
                <i class="bi bi-person-plus-fill"></i> Create Officer
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- View Police Modal -->
  <div class="modal fade" id="viewPoliceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h6 class="modal-title">Police Officer Details</h6>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row mb-2">
            <div class="col-4 text-muted small">Police ID</div>
            <div class="col-8 fw-bold" id="viewPoliceId">-</div>
          </div>
          <div class="row mb-2">
            <div class="col-4 text-muted small">Full Name</div>
            <div class="col-8" id="viewFullname">-</div>
          </div>
          <div class="row mb-2">
            <div class="col-4 text-muted small">Email</div>
            <div class="col-8" id="viewEmail">-</div>
          </div>
          <div class="row mb-2">
            <div class="col-4 text-muted small">Badge Number</div>
            <div class="col-8 fw-bold" id="viewBadgeNumber">-</div>
          </div>
          <div class="row mb-2">
            <div class="col-4 text-muted small">Station</div>
            <div class="col-8" id="viewStation">-</div>
          </div>
          <div class="row mb-2">
            <div class="col-4 text-muted small">District</div>
            <div class="col-8" id="viewDistrict">-</div>
          </div>
          <div class="row mb-2">
            <div class="col-4 text-muted small">State</div>
            <div class="col-8" id="viewState">-</div>
          </div>
          <div class="row mb-2">
            <div class="col-4 text-muted small">Status</div>
            <div class="col-8" id="viewStatus">-</div>
          </div>
          <div class="row mb-2">
            <div class="col-4 text-muted small">Created At</div>
            <div class="col-8 small text-muted" id="viewCreatedAt">-</div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Edit Police Modal -->
  <div class="modal fade" id="editPoliceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h6 class="modal-title">Edit Police Officer</h6>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <form id="editPoliceForm">
            <input type="hidden" name="police_id" id="editPoliceId">
            <div class="mb-3">
              <label class="form-label small">Full Name <span class="text-danger">*</span></label>
              <input class="form-control form-control-sm" name="fullname" id="editFullname" required>
            </div>
            <div class="mb-3">
              <label class="form-label small">Badge Number <span class="text-danger">*</span></label>
              <input class="form-control form-control-sm" name="badge_number" id="editBadgeNumber" required>
            </div>
            <div class="mb-3">
              <label class="form-label small">Station Name <span class="text-danger">*</span></label>
              <input class="form-control form-control-sm" name="station_name" id="editStationName" required>
            </div>
            <div class="row mb-3">
              <div class="col-6">
                <label class="form-label small">District <span class="text-danger">*</span></label>
                <input class="form-control form-control-sm" name="district" id="editDistrict" required>
              </div>
              <div class="col-6">
                <label class="form-label small">State <span class="text-danger">*</span></label>
                <input class="form-control form-control-sm" name="state" id="editState" required>
              </div>
            </div>
            <div class="text-end">
              <button type="submit" class="btn btn-sm btn-primary" id="editPoliceSaveBtn">
                <i class="bi bi-check-lg"></i> Save Changes
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- PDF Preview Modal (iframe-based, same page, no download) -->
  <div class="modal fade" id="pdfPreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h6 class="modal-title" id="pdfPreviewModalTitle">PDF Preview</h6>
          <button type="button" class="btn-close" data-bs-dismiss="modal" onclick="closePdfPreview()"></button>
        </div>
        <div class="modal-body p-0" style="height: 80vh;">
          <!-- iframe for PDF display - NO download, SAME page -->
          <iframe id="pdfPreviewIframe" 
                  src="" 
                  style="width: 100%; height: 100%; border: none;"
                  title="PDF Preview">
          </iframe>
        </div>
        <div class="modal-footer">
          <a id="pdfDownloadLink" href="" target="_blank" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-download"></i> Download PDF
          </a>
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal" onclick="closePdfPreview()">
            Close
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- View Report Modal -->
  <div class="modal fade" id="viewReportModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h6 class="modal-title">Report Details</h6>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <!-- Report ID and Status -->
          <div class="row mb-3">
            <div class="col-6">
              <strong>Report ID:</strong> <span id="viewReportId">-</span>
            </div>
            <div class="col-6 text-end">
              <span id="viewStatus">-</span>
            </div>
          </div>
          
          <!-- Missing Person Info -->
          <div class="card mb-3">
            <div class="card-header bg-light">
              <strong><i class="bi bi-person-fill"></i> Missing Person Information</strong>
            </div>
            <div class="card-body">
              <div class="row mb-2">
                <div class="col-4 text-muted small">Name</div>
                <div class="col-8" id="viewMissingName">-</div>
              </div>
              <div class="row mb-2">
                <div class="col-4 text-muted small">Age</div>
                <div class="col-8" id="viewAge">-</div>
              </div>
              <div class="row mb-2">
                <div class="col-4 text-muted small">Gender</div>
                <div class="col-8" id="viewGender">-</div>
              </div>
              <div class="row mb-2">
                <div class="col-4 text-muted small">Last Seen Location</div>
                <div class="col-8" id="viewLocation">-</div>
              </div>
              <div class="row mb-2">
                <div class="col-4 text-muted small">Last Seen Date</div>
                <div class="col-8" id="viewDateMissing">-</div>
              </div>
              <div class="row mb-2">
                <div class="col-4 text-muted small">Description</div>
                <div class="col-8" id="viewDescription">-</div>
              </div>
              <div class="row mb-2">
                <div class="col-4 text-muted small">Reported On</div>
                <div class="col-8" id="viewCreatedAt">-</div>
              </div>
            </div>
          </div>
          
          <!-- Reporter Info -->
          <div class="card mb-3">
            <div class="card-header bg-light">
              <strong><i class="bi bi-person-lines-fill"></i> Reporter Information</strong>
            </div>
            <div class="card-body">
              <div class="row mb-2">
                <div class="col-4 text-muted small">Name</div>
                <div class="col-8" id="viewReporterName">-</div>
              </div>
              <div class="row mb-2">
                <div class="col-4 text-muted small">Email</div>
                <div class="col-8" id="viewReporterEmail">-</div>
              </div>
            </div>
          </div>
          
          <!-- Found Details Section (NEW) -->
          <div class="card mb-3" id="viewFoundSection" style="display: none;">
            <div class="card-header bg-success text-white">
              <strong><i class="bi bi-check-circle-fill"></i> Found Details</strong>
            </div>
            <div class="card-body">
              <div class="row mb-2">
                <div class="col-4 text-muted small">Found Time</div>
                <div class="col-8" id="viewFoundDate">-</div>
              </div>
              <div class="row">
                <div class="col-4 text-muted small">Found Location</div>
                <div class="col-8" id="viewFoundLocation">-</div>
              </div>
            </div>
          </div>

          <!-- Assigned Police Info -->
          <div class="card mb-3" id="viewPoliceSection" style="display: none;">
            <div class="card-header bg-light">
              <strong><i class="bi bi-shield-fill"></i> Assigned Police</strong>
            </div>
            <div class="card-body">
              <div class="row mb-2">
                <div class="col-4 text-muted small">Officer Name</div>
                <div class="col-8" id="viewPoliceName">-</div>
              </div>
              <div class="row mb-2">
                <div class="col-4 text-muted small">Badge Number</div>
                <div class="col-8" id="viewPoliceBadge">-</div>
              </div>
              <div class="row mb-2">
                <div class="col-4 text-muted small">Station</div>
                <div class="col-8" id="viewPoliceStation">-</div>
              </div>
              <div class="row mb-2">
                <div class="col-4 text-muted small">Assigned At</div>
                <div class="col-8" id="viewAssignedAt">-</div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-primary btn-sm" id="viewReportPdfBtn" style="display: none;">
            <i class="bi bi-file-earmark-pdf"></i> View PDF
          </button>
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Assign Police Modal -->
  <div class="modal fade" id="assignPoliceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h6 class="modal-title">Assign Police Officer</h6>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p class="text-muted mb-3">Assign a police officer to investigate this missing person case.</p>
          
          <!-- Report Info -->
          <div class="alert alert-info py-2 mb-3">
            <strong>Report:</strong> <span id="assignReportId">-</span><br>
            <strong>Missing Person:</strong> <span id="assignMissingName">-</span>
          </div>
          
          <!-- Police Selection -->
          <div class="mb-3">
            <label class="form-label small">Select Police Officer <span class="text-danger">*</span></label>
            <select id="assignPoliceSelect" class="form-select form-select-sm">
              <option value="">-- Loading police officers --</option>
            </select>
          </div>
          
          <div class="form-text small">
            <i class="bi bi-info-circle"></i> Only active police officers are shown.
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary btn-sm" onclick="confirmPoliceAssignment()">
            <i class="bi bi-person-plus-fill"></i> Assign Police
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Bootstrap JS + your custom JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <!-- <script src="../JavaScript/Dashboard.js"></script> -->
  <script src="../JavaScript/Dashboard-fixed.js"></script>
  <!-- reportsModule.js REMOVED - conflicted with Dashboard.js pagination -->
  <!-- Interactive Dashboard Visualizations -->
  <script src="../JavaScript/DashboardViz.js"></script>
  <script src="../JavaScript/notifications.js"></script>
  <script src="dashboard_charts.js"></script>

  <script>
    // Existing dashboard functionality preserved
    document.querySelector('.notification-btn')?.addEventListener('click', function() {
      // Show dropdown
    });
  </script>
  <script>
    // Initialize Bootstrap Modals for user/police management
    const addUserModal = new bootstrap.Modal(document.getElementById('addUserModal'));
    const addPoliceModal = new bootstrap.Modal(document.getElementById('addPoliceModal'));
    const viewPoliceModal = new bootstrap.Modal(document.getElementById('viewPoliceModal'));
    const editPoliceModal = new bootstrap.Modal(document.getElementById('editPoliceModal'));
    const pdfPreviewModal = new bootstrap.Modal(document.getElementById('pdfPreviewModal'));
// Note: All modals handled by Dashboard-fixed.js (viewReportModal cached + safety init)
  
// Fallback safety for viewReportModal (if JS load issue)
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', function() {
    const viewReportEl = document.getElementById('viewReportModal');
    if (viewReportEl && typeof bootstrap !== 'undefined' && !window.modals?.viewReportModal) {
      window.modals = window.modals || {};
      window.modals.viewReportModal = bootstrap.Modal.getOrCreateInstance(viewReportEl);
      console.log('View Report Modal fallback initialized');
    }
  });
} else {
  const viewReportEl = document.getElementById('viewReportModal');
  if (viewReportEl && typeof bootstrap !== 'undefined' && !window.modals?.viewReportModal) {
    window.modals = window.modals || {};
    window.modals.viewReportModal = bootstrap.Modal.getOrCreateInstance(viewReportEl);
    console.log('View Report Modal fallback initialized');
  }
}

    // Helper functions for opening modals
    function openAddUserModal() {
      addUserModal.show();
    }

    function openAddPoliceModal() {
      addPoliceModal.show();
    }

    function openEditProfile() {
      alert('Open edit profile (implement)');
    }

    // Placeholder functions for features not yet implemented
    function openFoundModal() {
      alert('Open add found person modal (implement)');
    }

    function linkFound(id) {
      alert('Link found record: ' + id);
    }

    function approveMatch(id) {
      alert('Approve match ' + id);
    }

    function rejectMatch(id) {
      alert('Reject match ' + id);
    }

    function exportMatches() {
      alert('Exporting matches...');
    }

    function exportCSV(tableId) {
      alert('Export CSV for ' + tableId);
    }

    function bulkApprove() {
      alert('Bulk approve selected reports (implement)');
    }

    // Optional: simple anchor smooth scroll for sidebar navigation
    document.querySelectorAll('#sidebar a').forEach(a => {
      a.addEventListener('click', (e) => {
        const href = a.getAttribute('href') || '';
        if (href.startsWith('#')) {
          e.preventDefault();
          const el = document.querySelector(href);
          if (el) el.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
          });
        }
      });
    });
  </script>
</body>

</html>
