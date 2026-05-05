const sidebarCollapse = document.getElementById('sidebarCollapse');
const body = document.body;
const sidebar = document.getElementById('sidebar');

function toggleSidebar() {
  if (window.innerWidth <= 991) {
    if (body.classList.contains('sidebar-expanded')) {
      body.classList.remove('sidebar-expanded');
    } else {
      body.classList.add('sidebar-expanded');
    }
  } else {
    if (body.classList.contains('sidebar-collapsed')) {
      body.classList.remove('sidebar-collapsed');
    } else {
      body.classList.add('sidebar-collapsed');
    }
  }
}

// Dashboard Stats
fetch("../admin/api/getDashboardStats.php")
  .then(res => res.json())
  .then(data => {
    if (data.status === 'success') {
      const totalMissingEl = document.getElementById("totalMissing");
      const totalFoundEl = document.getElementById("totalFound");
      const todayDetectionsEl = document.getElementById("todayDetections");
      const aiSuccessEl = document.getElementById("aiSuccess");
      
      if (totalMissingEl) totalMissingEl.innerText = data.total_missing ?? 0;
      if (totalFoundEl) totalFoundEl.innerText = data.total_found ?? 0;
      if (todayDetectionsEl) todayDetectionsEl.innerText = data.today_detections ?? 0;
      if (aiSuccessEl) aiSuccessEl.innerText = data.ai_success ?? 0;
    }
  })
  .catch(err => console.error("Dashboard stats error:", err));

// Toggle button click
sidebarCollapse.addEventListener('click', toggleSidebar);

// Close overlay sidebar by clicking outside on small screens
document.addEventListener('click', (event) => {
  if (window.innerWidth <= 991 && body.classList.contains('sidebar-expanded')) {
    if (!sidebar.contains(event.target) && event.target !== sidebarCollapse) {
      body.classList.remove('sidebar-expanded');
    }
  }
});

// ==========================================
// HELPER FUNCTIONS
// ==========================================

/**
 * Escape HTML to prevent XSS
 */
function escapeHtml(text) {
  if (!text) return '';
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

/**
 * Capitalize first letter
 */
function capitalizeFirst(str) {
  if (!str) return '';
  return str.charAt(0).toUpperCase() + str.slice(1);
}

/**
 * Get status display class based on status value
 */
function getStatusClass(status) {
  // Backend returns 'active' or 'blocked' for users
  // Police status comes from users.status field
  if (status === 'active') return 'success';
  if (status === 'blocked') return 'danger';
  return 'secondary';
}

/**
 * Get status display label
 */
function getStatusLabel(status) {
  if (status === 'active') return 'Active';
  if (status === 'blocked') return 'Blocked';
  return capitalizeFirst(status);
}

/**
 * Get toggle icon class based on status
 */
function getToggleIconClass(status) {
  return status === 'active' ? 'bi-shield-lock text-warning' : 'bi-shield-check text-success';
}

/**
 * Get toggle title based on status
 */
function getToggleTitle(status) {
  return status === 'active' ? 'Block/Suspend' : 'Activate';
}

// ==========================================
// PDF PREVIEW FUNCTIONS
// ==========================================

/**
 * Open PDF in iframe modal (same page, modal, no download)
 * @param {string} pdfUrl - URL to the PDF file
 * @param {string} title - Title for the modal header
 */
function openPdfPreview(pdfUrl, title) {
  const modal = document.getElementById('pdfPreviewModal');
  const iframe = document.getElementById('pdfPreviewIframe');
  const modalTitle = document.getElementById('pdfPreviewModalTitle');
  
  if (!modal || !iframe) {
    Swal.fire('Error', 'PDF preview modal not found', 'error');
    return;
  }
  
  // Set the iframe source - using embed tag for better compatibility
  iframe.src = pdfUrl;
  modalTitle.textContent = title || 'PDF Preview';
  
  // Show modal using Bootstrap 5
  const bsModal = new bootstrap.Modal(modal);
  bsModal.show();
}

/**
 * Close PDF preview modal
 */
function closePdfPreview() {
  const iframe = document.getElementById('pdfPreviewIframe');
  if (iframe) {
    iframe.src = '';
  }
}

// ==========================================
// USERS MANAGEMENT
// ==========================================

// Load Users
function loadUsers() {
  fetch("../admin/api/getUsers.php")
    .then(res => res.json())
    .then(users => {
      // Handle if response is array directly or wrapped in object
      const userList = Array.isArray(users) ? users : (users.data || []);
      
      if (userList.length === 0) {
        document.getElementById("usersTable").innerHTML = 
          '<tr><td colspan="6" class="text-center text-muted">No users found</td></tr>';
        return;
      }
      
      let html = "";
      userList.forEach(u => {
        // fullname is not in users table - use email prefix or 'User'
        const displayName = u.fullname || u.email.split('@')[0] || 'User';
        
        html += `
          <tr>
            <td>#U${u.user_id}</td>
            <td>${escapeHtml(displayName)}</td>
            <td>${escapeHtml(u.email)}</td>
            <td>${u.created_at || '-'}</td>
            <td>
              <span class="badge ${u.status === 'active' ? 'bg-success' : 'bg-danger'}">
                ${getStatusLabel(u.status)}
              </span>
            </td>
            <td class="table-actions">
              <i class="bi ${getToggleIconClass(u.status)} btn-toggle-user"
                 data-id="${u.user_id}" data-status="${u.status}" title="${getToggleTitle(u.status)}">
              </i>
              <i class="bi bi-trash text-danger btn-delete-user"
                 data-id="${u.user_id}" title="Delete User">
              </i>
            </td>
          </tr>
        `;
      });
      document.getElementById("usersTable").innerHTML = html;
    })
    .catch(err => {
      console.error("FETCH ERROR =", err);
      document.getElementById("usersTable").innerHTML = 
        '<tr><td colspan="6" class="text-center text-danger">Error loading users</td></tr>';
    });
}

loadUsers();

// Delete User
document.addEventListener("click", function (e) {
  const deleteBtn = e.target.closest(".btn-delete-user");
  if (!deleteBtn) return;

  const id = deleteBtn.dataset.id;

  Swal.fire({
    title: "Delete User?",
    text: "This will permanently delete the user and all associated data.",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Yes, Delete",
    cancelButtonText: "No",
    confirmButtonColor: "#dc3545"
  }).then((result) => {
    if (!result.isConfirmed) return;

    fetch("../admin/api/deleteUser.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: "id=" + id
    })
    .then(res => res.text())
    .then(response => {
      if (response.trim() === "success") {
        Swal.fire("Deleted!", "User has been deleted.", "success");
        deleteBtn.closest("tr").remove();
        loadUsers(); // Refresh table
      } else {
        Swal.fire("Error", "Failed to delete user", "error");
      }
    })
    .catch(err => {
      console.error(err);
      Swal.fire("Error", "Server communication failed", "error");
    });
  });
});

// Block/Unblock User
document.addEventListener("click", function (e) {
  const blockIcon = e.target.closest(".btn-toggle-user");
  if (!blockIcon) return;

  const userId = blockIcon.dataset.id;
  const currentStatus = blockIcon.dataset.status;
  const action = currentStatus === "active" ? "Block" : "Activate";
  const actionLower = action.toLowerCase();

  Swal.fire({
    title: `${action} User?`,
    text: `Do you want to ${actionLower} this user?`,
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Yes",
    cancelButtonText: "No"
  }).then((result) => {
    if (!result.isConfirmed) return;

    fetch("../admin/api/toggleUserStatus.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: "id=" + userId
    })
    .then(res => res.text())
    .then(newStatus => {
      // Update data attribute
      blockIcon.dataset.status = newStatus;
      // Update icon class
      blockIcon.className = `bi ${getToggleIconClass(newStatus)} btn-toggle-user`;
      // Update badge in same row
      const badge = blockIcon.closest("tr").querySelector(".badge");
      if (badge) {
        badge.textContent = getStatusLabel(newStatus);
        badge.className = `badge bg-${getStatusClass(newStatus)}`;
      }
      Swal.fire("Success!", `User has been ${actionLower}ed`, "success");
    })
    .catch(err => {
      console.error(err);
      Swal.fire("Error", "Failed to update status", "error");
    });
  });
});

// Add User Form
document.addEventListener("DOMContentLoaded", function () {
  const addUserForm = document.getElementById("addUserForm");
  if (!addUserForm) return;

  addUserForm.addEventListener("submit", function (e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch("../admin/api/addUser.php", {
      method: "POST",
      body: formData
    })
    .then(async response => {
      if (!response.ok) {
        throw new Error(`Server error: ${response.status} ${response.statusText}`);
      }
      const text = await response.text();
      try {
        return JSON.parse(text);
      } catch (parseError) {
        console.error("JSON parse error:", parseError, "Response:", text);
        throw new Error("Invalid response from server");
      }
    })
    .then(data => {
      console.log("Server response:", data);
      if (data.status === "success") {
        Swal.fire({
          icon: "success",
          title: "Success",
          text: data.message,
          timer: 1500,
          showConfirmButton: false
        });
        addUserModal.hide();
        this.reset();
        loadUsers();
      } else {
        Swal.fire({
          icon: "error",
          title: "Error",
          text: data.message
        });
      }
    })
    .catch(err => {
      console.error("Fetch error:", err);
      Swal.fire({
        icon: "error",
        title: "Server Error",
        text: "Unable to connect to server. Please try again."
      });
    });
  });
});

// ==========================================
// POLICE MANAGEMENT FUNCTIONS
// ==========================================

/**
 * Load and display all police officers in the table
 */
function loadPolice() {
  fetch("../admin/api/getPolice.php")
    .then(res => res.json())
    .then(list => {
      if (!Array.isArray(list) || list.length === 0) {
        document.getElementById("policeTable").innerHTML =
          `<tr><td colspan="6" class="text-center text-muted">No police officers found</td></tr>`;
        return;
      }

      let html = "";
      list.forEach(p => {
        const statusBadgeClass = p.status === 'active' ? 'bg-success' : 'bg-danger';
        const statusLabel = getStatusLabel(p.status);
        const toggleIcon = getToggleIconClass(p.status);
        const toggleTitle = getToggleTitle(p.status);

        html += `
          <tr>
            <td><strong>#P${p.police_id}</strong></td>
            <td>${escapeHtml(p.fullname)}</td>
            <td>${escapeHtml(p.email)}</td>
            <td>${escapeHtml(p.station_name)}, ${escapeHtml(p.district)}</td>
            <td>
              <span class="badge ${statusBadgeClass}">
                ${statusLabel}
              </span>
            </td>
            <td class="table-actions">
              <i class="bi bi-eye text-primary" 
                 title="View Details"
                 style="cursor:pointer;margin-right:8px;"
                 onclick="viewPolice(${p.police_id})"></i>

              <i class="bi bi-pencil-square text-warning" 
                 title="Edit Officer"
                 style="cursor:pointer;margin-right:8px;"
                 onclick="openEditPoliceModal(${p.police_id})"></i>

              <i class="bi ${toggleIcon} btn-toggle-police"
                 title="${toggleTitle}"
                 style="cursor:pointer;margin-right:8px;"
                 data-id="${p.user_id}" data-status="${p.status}"></i>

              <i class="bi bi-trash text-danger" 
                 title="Delete Officer"
                 style="cursor:pointer;"
                 onclick="deletePolice(${p.user_id}, '${escapeHtml(p.fullname)}')"></i>
            </td>
          </tr>
        `;
      });

      document.getElementById("policeTable").innerHTML = html;
    })
    .catch(err => {
      console.error("Load Police Error:", err);
      document.getElementById("policeTable").innerHTML =
        `<tr><td colspan="6" class="text-center text-danger">Error loading police data</td></tr>`;
    });
}

/**
 * View police officer details and PDF
 * Opens modal with police info and PDF preview
 */
function viewPolice(policeId) {
  if (!policeId) {
    Swal.fire('Error', 'Invalid police ID', 'error');
    return;
  }

  fetch(`../admin/api/getSinglePolice.php?police_id=${policeId}`)
    .then(res => res.json())
    .then(r => {
      if (r.status !== "success") {
        Swal.fire('Error', 'Could not load police details', 'error');
        return;
      }

      const p = r.data;

      // Populate view modal fields
      document.getElementById("viewPoliceId").innerText = '#P' + p.police_id;
      document.getElementById("viewFullname").innerText = escapeHtml(p.fullname);
      document.getElementById("viewEmail").innerText = escapeHtml(p.email);
      document.getElementById("viewBadgeNumber").innerText = escapeHtml(p.badge_number);
      document.getElementById("viewStation").innerText = escapeHtml(p.station_name);
      document.getElementById("viewDistrict").innerText = escapeHtml(p.district);
      document.getElementById("viewState").innerText = escapeHtml(p.state);
      document.getElementById("viewStatus").innerHTML = 
        `<span class="badge ${p.status === 'active' ? 'bg-success' : 'bg-danger'}">
          ${getStatusLabel(p.status)}
        </span>`;
      document.getElementById("viewCreatedAt").innerText = p.created_at || '-';

      // Show view modal
      viewPoliceModal.show();
    })
    .catch(err => {
      console.error("View Police Error:", err);
      Swal.fire('Error', 'Failed to load police details', 'error');
    });
}

/**
 * Open Edit Police Modal and populate form
 */
function openEditPoliceModal(policeId) {
  if (!policeId) {
    Swal.fire('Error', 'Invalid police ID', 'error');
    return;
  }

  fetch(`../admin/api/getSinglePolice.php?police_id=${policeId}`)
    .then(res => res.json())
    .then(r => {
      if (r.status !== "success") {
        Swal.fire('Error', 'Could not load police data for editing', 'error');
        return;
      }

      const p = r.data;

      // Populate edit form
      document.getElementById("editPoliceId").value = p.police_id;
      document.getElementById("editFullname").value = p.fullname || '';
      document.getElementById("editBadgeNumber").value = p.badge_number || '';
      document.getElementById("editStationName").value = p.station_name || '';
      document.getElementById("editDistrict").value = p.district || '';
      document.getElementById("editState").value = p.state || '';

      // Show edit modal
      editPoliceModal.show();
    })
    .catch(err => {
      console.error("Open Edit Police Error:", err);
      Swal.fire('Error', 'Failed to load police data', 'error');
    });
}

/**
 * Handle edit police form submission
 */
document.addEventListener("DOMContentLoaded", function () {
  const editForm = document.getElementById("editPoliceForm");
  if (!editForm) return;

  editForm.addEventListener("submit", function (e) {
    e.preventDefault();

    const police_id = document.getElementById("editPoliceId").value;
    const formData = {
      police_id: police_id,
      fullname: document.getElementById("editFullname").value,
      badge_number: document.getElementById("editBadgeNumber").value,
      station_name: document.getElementById("editStationName").value,
      district: document.getElementById("editDistrict").value,
      state: document.getElementById("editState").value
    };

    fetch("../admin/api/updatePolice.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: new URLSearchParams(formData)
    })
    .then(res => res.json())
    .then(data => {
      if (data.status === "success") {
        Swal.fire('Success!', 'Police officer updated successfully', 'success');
        editPoliceModal.hide();
        editForm.reset();
        loadPolice();
      } else {
        Swal.fire('Error', data.message || 'Failed to update police', 'error');
      }
    })
    .catch(err => {
      console.error("Edit Police Error:", err);
      Swal.fire('Error', 'An error occurred while updating', 'error');
    });
  });
});

// Block/Unblock Police
document.addEventListener("click", function (e) {
  const blockIcon = e.target.closest(".btn-toggle-police");
  if (!blockIcon) return;

  const userId = blockIcon.dataset.id;
  const currentStatus = blockIcon.dataset.status;
  const action = currentStatus === "active" ? "Block" : "Activate";
  const actionLower = action.toLowerCase();

  Swal.fire({
    title: `${action} Officer?`,
    text: `Do you want to ${actionLower} this police officer?`,
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Yes",
    cancelButtonText: "No"
  }).then((result) => {
    if (!result.isConfirmed) return;

    fetch("../admin/api/togglePoliceStatus.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: "id=" + userId
    })
    .then(res => res.text())
    .then(newStatus => {
      // Update data attribute
      blockIcon.dataset.status = newStatus;
      // Update icon class
      blockIcon.className = `bi ${getToggleIconClass(newStatus)} btn-toggle-police`;
      // Update badge in same row
      const badge = blockIcon.closest("tr").querySelector(".badge");
      if (badge) {
        badge.textContent = getStatusLabel(newStatus);
        badge.className = `badge bg-${getStatusClass(newStatus)}`;
      }
      Swal.fire("Success!", `Officer has been ${actionLower}ed`, "success");
      loadPolice(); // Refresh table
    })
    .catch(err => {
      console.error(err);
      Swal.fire("Error", "Failed to update status", "error");
    });
  });
});

/**
 * Delete police officer from both users and police tables
 */
function deletePolice(userId, fullname) {
  if (!userId) {
    Swal.fire('Error', 'Invalid user ID', 'error');
    return;
  }

  Swal.fire({
    title: "Delete Officer?",
    html: `<p>This will permanently delete <strong>${escapeHtml(fullname || 'this officer')}</strong>.</p>
           <p class="text-muted small">The record will be removed from both <code>users</code> and <code>police</code> tables.</p>`,
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Yes, Delete",
    cancelButtonText: "Cancel",
    confirmButtonColor: "#dc3545"
  }).then((result) => {
    if (!result.isConfirmed) return;

    fetch("../admin/api/deletePolice.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: `user_id=${userId}`
    })
    .then(res => res.json())
    .then(data => {
      if (data.status === "success") {
        Swal.fire('Deleted!', 'Police officer has been removed', 'success');
        loadPolice();
      } else {
        Swal.fire('Error', data.message || 'Failed to delete police officer', 'error');
      }
    })
    .catch(err => {
      console.error("Delete Police Error:", err);
      Swal.fire('Error', 'An error occurred while deleting', 'error');
    });
  });
}

/**
 * Add police form submission handler
 */
document.addEventListener("DOMContentLoaded", function () {
  const addPoliceForm = document.getElementById("addPoliceForm");
  if (!addPoliceForm) return;

  addPoliceForm.addEventListener("submit", function (e) {
    e.preventDefault();

    const formData = new FormData(this);

    fetch("../admin/api/addPolice.php", {
      method: "POST",
      body: formData
    })
    .then(res => res.json())
    .then(data => {
      if (data.status === "success") {
        Swal.fire('Success!', 'Police officer added successfully', 'success');
        addPoliceModal.hide();
        this.reset();
        loadPolice();
      } else {
        Swal.fire('Error', data.message || 'Failed to add police officer', 'error');
      }
    })
    .catch(err => {
      console.error("Add Police Error:", err);
      Swal.fire('Error', 'An error occurred while adding officer', 'error');
    });
  });
});

// Load police data on page load
document.addEventListener("DOMContentLoaded", loadPolice);

// ==========================================
// MISSING REPORTS MANAGEMENT
// ==========================================

// Initialize report modals
let viewReportModal, assignPoliceModal, viewFoundModal;

document.addEventListener("DOMContentLoaded", function() {
  const viewReportEl = document.getElementById('viewReportModal');
  const assignPoliceEl = document.getElementById('assignPoliceModal');
  const viewFoundEl = document.getElementById('viewFoundModal');
  
  if (viewReportEl && typeof bootstrap !== 'undefined') {
    viewReportModal = bootstrap.Modal.getOrCreateInstance(viewReportEl);
  }
  if (assignPoliceEl && typeof bootstrap !== 'undefined') {
    assignPoliceModal = bootstrap.Modal.getOrCreateInstance(assignPoliceEl);
  }
  if (viewFoundEl && typeof bootstrap !== 'undefined') {
    viewFoundModal = bootstrap.Modal.getOrCreateInstance(viewFoundEl);
  }
});

/**
 * Format date string to readable format
 */
function formatDate(dateStr) {
  if (!dateStr) return '-';
  const date = new Date(dateStr);
  return date.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

/**
 * Load and display all missing reports in the table
 */
function loadReports() {
  fetch("../admin/api/getReports.php")
    .then(res => res.json())
    .then(response => {
      if (response.status !== 'success') {
        document.getElementById("reportsTable").innerHTML = 
          '<tr><td colspan="9" class="text-center text-danger">Error loading reports</td></tr>';
        return;
      }
      
      const reports = response.data;
      const tbody = document.getElementById("reportsTable");
      
      if (!reports || reports.length === 0) {
        tbody.innerHTML = 
          '<tr><td colspan="9" class="text-center text-muted">No reports found</td></tr>';
        return;
      }
      
      let html = "";
      reports.forEach(r => {
        const statusBadge = getReportStatusBadge(r.status);
        const assignedTo = r.police_name 
          ? `${escapeHtml(r.police_name)}<br><small class="text-muted">${escapeHtml(r.police_station || '')}</small>` 
          : '-';
        const ageGender = r.age ? `${r.age} yrs / ${capitalizeFirst(r.gender || '')}` : '-';
        const reporter = r.reporter_name 
          ? `${escapeHtml(r.reporter_name)}<br><small class="text-muted">${escapeHtml(r.reporter_email || '')}</small>`
          : '-';
        
        html += `
          <tr data-report-id="${r.report_id}">
            <td><strong>#R${r.report_id}</strong></td>
            <td>${escapeHtml(r.missing_name || 'Unknown')}</td>
            <td>${ageGender}</td>
            <td>${escapeHtml(r.last_seen_location || '-')}</td>
            <td>${reporter}</td>
            <td>${formatDate(r.created_at)}</td>
            <td>${statusBadge}</td>
            <td>${assignedTo}</td>
            <td class="table-actions">
              <i class="bi bi-eye text-primary"
                 title="View Report"
                 style="cursor:pointer;margin-right:8px;"
                 data-action="view-report"
                 data-id="${r.report_id}"></i>
              <i class="bi bi-person-lines-fill text-success"
                 title="Assign Police"
                 style="cursor:pointer;margin-right:8px;"
                 data-action="assign-police"
                 data-id="${r.report_id}"></i>
              <i class="bi bi-check2-circle text-success"
                 title="Verify Report"
                 style="cursor:pointer;margin-right:8px;"
                 data-action="verify-report"
                 data-id="${r.report_id}"></i>
              <i class="bi bi-trash text-danger"
                 title="Delete Report"
                 style="cursor:pointer;"
                 data-action="delete-report"
                 data-id="${r.report_id}"
                 data-name="${escapeHtml(r.missing_name || 'Unknown')}"></i>
            </td>
          </tr>
        `;
      });
      
      tbody.innerHTML = html;
    })
    .catch(err => {
      console.error("Load Reports Error:", err);
      document.getElementById("reportsTable").innerHTML = 
        '<tr><td colspan="9" class="text-center text-danger">Error loading reports</td></tr>';
    });
}

/**
 * Get status badge HTML for reports
 */
function getReportStatusBadge(status) {
  const statusConfig = {
    'pending': { class: 'bg-warning text-dark', label: 'Pending' },
    'verified': { class: 'bg-info text-white', label: 'Verified' },
    'assigned': { class: 'bg-primary', label: 'Assigned' },
    'closed': { class: 'bg-success', label: 'Closed' },
    'found': { class: 'bg-success', label: 'Found' }
  };
  
  const config = statusConfig[status] || { class: 'bg-secondary', label: capitalizeFirst(status || 'Unknown') };
  return `<span class="badge ${config.class}">${config.label}</span>`;
}

/**
 * View Report Details
 * Opens modal with full report info and PDF preview
 */
function viewReport(reportId) {
  if (!reportId) {
    Swal.fire('Error', 'Invalid report ID', 'error');
    return;
  }

  fetch(`../admin/api/getSingleReport.php?report_id=${reportId}`)
    .then(res => res.json())
    .then(response => {
      if (response.status !== 'success') {
        Swal.fire('Error', response.message || 'Failed to load report', 'error');
        return;
      }

      const r = response.data;
      
      // Populate view report modal
      document.getElementById('viewReportId').textContent = `#R${r.report_id}`;
      document.getElementById('viewMissingName').textContent = r.missing_name || 'Unknown';
      document.getElementById('viewAge').textContent = r.age ? `${r.age} years` : 'Not specified';
      document.getElementById('viewGender').textContent = r.gender ? capitalizeFirst(r.gender) : 'Not specified';
      document.getElementById('viewDescription').textContent = r.description || 'No description';
      document.getElementById('viewLocation').textContent = r.last_seen_location || 'Not specified';
      document.getElementById('viewDateMissing').textContent = r.last_seen_datetime || 'Not specified';
      document.getElementById('viewCreatedAt').textContent = formatDate(r.created_at);
      document.getElementById('viewStatus').innerHTML = getReportStatusBadge(r.status);
      
      // Reporter info
      document.getElementById('viewReporterName').textContent = r.reporter?.name || 'Unknown';
      document.getElementById('viewReporterEmail').textContent = r.reporter?.email || 'Unknown';
      
      // Assigned police info
      if (r.assigned_police) {
        document.getElementById('viewPoliceSection').style.display = 'block';
        document.getElementById('viewPoliceName').textContent = r.assigned_police.name;
        document.getElementById('viewPoliceBadge').textContent = r.assigned_police.badge;
        document.getElementById('viewPoliceStation').textContent = r.assigned_police.station;
        document.getElementById('viewAssignedAt').textContent = formatDate(r.assigned_police.assigned_at);
      } else {
        document.getElementById('viewPoliceSection').style.display = 'none';
      }
      
      // Handle PDF preview button
      const pdfBtn = document.getElementById('viewReportPdfBtn');
      if (pdfBtn) {
        if (r.document_pdf) {
          pdfBtn.style.display = 'inline-block';
          pdfBtn.onclick = () => openReportPdf(reportId);
        } else {
          pdfBtn.style.display = 'none';
        }
      }
      
      // Handle found details section
      const foundSection = document.getElementById('viewFoundSection');
      if (foundSection) {
        if (r.detection) {
          foundSection.style.display = 'block';
          document.getElementById('viewFoundDate').textContent = formatDate(r.detection.found_time) || 'Not specified';
          document.getElementById('viewFoundLocation').textContent = r.detection.found_location || 'Not specified';
        } else {
          foundSection.style.display = 'none';
        }
      }
      
      // Store report ID for PDF preview
      document.getElementById('viewReportModal').dataset.reportId = reportId;
      
      // Show modal
      viewReportModal.show();
    })
    .catch(err => {
      console.error("View Report Error:", err);
      Swal.fire('Error', 'Failed to load report details', 'error');
    });
}

/**
 * Open PDF Preview in iframe modal
 */
function openReportPdf(reportId) {
  if (!reportId) return;
  
  fetch(`../admin/api/getReportPdf.php?report_id=${reportId}`)
    .then(res => res.json())
    .then(response => {
      if (response.status !== 'success') {
        Swal.fire('Error', response.message || 'No PDF available', 'error');
        return;
      }
      
      const pdfUrl = `../../${response.pdf_path}`;
      openPdfPreview(pdfUrl, `Report #R${reportId} - PDF`);
    })
    .catch(err => {
      console.error("Get PDF Error:", err);
      Swal.fire('Error', 'Failed to load PDF', 'error');
    });
}

/**
 * Verify Report
 * Changes status from pending to verified
 */
function verifyReport(reportId) {
  if (!reportId) {
    Swal.fire('Error', 'Invalid report ID', 'error');
    return;
  }

  Swal.fire({
    title: 'Verify Report?',
    html: `<p>This will change the report status from <strong>Pending</strong> to <strong>Verified</strong>.</p>
           <p class="text-muted small">Verified reports can be assigned to police officers.</p>`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Yes, Verify',
    cancelButtonText: 'No, Cancel',
    confirmButtonColor: '#198754'
  }).then((result) => {
    if (!result.isConfirmed) return;

    fetch("../admin/api/verifyReport.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: `report_id=${reportId}`
    })
    .then(res => res.json())
    .then(data => {
      if (data.status === 'success') {
        Swal.fire('Verified!', 'Report has been verified successfully', 'success');
        loadReports(); // Refresh table
      } else {
        Swal.fire('Error', data.message || 'Failed to verify report', 'error');
      }
    })
    .catch(err => {
      console.error("Verify Report Error:", err);
      Swal.fire('Error', 'Failed to verify report', 'error');
    });
  });
}

/**
 * Assign Police to Report
 * Opens modal with list of active police officers
 */
function assignPolice(reportId) {
  if (!reportId) {
    Swal.fire('Error', 'Invalid report ID', 'error');
    return;
  }

  // First check if report is verified
  fetch(`../admin/api/getSingleReport.php?report_id=${reportId}`)
    .then(res => res.json())
    .then(response => {
      if (response.status !== 'success') {
        Swal.fire('Error', 'Failed to load report', 'error');
        return;
      }
      
      const report = response.data;
      
      if (report.status !== 'verified') {
        Swal.fire({
          title: 'Cannot Assign Police',
          html: `<p>Only <strong>verified</strong> reports can be assigned to police.</p>
                 <p class="text-muted small">Current status: ${getReportStatusBadge(report.status)}</p>`,
          icon: 'warning'
        });
        return;
      }
      
      // Store report ID and show assign modal
      document.getElementById('assignPoliceModal').dataset.reportId = reportId;
      document.getElementById('assignReportId').textContent = `#R${reportId}`;
      document.getElementById('assignMissingName').textContent = report.missing_name || 'Unknown';
      
      // Load police list
      loadPoliceForAssignment();
      assignPoliceModal.show();
    })
    .catch(err => {
      console.error("Check Report Error:", err);
      Swal.fire('Error', 'Failed to verify report status', 'error');
    });
}

/**
 * Load active police officers for assignment dropdown
 */
function loadPoliceForAssignment() {
  fetch("../admin/api/getPolice.php")
    .then(res => res.json())
    .then(policeList => {
      const select = document.getElementById('assignPoliceSelect');
      select.innerHTML = '<option value="">-- Select Police Officer --</option>';
      
      // Filter only active police
      const activePolice = Array.isArray(policeList) 
        ? policeList.filter(p => p.status === 'active')
        : [];
      
      if (activePolice.length === 0) {
        select.innerHTML = '<option value="">No active police officers available</option>';
        return;
      }
      
      activePolice.forEach(p => {
        const option = document.createElement('option');
        option.value = p.police_id;
        option.textContent = `${p.fullname} - ${p.station_name}, ${p.district}`;
        select.appendChild(option);
      });
    })
    .catch(err => {
      console.error("Load Police Error:", err);
      document.getElementById('assignPoliceSelect').innerHTML = 
        '<option value="">Error loading police</option>';
    });
}

/**
 * Confirm and submit police assignment
 */
function confirmPoliceAssignment() {
  const modal = document.getElementById('assignPoliceModal');
  const reportId = modal.dataset.reportId;
  const policeId = document.getElementById('assignPoliceSelect').value;
  
  if (!policeId) {
    Swal.fire('Select Police', 'Please select a police officer', 'warning');
    return;
  }
  
  const policeName = document.getElementById('assignPoliceSelect').options[
    document.getElementById('assignPoliceSelect').selectedIndex
  ].text;
  
  Swal.fire({
    title: 'Assign Police?',
    html: `<p>Assign <strong>${escapeHtml(policeName)}</strong> to this report?</p>
           <p class="text-muted small">Status will change to "Assigned"</p>`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Yes, Assign',
    cancelButtonText: 'No, Cancel',
    confirmButtonColor: '#0d6efd'
  }).then((result) => {
    if (!result.isConfirmed) return;
    
    fetch("../admin/api/assignPolice.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: `report_id=${reportId}&police_id=${policeId}`
    })
    .then(res => res.json())
    .then(data => {
      if (data.status === 'success') {
        Swal.fire('Assigned!', 'Police has been assigned to the report', 'success');
        assignPoliceModal.hide();
        loadReports(); // Refresh table
      } else {
        Swal.fire('Error', data.message || 'Failed to assign police', 'error');
      }
    })
    .catch(err => {
      console.error("Assign Police Error:", err);
      Swal.fire('Error', 'Failed to assign police', 'error');
    });
  });
}

/**
 * Delete Report
 * Removes report from database with confirmation
 */
function deleteReport(reportId, personName) {
  if (!reportId) {
    Swal.fire('Error', 'Invalid report ID', 'error');
    return;
  }

  Swal.fire({
    title: 'Delete Report?',
    html: `<p>This will permanently delete the report for <strong>${escapeHtml(personName)}</strong>.</p>
           <p class="text-danger small">This action cannot be undone!</p>`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Yes, Delete',
    cancelButtonText: 'No, Cancel',
    confirmButtonColor: '#dc3545'
  }).then((result) => {
    if (!result.isConfirmed) return;

    fetch("../admin/api/deleteReport.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: `report_id=${reportId}`
    })
    .then(res => res.json())
    .then(data => {
      if (data.status === 'success') {
        Swal.fire('Deleted!', 'Report has been deleted', 'success');
        // Remove row from table instantly
        const row = document.querySelector(`tr[data-report-id="${reportId}"]`);
        if (row) row.remove();
        
        // Refresh if no more rows
        const tbody = document.getElementById("reportsTable");
        if (!tbody.querySelector('tr[data-report-id]')) {
          loadReports();
        }
      } else {
        Swal.fire('Error', data.message || 'Failed to delete report', 'error');
      }
    })
    .catch(err => {
      console.error("Delete Report Error:", err);
      Swal.fire('Error', 'Failed to delete report', 'error');
    });
  });
}

/**
 * Search/Filter Reports
 */
function filterReports(searchTerm) {
  const rows = document.querySelectorAll('#reportsTable tr[data-report-id]');
  const term = searchTerm.toLowerCase();
  
  rows.forEach(row => {
    const text = row.textContent.toLowerCase();
    row.style.display = text.includes(term) ? '' : 'none';
  });
}

// ==========================================
// FOUND PERSONS MANAGEMENT
// ==========================================

/**
 * Get match status badge HTML for found persons
 */
function getFoundMatchStatusBadge(status) {
  const statusConfig = {
    'unmatched': { class: 'bg-info', label: 'Unmatched' },
    'matched': { class: 'bg-warning text-dark', label: 'Matched' },
    'confirmed': { class: 'bg-success', label: 'Confirmed' }
  };
  const config = statusConfig[status] || { class: 'bg-secondary', label: capitalizeFirst(status || 'Unknown') };
  return `<span class="badge ${config.class}">${config.label}</span>`;
}

/**
 * Load and display all found persons in the table with pagination
 */
function loadFoundPersons(page = 1, searchTerm = '') {
  const tbody = document.getElementById("foundTable");
  if (!tbody) return;

  const url = `../admin/api/getFoundPersons.php?page=${page}&limit=10&search=${encodeURIComponent(searchTerm)}`;

  fetch(url)
    .then(res => res.json())
    .then(response => {
      if (response.status !== 'success') {
        tbody.innerHTML = `<tr><td colspan="7" class="text-center text-danger">${response.message || 'Error loading found persons'}</td></tr>`;
        return;
      }

      const persons = response.data;
      if (!persons || persons.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No found persons recorded yet</td></tr>';
      } else {
        let html = "";
        persons.forEach(p => {
          const statusBadge = getFoundMatchStatusBadge(p.match_status);
          const photoUrl = p.photo_path ? `../${p.photo_path}` : `https://ui-avatars.com/api/?name=${encodeURIComponent(p.found_name || 'Found')}&background=28a745&color=fff&size=40`;

          html += `
            <tr data-found-id="${p.found_id}">
              <td><strong>#F${p.found_id}</strong></td>
              <td>
                <div class="d-flex align-items-center gap-2">
                  <img src="${photoUrl}" class="rounded" style="width:32px;height:32px;object-fit:cover;" alt="">
                  <span>${escapeHtml(p.found_name || 'Unknown')}</span>
                </div>
              </td>
              <td>${escapeHtml(p.found_location || '-')}</td>
              <td>${formatDate(p.found_datetime || p.created_at)}</td>
              <td>${statusBadge}</td>
              <td>${escapeHtml(p.added_by_name || '-')}</td>
              <td class="table-actions">
                <i class="bi bi-eye text-primary"
                   title="View Details"
                   style="cursor:pointer;margin-right:8px;"
                   onclick="viewFoundPerson(${p.found_id})"></i>
                <i class="bi bi-file-earmark-pdf text-danger"
                   title="View PDF"
                   style="cursor:pointer;margin-right:8px;"
                   onclick="openFoundPdf(${p.found_id})"></i>
                <i class="bi bi-link-45deg text-success"
                   title="Link to Report"
                   style="cursor:pointer;"
                   onclick="linkFound(${p.found_id})"></i>
              </td>
            </tr>
          `;
        });

        tbody.innerHTML = html;
      }

      // Render pagination
      renderFoundPagination(response.pagination, searchTerm);
    })
    .catch(err => {
      console.error("Load Found Persons Error:", err);
      tbody.innerHTML = '<tr><td colspan="7" class="text-center text-danger">Failed to connect. Check console.</td></tr>';
    });
}

/**
 * Render pagination for found persons
 */
function renderFoundPagination(pagination, searchTerm = '') {
  const container = document.getElementById('foundPagination');
  if (!container) return;
  
  container.innerHTML = '';

  if (!pagination || pagination.total_pages <= 1) return;

  const currentPage = pagination.current_page;

  // Previous button
  const prevLi = document.createElement('li');
  prevLi.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
  prevLi.innerHTML = `<a class="page-link" href="#" onclick="loadFoundPersons(${currentPage - 1}, '${searchTerm}');return false;">Previous</a>`;
  container.appendChild(prevLi);

  // Page numbers
  const startPage = Math.max(1, currentPage - 2);
  const endPage = Math.min(pagination.total_pages, currentPage + 2);

  for (let i = startPage; i <= endPage; i++) {
    const li = document.createElement('li');
    li.className = `page-item ${i === currentPage ? 'active' : ''}`;
    li.innerHTML = `<a class="page-link" href="#" onclick="loadFoundPersons(${i}, '${searchTerm}');return false;">${i}</a>`;
    container.appendChild(li);
  }

  // Next button
  const nextLi = document.createElement('li');
  nextLi.className = `page-item ${currentPage === pagination.total_pages ? 'disabled' : ''}`;
  nextLi.innerHTML = `<a class="page-link" href="#" onclick="loadFoundPersons(${currentPage + 1}, '${searchTerm}');return false;">Next</a>`;
  container.appendChild(nextLi);

  // Info
  const infoLi = document.createElement('li');
  infoLi.className = 'page-item disabled';
  infoLi.innerHTML = `<span class="page-link">${pagination.total_records} total records</span>`;
  container.appendChild(infoLi);
}

/**
 * Current found person ID for PDF viewing
 */
let currentFoundId = null;

/**
 * View Found Person Details
 * Opens modal with full found person info
 */
function viewFoundPerson(foundId) {
  if (!foundId) {
    Swal.fire('Error', 'Invalid found ID', 'error');
    return;
  }

  currentFoundId = foundId;

  fetch(`../admin/api/getFoundPerson.php?found_id=${foundId}`)
    .then(res => res.json())
    .then(response => {
      if (response.status !== 'success') {
        Swal.fire('Error', response.message || 'Failed to load found person', 'error');
        return;
      }

      const p = response.data;

      document.getElementById('viewFoundId').textContent = `#F${p.found_id}`;
      document.getElementById('viewFoundMatchStatus').innerHTML = getFoundMatchStatusBadge(p.match_status);
      document.getElementById('viewFoundName').textContent = escapeHtml(p.found_name || 'Unknown');
      document.getElementById('viewFoundLocationDetail').textContent = escapeHtml(p.found_location || 'Not specified');
      document.getElementById('viewFoundDateDetail').textContent = p.found_datetime ? formatDate(p.found_datetime) : '-';
      document.getElementById('viewFoundDescription').textContent = escapeHtml(p.description || 'No description');
      document.getElementById('viewFoundContact').textContent = escapeHtml(p.contact_info || 'Not provided');
      document.getElementById('viewFoundAddedBy').textContent = escapeHtml(p.added_by_name || 'Unknown');
      document.getElementById('viewFoundCreatedAt').textContent = formatDate(p.created_at);

      const photoEl = document.getElementById('viewFoundPhoto');
      if (p.photo_path) {
        photoEl.src = `../${p.photo_path}`;
        photoEl.style.display = 'block';
      } else {
        photoEl.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(p.found_name || 'Found')}&background=28a745&color=fff&size=200`;
        photoEl.style.display = 'block';
      }

      viewFoundModal.show();
    })
    .catch(err => {
      console.error("View Found Person Error:", err);
      Swal.fire('Error', 'Failed to load found person details', 'error');
    });
}

/**
 * Open Found Person PDF in iframe modal
 */
function openFoundPdf(foundId) {
  if (!foundId && !currentFoundId) {
    Swal.fire('Error', 'Invalid found ID', 'error');
    return;
  }
  const id = foundId || currentFoundId;
  const pdfUrl = `../admin/api/generate_found_pdf.php?found_id=${id}`;
  openPdfPreview(pdfUrl, `Found Person #F${id} - PDF`);
}

/**
 * Search/Filter Found Persons
 */
function filterFoundPersons(searchTerm) {
  const rows = document.querySelectorAll('#foundTable tr[data-found-id]');
  const term = searchTerm.toLowerCase();

  rows.forEach(row => {
    const text = row.textContent.toLowerCase();
    row.style.display = text.includes(term) ? '' : 'none';
  });
}

// ==========================================
// AI MATCHES MANAGEMENT
// ==========================================

/**
 * Get match status badge HTML
 */
function getAIMatchStatusBadge(status) {
  const statusConfig = {
    'pending': { class: 'bg-warning text-dark', label: 'Pending' },
    'approved': { class: 'bg-success', label: 'Approved' },
    'confirmed': { class: 'bg-success', label: 'Confirmed' },
    'rejected': { class: 'bg-danger', label: 'Rejected' }
  };
  const config = statusConfig[status] || { class: 'bg-secondary', label: capitalizeFirst(status || 'Unknown') };
  return `<span class="badge ${config.class}">${config.label}</span>`;
}

/**
 * Load and display all AI matches in the table
 */
function loadAIMatches() {
  const tbody = document.getElementById("matchesTable");
  if (!tbody) return;

  fetch("../admin/api/getAIMatches.php")
    .then(res => res.json())
    .then(response => {
      if (response.status !== 'success') {
        tbody.innerHTML = `<tr><td colspan="7" class="text-center text-danger">${response.message || 'Error loading AI matches'}</td></tr>`;
        return;
      }

      const matches = response.data;
      if (!matches || matches.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No AI match suggestions yet</td></tr>';
        return;
      }

      let html = "";
      matches.forEach(m => {
        const statusBadge = getAIMatchStatusBadge(m.status);
        const matchPercent = parseFloat(m.match_percentage || 0).toFixed(2);
        const percentClass = matchPercent >= 80 ? 'text-success' : (matchPercent >= 60 ? 'text-warning' : 'text-danger');

        html += `
          <tr data-match-id="${m.match_id}">
            <td><strong>#M${m.match_id}</strong></td>
            <td>${escapeHtml(m.missing_name || 'Unknown')}</td>
            <td>#F${m.found_id} - ${escapeHtml(m.found_name || 'Unknown')}</td>
            <td><strong class="${percentClass}">${matchPercent}%</strong></td>
            <td><span class="badge bg-${m.suggested_by === 'AI' ? 'info' : 'primary'}">${m.suggested_by || 'AI'}</span></td>
            <td>${statusBadge}</td>
            <td class="table-actions">
              <i class="bi bi-check2-square text-success"
                 title="Approve Match"
                 style="cursor:pointer;margin-right:8px;"
                 onclick="approveMatch(${m.match_id})"></i>
              <i class="bi bi-x-square text-danger"
                 title="Reject Match"
                 style="cursor:pointer;margin-right:8px;"
                 onclick="rejectMatch(${m.match_id})"></i>
              <i class="bi bi-eye text-primary"
                 title="View Details"
                 style="cursor:pointer;"
                 onclick="viewMatchDetail(${m.match_id})"></i>
            </td>
          </tr>
        `;
      });

      tbody.innerHTML = html;
    })
    .catch(err => {
      console.error("Load AI Matches Error:", err);
      tbody.innerHTML = '<tr><td colspan="7" class="text-center text-danger">Failed to connect. Check console.</td></tr>';
    });
}

/**
 * Approve a match
 */
function approveMatch(matchId) {
  if (!matchId) return;

  Swal.fire({
    title: 'Approve Match?',
    html: '<p>This will confirm the match between the missing person and found person.</p><p class="text-success">The reporter will be notified.</p>',
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Yes, Approve',
    cancelButtonText: 'Cancel',
    confirmButtonColor: '#198754'
  }).then((result) => {
    if (!result.isConfirmed) return;

    updateMatchStatus(matchId, 'confirmed');
  });
}

/**
 * Reject a match
 */
function rejectMatch(matchId) {
  if (!matchId) return;

  Swal.fire({
    title: 'Reject Match?',
    html: '<p>This will reject the match suggestion.</p><p class="text-danger">The match will be removed from suggestions.</p>',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Yes, Reject',
    cancelButtonText: 'Cancel',
    confirmButtonColor: '#dc3545'
  }).then((result) => {
    if (!result.isConfirmed) return;

    updateMatchStatus(matchId, 'rejected');
  });
}

/**
 * Update match status via API
 */
function updateMatchStatus(matchId, status) {
  fetch("../admin/api/updateMatchStatus.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: `match_id=${matchId}&status=${status}`
  })
    .then(res => res.json())
    .then(data => {
      if (data.status === 'success') {
        Swal.fire('Success!', `Match ${status} successfully`, 'success');
        loadAIMatches();
      } else {
        Swal.fire('Error', data.message || 'Failed to update match', 'error');
      }
    })
    .catch(err => {
      console.error("Update Match Error:", err);
      Swal.fire('Error', 'An error occurred', 'error');
    });
}

/**
 * View match details
 */
function viewMatchDetail(matchId) {
  fetch(`../admin/api/getAIMatches.php?match_id=${matchId}`)
    .then(res => res.json())
    .then(data => {
      if (data.status !== 'success' || !data.data) {
        Swal.fire('Error', 'Failed to load match details', 'error');
        return;
      }

      const m = Array.isArray(data.data) ? data.data[0] : data.data;
      const matchPercent = parseFloat(m.match_percentage || 0).toFixed(2);
      const percentClass = matchPercent >= 80 ? 'text-success' : (matchPercent >= 60 ? 'text-warning' : 'text-danger');

      const html = `
        <div class="row">
          <div class="col-md-6 text-center">
            <h6 class="text-muted">Missing Person</h6>
            <img src="${m.missing_photo ? '../' + m.missing_photo : 'https://via.placeholder.com/150'}"
                 class="img-fluid rounded mb-2" style="max-width:150px;" />
            <p><strong>${escapeHtml(m.missing_name || 'Unknown')}</strong></p>
            <p>Report #R${m.report_id}</p>
          </div>
          <div class="col-md-6 text-center">
            <h6 class="text-muted">Found Person</h6>
            <img src="${m.found_photo ? '../' + m.found_photo : 'https://via.placeholder.com/150'}"
                 class="img-fluid rounded mb-2" style="max-width:150px;" />
            <p><strong>${escapeHtml(m.found_name || 'Unknown')}</strong></p>
            <p>Found #F${m.found_id}</p>
          </div>
        </div>
        <hr/>
        <div class="text-center">
          <h4 class="${percentClass}">${matchPercent}% Match</h4>
          <p class="text-muted">Suggested by: ${m.suggested_by || 'AI'}</p>
          <p>Status: ${getAIMatchStatusBadge(m.status)}</p>
        </div>
      `;

      Swal.fire({
        title: 'Match Details',
        html: html,
        confirmButtonText: 'Close',
        width: '500px'
      });
    })
    .catch(err => {
      console.error("View Match Error:", err);
      Swal.fire('Error', 'Failed to load match details', 'error');
    });
}

/**
 * Export AI matches to CSV
 */
function exportMatches() {
  fetch("../admin/api/getAIMatches.php")
    .then(res => res.json())
    .then(data => {
      if (data.status !== 'success' || !data.data || data.data.length === 0) {
        Swal.fire('Info', 'No matches to export', 'info');
        return;
      }

      let csv = 'Match ID,Missing Person,Found Person,Match %,Suggested By,Status,Date\n';
      data.data.forEach(m => {
        csv += `${m.match_id},"${escapeHtml(m.missing_name || 'Unknown')}","${escapeHtml(m.found_name || 'Unknown')}",${parseFloat(m.match_percentage || 0).toFixed(2)}%,${m.suggested_by || 'AI'},${m.status || 'pending'},"${m.created_at || ''}"\n`;
      });

      const blob = new Blob([csv], { type: 'text/csv' });
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = 'ai_matches_' + new Date().toISOString().split('T')[0] + '.csv';
      a.click();
      window.URL.revokeObjectURL(url);
    })
    .catch(err => {
      console.error("Export Matches Error:", err);
      Swal.fire('Error', 'Failed to export matches', 'error');
    });
}

/**
 * Search/Filter AI Matches
 */
function filterAIMatches(searchTerm) {
  const rows = document.querySelectorAll('#matchesTable tr[data-match-id]');
  const term = searchTerm.toLowerCase();

  rows.forEach(row => {
    const text = row.textContent.toLowerCase();
    row.style.display = text.includes(term) ? '' : 'none';
  });
}

// ==========================================
// UNIFIED EVENT DELEGATION FOR REPORT ACTIONS
// ==========================================

document.addEventListener('click', function(e) {
  const target = e.target.closest('[data-action]');
  if (!target) return;

  const action = target.dataset.action;
  const id = target.dataset.id;

  // Validate required attributes
  if (!action || !id) {
    console.warn('[Dashboard] Missing data-action or data-id on clicked element:', target);
    return;
  }

  // Debug logging
  console.log('[Dashboard] Action clicked:', { action, id });

  // Prevent errors: check if id is valid
  if (id === 'undefined' || id === 'null' || id === '') {
    console.error('[Dashboard] Invalid ID for action:', action, '- id value:', id);
    Swal.fire('Error', 'Invalid record ID. Please refresh the page and try again.', 'error');
    return;
  }

  // Route to correct function with safety checks
  switch (action) {
    case 'view-report':
      if (typeof viewReport === 'function') {
        viewReport(id);
      } else {
        console.error('[Dashboard] viewReport() function not found');
        Swal.fire('Error', 'View report function is not available.', 'error');
      }
      break;

    case 'assign-police':
      if (typeof assignPolice === 'function') {
        assignPolice(id);
      } else {
        console.error('[Dashboard] assignPolice() function not found');
        Swal.fire('Error', 'Assign police function is not available.', 'error');
      }
      break;

    case 'verify-report':
      if (typeof verifyReport === 'function') {
        verifyReport(id);
      } else {
        console.error('[Dashboard] verifyReport() function not found');
        Swal.fire('Error', 'Verify report function is not available.', 'error');
      }
      break;

    case 'delete-report':
      const personName = target.dataset.name || 'Unknown';
      if (typeof deleteReport === 'function') {
        deleteReport(id, personName);
      } else {
        console.error('[Dashboard] deleteReport() function not found');
        Swal.fire('Error', 'Delete report function is not available.', 'error');
      }
      break;

    default:
      console.warn('[Dashboard] Unhandled action:', action);
  }
});

// ==========================================
// REPORT SEARCH HANDLER
// ==========================================

document.addEventListener("DOMContentLoaded", function() {
  const reportSearch = document.getElementById('reportSearch');
  if (reportSearch) {
    reportSearch.addEventListener('input', function() {
      filterReports(this.value);
    });
  }

  const foundSearch = document.getElementById('foundSearch');
  if (foundSearch) {
    foundSearch.addEventListener('input', function() {
      filterFoundPersons(this.value);
    });
  }

  const matchSearch = document.getElementById('matchSearch');
  if (matchSearch) {
    matchSearch.addEventListener('input', function() {
      filterAIMatches(this.value);
    });
  }

  // Load all tables on page load
  loadReports();
  loadFoundPersons();
  loadAIMatches();
});
