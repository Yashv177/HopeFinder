<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../Database/Conn_db.php';
require_once __DIR__ . '/jwt_helper.php';
require_once __DIR__ . '/auth_middleware.php';

$payload = AuthMiddleware::checkRole('police');

$officer_name = $_SESSION['fullname'] ?? 'Officer';

// Fetch police station from DB
global $conn;
$stmt = $conn->prepare('SELECT station_name FROM police WHERE user_id = ? LIMIT 1');
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$police_data = $result->fetch_assoc();
$stmt->close();

$station_name = $police_data['station_name'] ?? 'Patna Central';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>HopeFinder — Police Dashboard</title>
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
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
  <!-- SIDEBAR -->
  <nav id="sidebar">
    <div class="sidebar-header">
      <i class="bi bi-shield-fill-check"></i>
      <div>
        <strong class="sidebar-text">HopeFinder Police</strong>
        <div>Officer Dashboard</div>
      </div>
      <span class="online-dot"></span>
    </div>
    
    <!-- Quick Profile -->
    <div class="sidebar-profile">
      <img src="https://ui-avatars.com/api/?name=<?= htmlspecialchars($officer_name) ?>&background=667eea&color=fff&size=40" class="rounded-circle">
      <div>
        <div><?= htmlspecialchars($officer_name ?: 'Officer') ?></div>
        <span>● Online</span>
      </div>
    </div>
    
    <!-- Navigation Links -->
    <ul class="nav">
      <li><a href="#police-overview" class="sidebar-action active"><span class="nav-icon"><i class="bi bi-speedometer2"></i></span><span class="sidebar-text">Dashboard</span></a></li>
      <li><a href="#view-reports" class="sidebar-action"><span class="nav-icon"><i class="bi bi-list-ul"></i></span><span class="sidebar-text">View Reports</span></a></li>
      <li><a href="#case-details" class="sidebar-action"><span class="nav-icon"><i class="bi bi-file-earmark-text"></i></span><span class="sidebar-text">Case Details</span></a></li>
      <li><a href="#add-missing" class="sidebar-action"><span class="nav-icon"><i class="bi bi-person-plus"></i></span><span class="sidebar-text">Add Missing Report</span></a></li>
      <li><a href="#ai-suggestions" class="sidebar-action"><span class="nav-icon"><i class="bi bi-cpu"></i></span><span class="sidebar-text">AI Match Suggestions</span><span class="nav-badge">AI</span></a></li>
      <li><a href="#notifications-police" class="sidebar-action"><span class="nav-icon"><i class="bi bi-bell-fill"></i></span><span class="sidebar-text">Notifications</span><span class="nav-badge bg-danger">4</span></a></li>
    </ul>

    <!-- Bottom Actions (Profile & Logout) -->
    <div class="sidebar-logout">
      <a href="#profile-police" class="sidebar-action">
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
          <h5>Welcome Officer — <span><?= htmlspecialchars($officer_name ?: 'Officer') ?></span></h5>
          <small><?= htmlspecialchars($station_name) ?> | Station Active</small>
        </div>
      </div>
      <div class="topbar-right">
        <input class="search-input" placeholder="Search report ID / name / location" />
        <button class="notification-btn">
          <i class="bi bi-bell"></i>
          <span class="notification-badge">4</span>
        </button>
        <div class="user-profile">
          <img src="https://ui-avatars.com/api/?name=<?= htmlspecialchars($officer_name) ?>&background=667eea&color=fff&size=34" class="user-avatar" alt="User">
          <span class="user-name"><?= htmlspecialchars($officer_name ?: 'Officer') ?></span>
          <i class="bi bi-caret-down-fill user-dropdown"></i>
        </div>
      </div>
    </div>

    <!-- POLICE OVERVIEW -->
    <section id="police-overview">
      <h2>Officer Dashboard</h2>
      <p>Quick summary of cases & actions in your jurisdiction.</p>

      <!-- STAT CARDS -->
      <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
          <div class="stat-card">
            <div class="stat-label">Total Reports</div>
            <div class="stat-value" id="total-reports">248</div>
            <small>in your region</small>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-card">
            <div class="stat-label">Pending Verification</div>
            <div class="stat-value" id="pending-verification">18</div>
            <small>awaiting your review</small>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-card">
            <div class="stat-label">Verified Cases</div>
            <div class="stat-value" id="verified-cases">120</div>
            <small>verified by you/team</small>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-card">
            <div class="stat-label">Matches Found</div>
            <div class="stat-value" id="matches-found">10</div>
            <small>confirmed matches</small>
          </div>
        </div>
      </div>

      <!-- SHORTCUTS -->
      <div class="mb-3 d-flex flex-wrap gap-2">
        <a href="#view-reports" class="btn btn-outline-primary"><i class="bi bi-list-ul"></i> View All Reports</a>
        <a href="#add-missing" class="btn btn-outline-success"><i class="bi bi-person-plus"></i> Add Missing Report</a>
        <a href="#ai-suggestions" class="btn btn-outline-info"><i class="bi bi-brain"></i> AI Match Suggestions</a>
      </div>

      <div class="row g-3">
        <!-- MAIN CHART -->
        <div class="col-lg-8">
          <div class="chart-container">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <div>
                <h5 style="margin:0;color:var(--text-primary);"><i class="bi bi-graph-up" style="color:var(--primary-light);margin-right:8px;"></i>Reports Trend</h5>
                <small style="color:var(--text-secondary);">Daily reports in last 7 days</small>
              </div>
              <button class="btn btn-outline-secondary btn-sm" onclick="refreshCharts()"><i class="bi bi-arrow-clockwise"></i></button>
            </div>
            <div class="chart-wrapper">
              <canvas id="reportsChart"></canvas>
            </div>
          </div>
        </div>

        <!-- SIDE CHARTS -->
        <div class="col-lg-4">
          <div class="row g-3">
            <!-- STATUS CHART -->
            <div class="col-12">
              <div class="chart-container">
                <div class="d-flex align-items-center justify-content-between mb-2">
                  <h6 style="margin:0;color:var(--text-primary);"><i class="bi bi-pie-chart" style="color:var(--secondary);margin-right:8px;"></i>Case Status</h6>
                  <small style="color:var(--text-muted);">Distribution</small>
                </div>
                <div class="chart-wrapper" style="height:160px;">
                  <canvas id="statusChart"></canvas>
                </div>
                <div class="d-flex justify-content-around mt-2" style="padding-top:12px;border-top:1px solid var(--border-color);">
                  <div class="text-center">
                    <div style="font-size:0.75rem;font-weight:600;color:var(--warning);">Pending</div>
                    <small style="color:var(--text-muted);" id="pending-count">0</small>
                  </div>
                  <div class="text-center">
                    <div style="font-size:0.75rem;font-weight:600;color:var(--accent);">Verified</div>
                    <small style="color:var(--text-muted);" id="verified-count">0</small>
                  </div>
                  <div class="text-center">
                    <div style="font-size:0.75rem;font-weight:600;color:var(--primary-light);">Matched</div>
                    <small style="color:var(--text-muted);" id="matched-count">0</small>
                  </div>
                </div>
              </div>
            </div>

            <!-- FOUND VS MISSING -->
            <div class="col-12">
              <div class="chart-container">
                <div class="d-flex align-items-center justify-content-between mb-2">
                  <h6 style="margin:0;color:var(--text-primary);"><i class="bi bi-bar-chart" style="color:var(--accent);margin-right:8px;"></i>Found vs Missing</h6>
                  <div class="d-flex align-items-center gap-2">
                    <span style="width:8px;height:8px;background:var(--danger);border-radius:50%;display:inline-block;"></span>
                    <small style="color:var(--text-muted);font-size:0.75rem;">Missing</small>
                    <span style="width:8px;height:8px;background:var(--accent);border-radius:50%;display:inline-block;"></span>
                    <small style="color:var(--text-muted);font-size:0.75rem;">Found</small>
                  </div>
                </div>
                <div class="chart-wrapper" style="height:160px;">
                  <canvas id="foundVsMissingChart"></canvas>
                </div>
              </div>
            </div>

            <!-- QUICK ACTIONS -->
            <div class="col-12">
              <div class="chart-container">
                <h6 style="margin:0;color:var(--text-primary);margin-bottom:12px;"><i class="bi bi-lightning" style="color:var(--warning);margin-right:8px;"></i>Quick Actions</h6>
                <div class="d-grid gap-2">
                  <button class="btn btn-outline-secondary btn-sm" onclick="refreshAllData()"><i class="bi bi-arrow-clockwise"></i> Refresh Data</button>
                  <button class="btn btn-outline-secondary btn-sm"><i class="bi bi-file-earmark-pdf"></i> Export Report</button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- METRICS ROW -->
      <div class="row g-3 mt-2">
        <div class="col-md-6">
          <div class="chart-container">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <h6 style="margin:0;color:var(--text-primary);"><i class="bi bi-clock" style="color:var(--info);margin-right:8px;"></i>Response Time</h6>
              <span class="badge-status bg-success" style="background:rgba(34,197,94,0.2)!important;color:var(--accent);">Avg: 2.3h</span>
            </div>
            <div class="d-flex justify-content-around" style="padding:12px 0;">
              <div class="text-center">
                <div style="font-size:1.25rem;font-weight:700;color:var(--info);">2.3h</div>
                <small style="color:var(--text-muted);font-size:0.75rem;">Average</small>
              </div>
              <div class="text-center">
                <div style="font-size:1.25rem;font-weight:700;color:var(--accent);">1.1h</div>
                <small style="color:var(--text-muted);font-size:0.75rem;">Fastest</small>
              </div>
              <div class="text-center">
                <div style="font-size:1.25rem;font-weight:700;color:var(--warning);">4.7h</div>
                <small style="color:var(--text-muted);font-size:0.75rem;">Slowest</small>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="chart-container">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <h6 style="margin:0;color:var(--text-primary);"><i class="bi bi-trophy" style="color:var(--warning);margin-right:8px;"></i>Performance</h6>
              <span class="badge-status bg-primary" style="background:rgba(99,102,241,0.2)!important;color:var(--primary-light);">This Month</span>
            </div>
            <div style="padding:8px 0;">
              <div style="margin-bottom:12px;">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <span style="color:var(--text-secondary);font-size:0.8rem;">Cases Resolved</span>
                  <span style="color:var(--accent);font-weight:600;font-size:0.8rem;">89%</span>
                </div>
                <div class="progress">
                  <div class="progress-bar bg-success" style="width:89%"></div>
                </div>
              </div>
              <div style="margin-bottom:12px;">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <span style="color:var(--text-secondary);font-size:0.8rem;">Matches Found</span>
                  <span style="color:var(--primary-light);font-weight:600;font-size:0.8rem;">67%</span>
                </div>
                <div class="progress">
                  <div class="progress-bar bg-primary" style="width:67%"></div>
                </div>
              </div>
              <div style="margin-bottom:12px;">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <span style="color:var(--text-secondary);font-size:0.8rem;">Response Rate</span>
                  <span style="color:var(--secondary);font-weight:600;font-size:0.8rem;">94%</span>
                </div>
                <div class="progress">
                  <div class="progress-bar bg-info" style="width:94%"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <hr/>

    <!-- VIEW REPORTS -->
    <section id="view-reports">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <h4>View All Missing Reports</h4>
        <div class="d-flex gap-2">
          <input type="text" id="reportSearchInput" class="form-control form-control-sm" 
                 placeholder="Search reports..." style="width:180px;background:var(--bg-hover);border:1px solid var(--border-color);color:var(--text-primary);" />
          <select id="reportStatusFilter" class="form-select form-select-sm" 
                  style="width:140px;background:var(--bg-hover);border:1px solid var(--border-color);color:var(--text-primary);">
            <option value="">All Status</option>
            <option value="pending">Pending</option>
            <option value="verified">Verified</option>
            <option value="assigned">Assigned</option>
            <option value="closed">Closed</option>
          </select>
          <button class="btn btn-outline-secondary btn-sm" onclick="refreshReports()">
            <i class="bi bi-arrow-clockwise"></i> Refresh
          </button>
        </div>
      </div>
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>Report ID</th>
              <th>Person Name</th>
              <th>Age/Gender</th>
              <th>Last Seen</th>
              <th>Date Reported</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="reportsTableBody">
            <!-- Reports will be loaded dynamically -->
          </tbody>
        </table>
      </div>
    </section>

    <hr/>

    <!-- CASE DETAILS -->
    <section id="case-details" data-report-id="">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <h4>Case Details</h4>
        <div>
          <button class="btn btn-outline-secondary btn-sm me-2" onclick="downloadReportPDF()">Download PDF</button>
          <a href="#add-missing" class="btn btn-outline-primary btn-sm">Add Missing Report</a>
        </div>
      </div>
      <div class="card">
        <div class="row">
          <div class="col-md-4">
            <img id="case-photo" src="https://ui-avatars.com/api/?name=Missing+Person&background=667eea&color=fff&size=300" class="img-fluid rounded" style="width:100%;max-width:300px;" />
          </div>
          <div class="col-md-8">
            <h5 id="case-name" style="margin-bottom:16px;">Select a report to view details</h5>
            <p id="case-age-gender" style="margin-bottom:8px;"><strong>Age/Gender:</strong> -</p>
            <p id="case-last-seen" style="margin-bottom:8px;"><strong>Last Seen:</strong> -</p>
            <p id="case-date-missing" style="margin-bottom:8px;"><strong>Date Missing:</strong> -</p>
            <p id="case-reported-by" style="margin-bottom:8px;"><strong>Reported By:</strong> -</p>
            <p id="case-contact" style="margin-bottom:16px;"><strong>Contact:</strong> -</p>
            <div style="margin-bottom:16px;">
              <label class="form-label">Status</label>
              <select id="case-status" class="form-select form-select-sm" style="width:180px;background:var(--bg-hover);border:1px solid var(--border-color);color:var(--text-primary);">
                <option value="pending">Pending</option>
                <option value="verified">Verified</option>
                <option value="assigned">Assigned</option>
                <option value="closed">Closed</option>
              </select>
            </div>
            <div style="margin-bottom:16px;">
              <label class="form-label">Officer Remarks</label>
              <textarea id="case-remarks" class="form-control" rows="3" style="background:var(--bg-hover);border:1px solid var(--border-color);color:var(--text-primary);" placeholder="Add notes about this case..."></textarea>
            </div>
            <div>
              <button class="btn btn-primary btn-sm" onclick="saveCaseUpdate()">Save Update</button>
              <button class="btn btn-outline-secondary btn-sm ms-2" onclick="notifyReporter()">Notify Reporter</button>
            </div>
          </div>
        </div>
      </div>
    </section>

    <hr/>

    <!-- ADD MISSING REPORT -->
    <section id="add-missing">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <h4>Add Missing Report</h4>
        <button class="btn btn-primary btn-sm" onclick="showNewMissingReportForm()">New Report</button>
      </div>
      <div class="card">
        <form id="addMissingReportForm" enctype="multipart/form-data">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Missing Person Name</label>
              <input type="text" name="missing_name" class="form-control" placeholder="Full name of missing person" required />
            </div>
            <div class="col-md-3">
              <label class="form-label">Age</label>
              <input type="number" name="age" class="form-control" placeholder="Age" min="0" max="150" />
            </div>
            <div class="col-md-3">
              <label class="form-label">Gender</label>
              <select name="gender" class="form-select">
                <option value="">Select</option>
                <option value="male">Male</option>
                <option value="female">Female</option>
                <option value="other">Other</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Last Seen Location</label>
              <input type="text" name="last_seen_location" class="form-control" placeholder="Address / place last seen" required />
            </div>
            <div class="col-md-3">
              <label class="form-label">Date Missing</label>
              <input type="datetime-local" name="last_seen_datetime" class="form-control" required />
            </div>
            <div class="col-md-3">
              <label class="form-label">Upload Photo</label>
              <input type="file" id="missingPhoto" name="photo" class="form-control" accept="image/*" />
              <img id="missingPhotoPreview" src="" alt="Photo Preview" style="display:none;width:80px;height:80px;object-fit:cover;border-radius:8px;margin-top:8px;" />
            </div>
            <div class="col-12">
              <label class="form-label">Description</label>
              <textarea name="description" class="form-control" rows="3" placeholder="Physical description, clothing worn, any distinguishing marks, etc."></textarea>
            </div>
            <div class="col-12 text-end">
              <button type="submit" class="btn btn-success btn-sm">
                <i class="bi bi-save"></i> Submit Report
              </button>
            </div>
          </div>
        </form>
      </div>
      
      <!-- Recently Submitted -->
      <div class="mt-4">
        <h6 style="margin-bottom:12px;color:var(--text-secondary);">Recent Reports</h6>
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>Report ID</th>
                <th>Name</th>
                <th>Age/Gender</th>
                <th>Last Seen</th>
                <th>Date</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="missingReportsTableBody">
              <tr><td colspan="7" class="text-center text-muted">Loading...</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <hr/>

    <!-- AI MATCH SUGGESTIONS -->
    <section id="ai-suggestions">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <h4>AI Match Suggestions</h4>
        <div>
          <button class="btn btn-outline-secondary btn-sm" onclick="refreshMatches()">Refresh</button>
          <button class="btn btn-outline-primary btn-sm ms-2" onclick="exportMatchesCSV()">Export CSV</button>
        </div>
      </div>
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>Match ID</th>
              <th>Missing</th>
              <th>Found</th>
              <th>Match %</th>
              <th>Suggested By</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>#M9001</td>
              <td>Sunil Verma</td>
              <td>#F051</td>
              <td>87%</td>
              <td>AI</td>
              <td class="table-actions">
                <i class="bi bi-check2-square text-success"></i>
                <i class="bi bi-x-square text-danger"></i>
                <i class="bi bi-eye text-info"></i>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <hr/>

    <!-- NOTIFICATIONS -->
    <section id="notifications-police">
      <h4>Notifications</h4>
      <div class="card">
        <div class="row g-3 mb-3">
          <div class="col-md-3">
            <select class="form-select form-select-sm" style="background:var(--bg-hover);border:1px solid var(--border-color);color:var(--text-primary);">
              <option value="reporter">Notify Reporter</option>
              <option value="admin">Notify Admin</option>
            </select>
          </div>
          <div class="col-md-6">
            <input class="form-control form-select-sm" placeholder="Write notification..." />
          </div>
          <div class="col-md-3 text-end">
            <button class="btn btn-primary btn-sm">Send</button>
          </div>
        </div>
        <div style="margin-top:16px;">
          <h6 style="font-size:0.8rem;color:var(--text-muted);margin-bottom:12px;">Recent</h6>
          <div style="background:var(--bg-hover);border-radius:8px;overflow:hidden;">
            <div style="padding:10px 16px;border-bottom:1px solid var(--border-color);font-size:0.85rem;color:var(--text-secondary);">[2025-11-12] New match suggested for #R124</div>
            <div style="padding:10px 16px;font-size:0.85rem;color:var(--text-secondary);">[2025-11-11] Admin requested evidence for #R1023</div>
          </div>
        </div>
      </div>
    </section>

    <hr/>

    <!-- PROFILE -->
    <section id="profile-police">
      <h4>Officer Profile</h4>
      <div class="row">
        <div class="col-md-4">
          <div class="card text-center">
            <img src="https://ui-avatars.com/api/?name=<?= htmlspecialchars($officer_name) ?>&background=667eea&color=fff&size=120" class="rounded-circle mb-3" style="width:100px;height:100px;" />
            <h6 style="margin-bottom:4px;"><?= htmlspecialchars($officer_name ?: 'Officer') ?></h6>
            <p style="color:var(--text-muted);font-size:0.85rem;margin-bottom:16px;">Patna Central</p>
            <button class="btn btn-outline-secondary btn-sm">Edit Profile</button>
          </div>
        </div>
        <div class="col-md-8">
          <div class="card">
            <h6 style="margin-bottom:16px;">Profile Details</h6>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Full Name</label>
                <input class="form-control" value="<?= htmlspecialchars($officer_name ?: 'Officer') ?>" />
              </div>
              <div class="col-md-6">
                <label class="form-label">Email</label>
                <input class="form-control" value="ajay.police@example.com" />
              </div>
              <div class="col-md-6">
                <label class="form-label">Phone</label>
                <input class="form-control" value="+91-XXXXXXXXXX" />
              </div>
              <div class="col-md-6">
                <label class="form-label">Station</label>
                <input class="form-control" value="Patna Central" />
              </div>
              <div class="col-12 text-end mt-2">
                <button class="btn btn-primary btn-sm">Save Changes</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../assets/js/police-dashboard-realtime.js"></script>
  <!-- Reports CRUD Operations -->
  <script src="../assets/js/reports-crud.js"></script>
  <!-- Add Missing Report -->
  <script src="../assets/js/add-missing-report.js"></script>
  <!-- Add Found Person -->
  <script src="../assets/js/add-found-person.js"></script>
  <!-- Sidebar Toggle Fix JS (must load after police-dashboard-realtime.js) -->
  <script src="../assets/js/sidebar-toggle.js"></script>
  <script src="../assets/js/dashboardP_cards.js"></script>
  <script src="../assets/js/dashboardP_charts.js"></script>
  <script src="../assets/js/ai_match.js"></script>

</body>
</html>
