<?php
/**
 * My Reports Page - Modern UI
 */

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['fullname'] ?? 'User';

require_once '../Database/Conn_db.php';

$reports = [];
$sql = "SELECT * FROM missing_reports WHERE user_id = $user_id ORDER BY created_at DESC";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $reports[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>HopeFinder — My Reports</title>
  <link rel="icon" href="../logo.png" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet"/>
  <link href="../assets/css/police-dashboard-modern.css" rel="stylesheet"/>
  <link href="../assets/css/sidebar-toggle.css" rel="stylesheet"/>
  <style>
    .reports-card { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 16px; overflow: hidden; }
    .person-photo { width: 50px; height: 50px; border-radius: 10px; object-fit: cover; border: 1px solid var(--border-color); }
    .search-box input { padding-left: 45px; background: var(--bg-hover); border: 1px solid var(--border-color); border-radius: 10px; color: var(--text-primary); }
    .search-box input:focus { background: var(--bg-hover); border-color: var(--primary); box-shadow: 0 0 0 4px rgba(99,102,241,0.1); color: var(--text-primary); }
    .search-box i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--text-muted); }
    .filter-select { background: var(--bg-hover); border: 1px solid var(--border-color); border-radius: 10px; color: var(--text-primary); padding: 10px 15px; }
    .filter-select:focus { border-color: var(--primary); box-shadow: 0 0 0 4px rgba(99,102,241,0.1); }
    .filter-select option { background: var(--card-bg); }
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
        <div class="topbar-title"><h5>My Reports</h5></div>
      </div>
      <div class="topbar-right">
        <div class="user-profile">
          <img src="https://ui-avatars.com/api/?name=<?= urlencode($user_name) ?>&background=667eea&color=fff" class="user-avatar">
          <span class="user-name"><?= htmlspecialchars($user_name) ?></span>
        </div>
      </div>
    </div>

    <section id="my-reports">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>My Reports</h2>
        <a href="add-missing-report.php" class="btn btn-primary"><i class="bi bi-plus-lg me-2"></i>New Report</a>
      </div>
      
      <div class="row g-3 mb-4">
        <div class="col-md-6">
          <div class="search-box">
            <i class="bi bi-search"></i>
            <input type="text" class="form-control" id="searchInput" placeholder="Search by name or location...">
          </div>
        </div>
        <div class="col-md-3">
          <select class="form-select filter-select" id="statusFilter">
            <option value="">All Status</option>
            <option value="pending">Pending</option>
            <option value="verified">Verified</option>
            <option value="closed">Found/Matched</option>
          </select>
        </div>
      </div>
      
      <div class="reports-card">
        <?php if (empty($reports)): ?>
          <div class="text-center py-5 text-muted">
            <i class="bi bi-folder-x fs-1"></i>
            <p class="mt-2">No reports yet. <a href="add-missing-report.php" style="color:var(--primary-light);">Submit your first report</a></p>
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table mb-0">
              <thead>
                <tr>
                  <th>Photo</th>
                  <th>Report ID</th>
                  <th>Person Name</th>
                  <th>Last Seen</th>
                  <th>Last Seen DateTime</th>
                  <th>Status</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody id="reportsTableBody">
                <?php foreach ($reports as $report): ?>
                <tr data-status="<?= $report['status'] ?>" data-name="<?= strtolower($report['missing_name']) ?>" data-location="<?= strtolower($report['last_seen_location']) ?>">
                  <td><img src="<?= $report['photo'] ? '../' . htmlspecialchars($report['photo']) : 'https://via.placeholder.com/50' ?>" class="person-photo"></td>
                  <td><strong>#R<?= $report['report_id'] ?></strong></td>
                  <td><?= htmlspecialchars($report['missing_name']) ?></td>
                  <td><?= htmlspecialchars($report['last_seen_location']) ?></td>
                  <td><?= date('d M Y, h:i A', strtotime($report['last_seen_datetime'])) ?></td>
                  <td><span class="badge bg-<?= $report['status'] == 'pending' ? 'warning' : ($report['status'] == 'verified' ? 'primary' : 'success') ?>"><?= $report['status'] == 'closed' ? 'Found' : ucfirst($report['status']) ?></span></td>
                  <td><a href="view-report.php?id=<?= $report['report_id'] ?>" class="btn btn-sm btn-outline-info"><i class="bi bi-eye"></i></a></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </section>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../assets/js/sidebar-toggle.js"></script>
  <script>
    document.getElementById('searchInput').addEventListener('keyup', filterReports);
    document.getElementById('statusFilter').addEventListener('change', filterReports);
    
    function filterReports() {
      const search = document.getElementById('searchInput').value.toLowerCase();
      const status = document.getElementById('statusFilter').value;
      
      document.querySelectorAll('#reportsTableBody tr').forEach(row => {
        const name = row.dataset.name || '';
        const location = row.dataset.location || '';
        const rowStatus = row.dataset.status || '';
        const matchesSearch = search === '' || name.includes(search) || location.includes(search);
        const matchesStatus = status === '' || rowStatus === status;
        row.style.display = (matchesSearch && matchesStatus) ? '' : 'none';
      });
    }
  </script>
</body>
</html>

