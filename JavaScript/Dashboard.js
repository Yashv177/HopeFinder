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
    document.getElementById("statUsers").innerText = data.users;
    document.getElementById("statPolice").innerText = data.police;
    document.getElementById("statReports").innerText = data.reports;
    document.getElementById("statMatches").innerText = data.matches;
    document.getElementById("statPending").innerText = data.pending;
  });

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

/**
 * Load and display all missing reports in the table
 */
function loadReports() {
  fetch("../admin/api/getReports.php")
    .then(res => res.json())
    .then(response => {
      if (response.status !== 'success') {
        document.getElementById("reportsTable").innerHTML = 
          '<tr><td colspan="7" class="text-center text-danger">Error loading reports</td></tr>';
        return;
      }
      
      const reports = response.data;
      const tbody = document.getElementById("reportsTable");
      
      if (!reports || reports.length === 0) {
        tbody.innerHTML = 
          '<tr><td colspan="7" class="text-center text-muted">No reports found</td></tr>';
        return;
      }
      
      let html = "";
      reports.forEach(r => {
        const statusBadge = getReportStatusBadge(r.status);
        const assignedTo = r.assigned_police_name 
          ? `${escapeHtml(r.assigned_police_name)}<br><small class="text-muted">${escapeHtml(r.police_station || '')}</small>` 
          : '-';
        
        html += `
          <tr data-report-id="${r.report_id}">
            <td><strong>#R${r.report_id}</strong></td>
            <td>${escapeHtml(r.missing_person_name || 'Unknown')}</td>
            <td>${escapeHtml(r.reporter_name || 'Unknown')}</td>
            <td>${r.date_reported || '-'}</td>
            <td>${statusBadge}</td>
            <td>${assignedTo}</td>
            <td class="table-actions">
              <i class="bi bi-eye text-primary" 
                 title="View Report" 
                 onclick="viewReport(${r.report_id})"></i>
              <i class="bi bi-person-lines-fill text-success" 
                 title="Assign Police" 
                 onclick="assignPolice(${r.report_id})"></i>
              <i class="bi bi-check2-circle text-success" 
                 title="Verify Report" 
                 onclick="verifyReport(${r.report_id})"></i>
              <i class="bi bi-trash text-danger" 
                 title="Delete Report" 
                 onclick="deleteReport(${r.report_id}, '${escapeHtml(r.missing_person_name || 'Unknown')}')"></i>
            </td>
          </tr>
        `;
      });
      
      tbody.innerHTML = html;
    })
    .catch(err => {
      console.error("Load Reports Error:", err);
      document.getElementById("reportsTable").innerHTML = 
        '<tr><td colspan="7" class="text-center text-danger">Error loading reports</td></tr>';
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
    'closed': { class: 'bg-success', label: 'Closed' }
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
      document.getElementById('viewMissingPerson').textContent = r.missing_person_name || 'Unknown';
      document.getElementById('viewDescription').textContent = r.description || 'No description';
      document.getElementById('viewLocation').textContent = r.location || 'Not specified';
      document.getElementById('viewDateMissing').textContent = r.date_missing || 'Not specified';
      document.getElementById('viewDateReported').textContent = r.date_reported || 'Not specified';
      document.getElementById('viewStatus').innerHTML = getReportStatusBadge(r.status);
      
      // Reporter info
      document.getElementById('viewReporterName').textContent = r.reporter?.name || 'Unknown';
      document.getElementById('viewReporterEmail').textContent = r.reporter?.email || 'Unknown';
      document.getElementById('viewReporterPhone').textContent = r.reporter?.phone || 'Not provided';
      
      // Assigned police info
      if (r.assigned_police) {
        document.getElementById('viewPoliceSection').style.display = 'block';
        document.getElementById('viewPoliceName').textContent = r.assigned_police.name;
        document.getElementById('viewPoliceBadge').textContent = r.assigned_police.badge;
        document.getElementById('viewPoliceStation').textContent = r.assigned_police.station;
      } else {
        document.getElementById('viewPoliceSection').style.display = 'none';
      }
      
      // Store PDF path for download button
      const downloadLink = document.getElementById('viewReportDownloadLink');
      if (r.pdf_path) {
        downloadLink.href = `../../${r.pdf_path}`;
        downloadLink.style.display = 'inline-block';
      } else {
        downloadLink.style.display = 'none';
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
      document.getElementById('assignMissingPerson').textContent = report.missing_person_name;
      
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

// Report search input handler
document.addEventListener("DOMContentLoaded", function() {
  const reportSearch = document.getElementById('reportSearch');
  if (reportSearch) {
    reportSearch.addEventListener('input', function() {
      filterReports(this.value);
    });
  }
  
  // Load reports on page load
  loadReports();
});
