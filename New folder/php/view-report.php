<?php
/**
 * View Report Page - Modern UI
 */

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['fullname'] ?? 'User';
$report_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($report_id <= 0) {
    header('Location: my-reports.php');
    exit;
}

require_once '../Database/Conn_db.php';

$stmt = $conn->prepare("SELECT * FROM missing_reports WHERE report_id = ? AND user_id = ?");
$stmt->bind_param('ii', $report_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if (!$row = $result->fetch_assoc()) {
    header('Location: my-reports.php');
    exit;
}
$report = $row;
$stmt->close();

// Get AI matches
$matches = [];
$matchResult = $conn->query("SELECT m.*, fp.name as found_name, fp.found_location, fp.date_found, fp.photo_path as found_photo FROM ai_matches m LEFT JOIN found_persons fp ON m.found_id = fp.found_id WHERE m.report_id = $report_id AND m.status = 'approved' ORDER BY m.match_percent DESC");
if ($matchResult) {
    while ($row = $matchResult->fetch_assoc()) {
        $matches[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>HopeFinder — Report #R<?= $report_id ?></title>
  <link rel="icon" href="../logo.png" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet"/>
  <link href="../assets/css/police-dashboard-modern.css" rel="stylesheet"/>
  <link href="../assets/css/sidebar-toggle.css" rel="stylesheet"/>
  <style>
    .report-card { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 16px; padding: 30px; }
    .person-photo-large { width: 100%; max-width: 300px; border-radius: 16px; object-fit: cover; border: 1px solid var(--border-color); }
    .info-row { display: flex; padding: 12px 0; border-bottom: 1px solid var(--border-color); }
    .info-row:last-child { border-bottom: none; }
    .info-label { color: var(--text-muted); width: 150px; flex-shrink: 0; }
    .info-value { color: var(--text-primary); }
    .timeline { position: relative; padding-left: 30px; }
    .timeline::before { content: ''; position: absolute; left: 8px; top: 0; bottom: 0; width: 2px; background: var(--border-color); }
    .timeline-item { position: relative; padding-bottom: 25px; }
    .timeline-item:last-child { padding-bottom: 0; }
    .timeline-icon { position: absolute; left: -30px; width: 20px; height: 20px; border-radius: 50%; background: var(--card-bg); border: 2px solid var(--border-color); display: flex; align-items: center; justify-content: center; font-size: 10px; color: var(--text-muted); }
    .timeline-item.completed .timeline-icon { background: var(--primary); border-color: var(--primary); color: #fff; }
    .timeline-content { color: var(--text-muted); font-size: 0.9rem; }
    .timeline-item.completed .timeline-content { color: var(--text-primary); }
    .match-card { background: linear-gradient(135deg, rgba(99,102,241,0.1), rgba(118,75,162,0.1)); border: 1px solid rgba(99,102,241,0.2); border-radius: 16px; padding: 25px; margin-bottom: 20px; }
    .match-photo { width: 100px; height: 100px; border-radius: 12px; object-fit: cover; border: 1px solid var(--border-color); }
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
      <li><a href="add-missing-report.php"><span class="nav-icon"><i class="bi bi-person-plus"></i></span><span class="sidebar-text">Report Missing</span></a></li>
      <li><a href="search-missing.php"><span class="nav-icon"><i class="bi bi-search"></i></span><span class="sidebar-text">Search</span></a></li>
      <li><a href="my-reports.php" class="active"><span class="nav-icon"><i class="bi bi-folder-check"></i></span><span class="sidebar-text">My Reports</span></a></li>
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
        <div class="topbar-title">
          <h5>Report #R<?= $report_id ?></h5>
          <small><?= htmlspecialchars($report['missing_name']) ?></small>
        </div>
      </div>
      <div class="topbar-right">
        <div class="user-profile">
          <img src="https://ui-avatars.com/api/?name=<?= urlencode($user_name) ?>&background=667eea&color=fff" class="user-avatar">
          <span class="user-name"><?= htmlspecialchars($user_name) ?></span>
        </div>
      </div>
    </div>

    <section id="view-report">
      <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="UserD.php" style="color:var(--text-muted); text-decoration:none;">Dashboard</a></li>
          <li class="breadcrumb-item"><a href="my-reports.php" style="color:var(--text-muted); text-decoration:none;">My Reports</a></li>
          <li class="breadcrumb-item active" style="color:var(--text-primary);">#R<?= $report_id ?></li>
        </ol>
      </nav>
      
      <div class="row g-4">
        <div class="col-lg-8">
          <div class="report-card">
            <div class="d-flex justify-content-between align-items-start mb-4">
              <div>
                <h3><?= htmlspecialchars($report['missing_name']) ?></h3>
                <span class="badge bg-<?= $report['status'] == 'pending' ? 'warning' : ($report['status'] == 'verified' ? 'primary' : 'success') ?>"><?= $report['status'] == 'closed' ? 'Found/Matched' : ucfirst($report['status']) ?></span>
              </div>
              <a href="my-reports.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-2"></i>Back</a>
            </div>
            
            <div class="row g-4">
              <div class="col-md-4">
                <img src="<?= $report['photo'] ? '../' . htmlspecialchars($report['photo']) : 'https://via.placeholder.com/300' ?>" class="person-photo-large">
              </div>
              <div class="col-md-8">
                <div class="info-row"><span class="info-label">Age/Gender</span><span class="info-value"><?= $report['age'] ?? 'N/A' ?> / <?= ucfirst($report['gender'] ?? 'N/A') ?></span></div>
                <div class="info-row"><span class="info-label">Last Seen</span><span class="info-value"><?= htmlspecialchars($report['last_seen_location']) ?></span></div>
                <div class="info-row"><span class="info-label">Last Seen DateTime</span><span class="info-value"><?= date('d M Y, h:i A', strtotime($report['last_seen_datetime'])) ?></span></div>
                <div class="info-row"><span class="info-label">Submitted</span><span class="info-value"><?= date('d M Y, h:i A', strtotime($report['created_at'])) ?></span></div>
              </div>
            </div>
            
            <?php if (!empty($report['description'])): ?>
              <div class="mt-4 pt-4" style="border-top:1px solid var(--border-color);">
                <h6 class="text-muted mb-2">Description</h6>
                <p style="color:var(--text-primary);"><?= nl2br(htmlspecialchars($report['description'])) ?></p>
              </div>
            <?php endif; ?>
            
            <div class="mt-4 pt-4" style="border-top:1px solid var(--border-color);">
              <h6 class="text-muted mb-3">Contact Information</h6>
              <div class="row g-3">
                <div class="col-md-4"><small class="text-muted">Name</small><div style="color:var(--text-primary);"><?= htmlspecialchars($report['contact_name'] ?? 'N/A') ?></div></div>
                <div class="col-md-4"><small class="text-muted">Contact Number</small><div style="color:var(--text-primary);"><?= htmlspecialchars($report['contact_number'] ?? 'N/A') ?></div></div>
                <div class="col-md-4"><small class="text-muted">Relationship</small><div style="color:var(--text-primary);"><?= htmlspecialchars($report['contact_relation'] ?? 'N/A') ?></div></div>
              </div>
            </div>
          </div>
        </div>
        
        <div class="col-lg-4">
          <div class="report-card mb-4">
            <h5 class="mb-4"><i class="bi bi-clock me-2"></i>Status Timeline</h5>
            <div class="timeline">
              <div class="timeline-item completed">
                <div class="timeline-icon"><i class="bi bi-check"></i></div>
                <div class="timeline-content"><strong>Submitted</strong><div class="text-muted" style="font-size:0.8rem;"><?= date('M d, Y h:i A', strtotime($report['created_at'])) ?></div></div>
              </div>
              <?php if ($report['status'] != 'pending'): ?>
              <div class="timeline-item completed">
                <div class="timeline-icon"><i class="bi bi-check"></i></div>
                <div class="timeline-content"><strong>Verified</strong><div class="text-muted" style="font-size:0.8rem;">Report verified by police</div></div>
              </div>
              <?php endif; ?>
              <?php if ($report['status'] == 'closed'): ?>
              <div class="timeline-item completed">
                <div class="timeline-icon"><i class="bi bi-check"></i></div>
                <div class="timeline-content"><strong>Found/Matched</strong><div class="text-muted" style="font-size:0.8rem;">A match has been found!</div></div>
              </div>
              <?php endif; ?>
            </div>
          </div>
          
          <div class="report-card">
            <h5 class="mb-4"><i class="bi bi-cpu me-2" style="color:var(--primary);"></i>AI Match Results</h5>
            <?php if (empty($matches)): ?>
              <div class="text-center py-4 text-muted">
                <i class="bi bi-search fs-1"></i>
                <p class="mt-2 mb-0">No matches found yet</p>
                <small>AI is continuously searching</small>
              </div>
            <?php else: ?>
              <?php foreach ($matches as $match): ?>
                <div class="match-card">
                  <div class="row g-3 align-items-center">
                    <div class="col-4"><img src="<?= $match['found_photo'] ? '../' . htmlspecialchars($match['found_photo']) : 'https://via.placeholder.com/100' ?>" class="match-photo"></div>
                    <div class="col-8">
                      <div style="font-size:2rem;font-weight:700;background:linear-gradient(135deg,var(--primary),var(--secondary));-webkit-background-clip:text;-webkit-text-fill-color:transparent;"><?= $match['match_percent'] ?>%</div>
                      <div style="color:var(--text-muted);font-size:0.85rem;">
                        <div><i class="bi bi-person me-1"></i><?= htmlspecialchars($match['found_name'] ?? 'Unknown') ?></div>
                        <div><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($match['found_location']) ?></div>
                        <div><i class="bi bi-calendar me-1"></i><?= date('M d, Y', strtotime($match['date_found'])) ?></div>
                      </div>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </section>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../assets/js/sidebar-toggle.js"></script>
</body>
</html>

        <a href="UserD.php">
          <i class="bi bi-speedometer2"></i>
          <span class="sidebar-text">Dashboard</span>
        </a>
      </li>
      <li>
        <a href="add-missing-report.php">
          <i class="bi bi-person-plus"></i>
          <span class="sidebar-text">Report Missing</span>
        </a>
      </li>
      <li>
        <a href="search-missing.php">
          <i class="bi bi-search"></i>
          <span class="sidebar-text">Search</span>
        </a>
      </li>
      <li>
        <a href="my-reports.php" class="active">
          <i class="bi bi-folder-check"></i>
          <span class="sidebar-text">My Reports</span>
        </a>
      </li>
      <li>
        <a href="notifications.php">
          <i class="bi bi-bell-fill"></i>
          <span class="sidebar-text">Notifications</span>
        </a>
      </li>
      <li>
        <a href="profile.php">
          <i class="bi bi-person-circle"></i>
          <span class="sidebar-text">Profile</span>
        </a>
      </li>
      <li>
        <a href="Logout.php">
          <i class="bi bi-box-arrow-right"></i>
          <span class="sidebar-text">Logout</span>
        </a>
      </li>
    </ul>
  </nav>

  <!-- MAIN CONTENT -->
  <main id="content">
    <div class="topbar">
      <button id="sidebarCollapse" class="sidebar-toggle">
        <i class="bi bi-list"></i>
      </button>
      
      <div class="topbar-right">
        <div class="user-profile">
          <img src="https://ui-avatars.com/api/?name=<?= urlencode($user_name) ?>&background=667eea&color=fff" class="user-avatar" alt="User">
          <span class="user-name"><?= htmlspecialchars($user_name) ?></span>
          <i class="bi bi-caret-down-fill user-dropdown"></i>
        </div>
      </div>
    </div>

    <div class="dashboard-content">
      <!-- Breadcrumb -->
      <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="UserD.php" class="text-decoration-none text-muted">Dashboard</a></li>
          <li class="breadcrumb-item"><a href="my-reports.php" class="text-decoration-none text-muted">My Reports</a></li>
          <li class="breadcrumb-item active text-white">#R<?= $report_id ?></li>
        </ol>
      </nav>
      
      <div class="row g-4">
        <!-- Report Details -->
        <div class="col-lg-8">
          <div class="report-detail-card">
            <div class="d-flex justify-content-between align-items-start mb-4">
              <div>
                <h3 class="mb-1"><?= htmlspecialchars($report['missing_name']) ?></h3>
                <span class="status-badge status-<?= $report['status'] ?>">
                  <?= $report['status'] == 'closed' ? 'Found/Matched' : ucfirst($report['status']) ?>
                </span>
              </div>
              <a href="my-reports.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Back
              </a>
            </div>
            
            <div class="row g-4">
              <div class="col-md-4">
                <img src="<?= $report['photo'] ? '../' . htmlspecialchars($report['photo']) : 'https://via.placeholder.com/300' ?>" 
                     class="person-photo-large" alt="Missing Person Photo">
              </div>
              <div class="col-md-8">
                <div class="info-row">
                  <span class="info-label">Report ID</span>
                  <span class="info-value"><strong>#R<?= $report['report_id'] ?></strong></span>
                </div>
                <div class="info-row">
                  <span class="info-label">Age / Gender</span>
                  <span class="info-value"><?= $report['age'] ?? 'N/A' ?> / <?= ucfirst($report['gender'] ?? 'N/A') ?></span>
                </div>
                <div class="info-row">
                  <span class="info-label">Last Seen</span>
                  <span class="info-value"><?= htmlspecialchars($report['last_seen_location']) ?></span>
                </div>
                <div class="info-row">
                  <span class="info-label">Date Missing</span>
                  <span class="info-value"><?= date('F d, Y', strtotime($report['last_seen_datetime'])) ?></span>
                </div>
                <div class="info-row">
                  <span class="info-label">Submitted On</span>
                  <span class="info-value"><?= date('F d, Y h:i A', strtotime($report['created_at'])) ?></span>
                </div>
              </div>
            </div>
            
            <?php if (!empty($report['description'])): ?>
              <div class="mt-4">
                <h6 class="text-muted mb-2">Description</h6>
                <p class="text-white"><?= nl2br(htmlspecialchars($report['description'])) ?></p>
              </div>
            <?php endif; ?>
            
            <!-- Contact Info -->
            <div class="mt-4 pt-4 border-top" style="border-color: rgba(255,255,255,0.1)!important;">
              <h6 class="text-muted mb-3">Contact Information</h6>
              <div class="row g-3">
                <div class="col-md-4">
                  <small class="text-muted">Contact Name</small>
                  <div class="text-white"><?= htmlspecialchars($report['contact_name'] ?? 'N/A') ?></div>
                </div>
                <div class="col-md-4">
                  <small class="text-muted">Phone</small>
                  <div class="text-white"><?= htmlspecialchars($report['contact_phone'] ?? 'N/A') ?></div>
                </div>
                <div class="col-md-4">
                  <small class="text-muted">Relationship</small>
                  <div class="text-white"><?= htmlspecialchars($report['contact_relation'] ?? 'N/A') ?></div>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Sidebar -->
        <div class="col-lg-4">
          <!-- Status Timeline -->
          <div class="report-detail-card mb-4">
            <h5 class="mb-4">Status Timeline</h5>
            <div class="timeline">
              <div class="timeline-item completed">
                <div class="timeline-icon"><i class="bi bi-check"></i></div>
                <div class="timeline-content">
                  <strong>Report Submitted</strong>
                  <div class="timeline-date"><?= date('M d, Y h:i A', strtotime($report['created_at'])) ?></div>
                </div>
              </div>
              <?php if ($report['status'] != 'pending'): ?>
                <div class="timeline-item completed">
                  <div class="timeline-icon"><i class="bi bi-check"></i></div>
                  <div class="timeline-content">
                    <strong>Verified by Police</strong>
                    <div class="timeline-date">Report has been verified</div>
                  </div>
                </div>
              <?php endif; ?>
              <?php if ($report['status'] == 'assigned' || $report['status'] == 'closed'): ?>
                <div class="timeline-item completed">
                  <div class="timeline-icon"><i class="bi bi-check"></i></div>
                  <div class="timeline-content">
                    <strong>Assigned to Officer</strong>
                    <div class="timeline-date">An officer is working on this case</div>
                  </div>
                </div>
              <?php endif; ?>
              <?php if ($report['status'] == 'closed'): ?>
                <div class="timeline-item completed">
                  <div class="timeline-icon"><i class="bi bi-check"></i></div>
                  <div class="timeline-content">
                    <strong>Found/Matched</strong>
                    <div class="timeline-date">A matching person has been found!</div>
                  </div>
                </div>
              <?php endif; ?>
            </div>
          </div>
          
          <!-- AI Match Results -->
          <div class="report-detail-card">
            <h5 class="mb-4">
              <i class="bi bi-cpu me-2" style="color: #667eea;"></i>
              AI Match Results
            </h5>
            
            <?php if (empty($matches)): ?>
              <div class="text-center py-4">
                <i class="bi bi-search fs-1 text-muted"></i>
                <p class="text-muted mt-2 mb-0">No matches found yet</p>
                <small class="text-muted">Our AI system is continuously searching for potential matches.</small>
              </div>
            <?php else: ?>
              <?php foreach ($matches as $match): ?>
                <div class="match-card">
                  <div class="row g-3 align-items-center">
                    <div class="col-4">
                      <img src="<?= $match['found_photo'] ? '../' . htmlspecialchars($match['found_photo']) : 'https://via.placeholder.com/120' ?>" 
                           class="match-photo" alt="Found Person">
                    </div>
                    <div class="col-8">
                      <div class="match-percent"><?= $match['match_percent'] ?>%</div>
                      <div class="match-details">
                        <div><i class="bi bi-person me-1"></i> <?= htmlspecialchars($match['found_name'] ?? 'Unknown') ?></div>
                        <div><i class="bi bi-geo-alt me-1"></i> <?= htmlspecialchars($match['found_location']) ?></div>
                        <div><i class="bi bi-calendar me-1"></i> Found: <?= date('M d, Y', strtotime($match['date_found'])) ?></div>
                      </div>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../JavaScript/Dashboard.js"></script>
</body>
</html>

