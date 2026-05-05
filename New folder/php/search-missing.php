<?php
/**
 * Search Missing Persons Page
 * Allows public users to search through all verified missing persons
 */

session_start();

// Check if user is logged in (optional for search, but required for details)
$user_id = $_SESSION['user_id'] ?? 0;
$user_name = $_SESSION['fullname'] ?? 'Guest';

require_once '../Database/Conn_db.php';

// Handle search
$searchResults = [];
$searchTerm = isset($_GET['q']) ? trim($_GET['q']) : '';
$searchGender = isset($_GET['gender']) ? $_GET['gender'] : '';

$sql = "SELECT * FROM missing_reports WHERE status IN ('verified', 'assigned')";
$params = [];
$types = '';

if (!empty($searchTerm)) {
    $sql .= " AND (missing_name LIKE ? OR last_seen_location LIKE ?)";
    $params[] = "%$searchTerm%";
    $params[] = "%$searchTerm%";
    $types .= 'ss';
}

if (!empty($searchGender)) {
    $sql .= " AND gender = ?";
    $params[] = $searchGender;
    $types .= 's';
}

$sql .= " ORDER BY created_at DESC LIMIT 100";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $searchResults[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Search - HopeFinder</title>
  <link rel="icon" href="../logo.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet"/>
  <link href="../Css/Dashboard.css" rel="stylesheet"/>
  <style>
    .search-card {
      background: #1a1a2e;
      border: 1px solid rgba(255,255,255,0.1);
      border-radius: 16px;
      overflow: hidden;
      transition: all 0.3s ease;
    }
    .search-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 40px rgba(0,0,0,0.3);
    }
    .search-photo {
      width: 100%;
      height: 200px;
      object-fit: cover;
      border-bottom: 1px solid rgba(255,255,255,0.1);
    }
    .search-card-body {
      padding: 20px;
    }
    .search-name {
      color: #fff;
      font-size: 1.1rem;
      font-weight: 600;
      margin-bottom: 5px;
    }
    .search-info {
      color: rgba(255,255,255,0.6);
      font-size: 0.85rem;
    }
    .search-badge {
      display: inline-block;
      padding: 4px 10px;
      border-radius: 20px;
      font-size: 0.75rem;
      font-weight: 500;
      margin-top: 10px;
    }
    .search-badge.male { background: rgba(13,110,253,0.2); color: #0d6efd; }
    .search-badge.female { background: rgba(255,193,7,0.2); color: #ffc107; }
    .search-badge.other { background: rgba(111,66,193,0.2); color: #a084ca; }
    .search-form {
      background: #1a1a2e;
      border: 1px solid rgba(255,255,255,0.1);
      border-radius: 16px;
      padding: 25px;
    }
    .form-control, .form-select {
      background: rgba(255,255,255,0.05);
      border: 1px solid rgba(255,255,255,0.1);
      border-radius: 10px;
      color: #fff;
      padding: 12px 16px;
    }
    .form-control:focus, .form-select:focus {
      background: rgba(255,255,255,0.08);
      border-color: #667eea;
      box-shadow: 0 0 0 4px rgba(102,126,234,0.1);
      color: #fff;
    }
    .form-select option {
      background: #1a1a2e;
    }
    .empty-state {
      text-align: center;
      padding: 60px 20px;
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
    
    <?php if ($user_id > 0): ?>
    <div class="sidebar-profile">
      <img src="https://ui-avatars.com/api/?name=<?= urlencode($user_name) ?>&background=667eea&color=fff" class="rounded-circle">
      <div>
        <div><?= htmlspecialchars($user_name) ?></div>
        <span><i class="bi bi-circle-fill text-success" style="font-size: 8px;"></i> Online</span>
      </div>
    </div>
    <?php endif; ?>
    
    <ul class="nav flex-column">
      <li><a href="<?= $user_id > 0 ? 'UserD.php' : '../Login.html' ?>"><i class="bi bi-speedometer2"></i><span class="sidebar-text">Dashboard</span></a></li>
      <li><a href="<?= $user_id > 0 ? 'add-missing-report.php' : '../Login.html' ?>"><i class="bi bi-person-plus"></i><span class="sidebar-text">Report Missing</span></a></li>
      <li><a href="search-missing.php" class="active"><i class="bi bi-search"></i><span class="sidebar-text">Search</span></a></li>
      <?php if ($user_id > 0): ?>
      <li><a href="my-reports.php"><i class="bi bi-folder-check"></i><span class="sidebar-text">My Reports</span></a></li>
      <li><a href="notifications.php"><i class="bi bi-bell-fill"></i><span class="sidebar-text">Notifications</span></a></li>
      <li><a href="profile.php"><i class="bi bi-person-circle"></i><span class="sidebar-text">Profile</span></a></li>
      <li><a href="Logout.php"><i class="bi bi-box-arrow-right"></i><span class="sidebar-text">Logout</span></a></li>
      <?php endif; ?>
    </ul>
  </nav>

  <!-- MAIN CONTENT -->
  <main id="content">
    <div class="topbar">
      <button id="sidebarCollapse" class="sidebar-toggle"><i class="bi bi-list"></i></button>
      <div class="topbar-right">
        <?php if ($user_id > 0): ?>
        <div class="user-profile">
          <img src="https://ui-avatars.com/api/?name=<?= urlencode($user_name) ?>&background=667eea&color=fff" class="user-avatar" alt="User">
          <span class="user-name"><?= htmlspecialchars($user_name) ?></span>
        </div>
        <?php else: ?>
        <a href="../Login.html" class="btn btn-primary btn-sm">Login</a>
        <?php endif; ?>
      </div>
    </div>

    <div class="dashboard-content">
      <h2 class="mb-4">Search Missing Persons</h2>
      
      <!-- Search Form -->
      <div class="search-form mb-4">
        <form method="GET" class="row g-3">
          <div class="col-md-6">
            <div class="input-group">
              <span class="input-group-text bg-transparent border-end-0" style="border-radius: 10px 0 0 10px;"><i class="bi bi-search"></i></span>
              <input type="text" class="form-control border-start-0" name="q" 
                     placeholder="Search by name or location..." value="<?= htmlspecialchars($searchTerm) ?>">
            </div>
          </div>
          <div class="col-md-3">
            <select class="form-select" name="gender">
              <option value="">All Genders</option>
              <option value="male" <?= $searchGender == 'male' ? 'selected' : '' ?>>Male</option>
              <option value="female" <?= $searchGender == 'female' ? 'selected' : '' ?>>Female</option>
              <option value="other" <?= $searchGender == 'other' ? 'selected' : '' ?>>Other</option>
            </select>
          </div>
          <div class="col-md-3">
            <button type="submit" class="btn btn-primary w-100">
              <i class="bi bi-search me-2"></i>Search
            </button>
          </div>
        </form>
      </div>
      
      <!-- Results -->
      <?php if (!empty($searchTerm) || !empty($searchGender)): ?>
        <p class="text-muted mb-3">
          <?= count($searchResults) ?> result(s) found
          <?php if (!empty($searchTerm)): ?> for "<?= htmlspecialchars($searchTerm) ?>"<?php endif; ?>
        </p>
      <?php endif; ?>
      
      <?php if (empty($searchResults)): ?>
        <div class="empty-state">
          <i class="bi bi-search"></i>
          <h4 class="text-white">No Results Found</h4>
          <p class="text-muted">Try different search terms or filters.</p>
        </div>
      <?php else: ?>
        <div class="row g-4">
          <?php foreach ($searchResults as $person): ?>
            <div class="col-md-6 col-lg-4">
              <div class="search-card">
                <img src="<?= $person['photo'] ? '../' . htmlspecialchars($person['photo']) : 'https://via.placeholder.com/400x200' ?>" 
                     class="search-photo" alt="<?= htmlspecialchars($person['missing_name']) ?>">
                <div class="search-card-body">
                  <h5 class="search-name"><?= htmlspecialchars($person['missing_name']) ?></h5>
                  <p class="search-info">
                    <i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($person['last_seen_location']) ?><br>
                    <i class="bi bi-calendar me-1"></i>Missing: <?= date('M d, Y', strtotime($person['last_seen_datetime'])) ?><br>
                    <?php if ($person['age']): ?>
                    <i class="bi bi-person me-1"></i>Age: <?= $person['age'] ?>
                    <?php endif; ?>
                  </p>
                  <span class="search-badge <?= $person['gender'] ?? 'other' ?>">
                    <?= ucfirst($person['gender'] ?? 'Unknown') ?>
                  </span>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../JavaScript/Dashboard.js"></script>
</body>
</html>

