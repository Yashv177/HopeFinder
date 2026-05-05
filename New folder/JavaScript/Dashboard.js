javascript
// ==========================================
// CONFIGURATION & CONSTANTS
// ==========================================

const API_BASE = "../admin/api/";

// Modal instances cache
const modals = {
  viewPolice: null,
  editPolice: null,
  addUser: null,
  addPolice: null,
  viewReport: null,
  assignPolice: null,
  pdfPreview: null
};

/**
 * Get or create Bootstrap modal instance
 */
function getModal(elementId) {
  if (!modals[elementId]) {
    const element = document.getElementById(elementId);
    if (element) {
      modals[elementId] = new bootstrap.Modal(element);
    }
  }
  return modals[elementId];
}

// ==========================================
// SIDEBAR TOGGLE
// ==========================================

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

if (sidebarCollapse) {
  sidebarCollapse.addEventListener('click', toggleSidebar);
}

// Close overlay sidebar by clicking outside on small screens
document.addEventListener('click', (event) => {
  if (window.innerWidth <= 991 && body.classList.contains('sidebar-expanded')) {
    if (!sidebar.contains(event.target) && event.target !== sidebarCollapse) {
      body.classList.remove('sidebar-expanded');
    }
  }
});

// ==========================================
// DASHBOARD STATS
// ==========================================

fetch(API_BASE + "getDashboardStats.php")
  .then(res => {
    if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
    return res.json();
  })
  .then(data => {
    if (data) {
      document.getElementById("statUsers").innerText = data.users || 0;
      document.getElementById("statPolice").innerText = data.police || 0;
      document.getElementById("statReports").innerText = data.reports || 0;
      document.getElementById("statMatches").innerText = data.matches || 0;
      document.getElementById("statPending").innerText = data.pending || 0;
    }
  })
  .catch(err => console.error("Dashboard stats error:", err));

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
 * Open PDF in iframe modal
 */
function openPdfPreview(pdfUrl, title) {
  const modal = document.getElementById('pdfPreviewModal');
  const iframe = document.getElementById('pdfPreviewIframe');
  const modalTitle = document.getElementById('pdfPreviewModalTitle');
  
  if (!modal || !iframe) {
    Swal.fire('Error', 'PDF preview modal not found', 'error');
    return;
  }
  
  // Validate URL
  if (!pdfUrl || typeof pdfUrl !== 'string') {
    Swal.fire('Error', 'Invalid PDF URL', 'error');
    return;
  }
  
  iframe.src = escapeHtml(pdfUrl);
  if (modalTitle) {
    modalTitle.textContent = title || 'PDF Preview';
  }
  
  const bsModal = getModal('pdfPreview') || new bootstrap.Modal(modal);
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
// UNIFIED EVENT LISTENER
// ==========================================

document.addEventListener('click', function(e) {
  // Delete User
  if (e.target.closest('.btn-delete-user')) {
    handleDeleteUser(e);
  }
  
  // Toggle User Status
  if (e.target.closest('.btn-toggle-user')) {
    handleToggleUserStatus(e);
  }
  
  // Toggle Police Status
  if (e.target.closest('.btn-toggle-police')) {
    handleTogglePoliceStatus(e);
  }
});

// ==========================================
// USERS MANAGEMENT
// ==========================================

/**
 * Load Users
 */
function loadUsers() {
fetch(API_BASE + "getUsers.php")
    .then(res => res.json())
    .then(users => {
      const userList = Array.isArray(users) ? users : (users.data || []);
      const tbody = document.getElementById("usersTable");
      
      if (!userList.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No users found</td></tr>';
        return;
      }
      
      let html = "";
      userList.forEach(u => {
        const displayName = u.fullname || u.email.split('@')[0] || 'User';
        const status = u.status || 'blocked';
        
        html += `
          <tr>
            <td>#U${u.user_id || '-'}</td>
            <td>${escapeHtml(displayName)}</td>
            <td>${escapeHtml(u.email || '-')}</td>
            <td>${u.created_at || '-'}</td>
            <td>
              <span class="badge bg-${getStatusClass(status)}">
                ${getStatusLabel(status)}
              </span>
            </td>
            <td class="table-actions">
              <i class="bi ${getToggleIconClass(status)} btn-toggle-user"
                 data-id="${u.user_id}" data-status="${status}" 
                 title="${getToggleTitle(status)}" style="cursor:pointer;margin-right:8px;">
              </i>
              <i class="bi bi-trash text-danger btn-delete-user"
                 data-id="${u.user_id}" title="Delete User" style="cursor:pointer;">
              </i>
            </td>
          </tr>
        `;
      });
      tbody.innerHTML = html;
    })
    .catch(err => {
      console.error("Load Users Error:", err);
      document.getElementById("usersTable").innerHTML = 
        '<tr><td colspan="6" class="text-center text-danger">Error loading users</td></tr>';
    });
}

/**
 * Handle Delete User
 */
function handleDeleteUser(event) {
  const deleteBtn = event.target.closest('.btn-delete-user');
  if (!deleteBtn) return;

  const userId = deleteBtn.dataset.id;
  if (!userId) return;

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

    Swal.showLoading();

    fetch(API_BASE + "deleteUser.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: "id=" + encodeURIComponent(userId)
    })
    .then(res => {
      if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
      return res.json();
    })
    .then(data => {
      Swal.hideLoading();
      
      if (data.status === "success") {
        Swal.fire("Deleted!", "User has been deleted.", "success");
        loadUsers();
      } else {
        Swal.fire("Error", data.message || "Failed to delete user", "error");
      }
    })
    .catch(err => {
      console.error("Delete User Error:", err);
      Swal.hideLoading();
      Swal.fire("Error", "Failed to delete user", "error");
    });
  });
}

/**
 * Handle Toggle User Status
 */
function handleToggleUserStatus(event) {
  const blockIcon = event.target.closest('.btn-toggle-user');
  if (!blockIcon) return;

  const userId = blockIcon.dataset.id;
  const currentStatus = blockIcon.dataset.status;
  
  if (!userId) return;

  const action = currentStatus === "active" ? "Block" : "Activate";

  Swal.fire({
    title: `${action} User?`,
    text: `Do you want to ${action.toLowerCase()} this user?`,
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Yes",
    cancelButtonText: "No"
  }).then((result) => {
    if (!result.isConfirmed) return;

    Swal.showLoading();

    fetch(API_BASE + "toggleUserStatus.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: "id=" + encodeURIComponent(userId)
    })
    .then(res => {
      if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
      return res.json();
    })
    .then(data => {
      Swal.hideLoading();
      
      if (!data.status || data.status === "error") {
        Swal.fire("Error", data.message || "Failed to update status", "error");
        return;
      }

      const newStatus = data.status || data;
      
      // Update data attribute
      blockIcon.dataset.status = newStatus;
      // Update icon class
      blockIcon.className = `bi ${getToggleIconClass(newStatus)} btn-toggle-user`;
      blockIcon.setAttribute('title', getToggleTitle(newStatus));
      
      // Update badge in same row
      const badge = blockIcon.closest("tr").querySelector(".badge");
      if (badge) {
        badge.textContent = getStatusLabel(newStatus);
        badge.className = `badge bg-${getStatusClass(newStatus)}`;
      }
      
      Swal.fire("Success!", `User has been ${action.toLowerCase()}ed`, "success");
    })
    .catch(err => {
      console.error("Toggle User Status Error:", err);
      Swal.hideLoading();
      Swal.fire("Error", "Failed to update status", "error");
    });
  });
}

/**
 * Handle Add User Form
 */
document.addEventListener("DOMContentLoaded", function () {
  const addUserForm = document.getElementById("addUserForm");
  if (!addUserForm) return;

  addUserForm.addEventListener("submit", function (e) {
    e.preventDefault();
    
    Swal.showLoading();
    const formData = new FormData(this);
    
    fetch(API_BASE + "addUser.php", {
      method: "POST",
      body: formData
    })
    .then(res => {
      if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
      return res.json();
    })
    .then(data => {
      Swal.hideLoading();
      
      if (data.status === "success") {
        Swal.fire({
          icon: "success",
          title: "Success",
          text: data.message || "User added successfully",
          timer: 1500,
          showConfirmButton: false
        });
        
        const modalElement = document.getElementById('addUserModal');
        if (modalElement) {
          const modal = getModal('addUser');
          if (modal) modal.hide();
        }
        this.reset();
        loadUsers();
      } else {
        Swal.fire("Error", data.message || "Failed to add user", "error");
      }
    })
    .catch(err => {
      console.error("Add User Error:", err);
      Swal.hideLoading();
      Swal.fire("Error", "Failed to connect to server", "error");
    });
  });
});

// Load users on page load
if (document.getElementById("usersTable")) {
  loadUsers();
}

// ==========================================
// POLICE MANAGEMENT
// ==========================================

/**
 * Load Police
 */
function loadPolice() {
fetch(API_BASE + "getPolice.php")
    .then(res => res.json())
    .then(list => {
      const policeList = Array.isArray(list) ? list : (list.data || []);
      const tbody = document.getElementById("policeTable");
      
      if (!policeList.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No police officers found</td></tr>';
        return;
      }

      let html = "";
      policeList.forEach(p => {
        const status = p.status || 'blocked';
        const statusBadgeClass = status === 'active' ? 'bg-success' : 'bg-danger';
        const statusLabel = getStatusLabel(status);
        const toggleIcon = getToggleIconClass(status);
        const toggleTitle = getToggleTitle(status);

        html += `
          <tr>
            <td><strong>#P${p.police_id || '-'}</strong></td>
            <td>${escapeHtml(p.fullname || 'Unknown')}</td>
            <td>${escapeHtml(p.email || '-')}</td>
            <td>${escapeHtml((p.station_name || 'Unknown') + ', ' + (p.district || 'N/A'))}</td>
            <td>
              <span class="badge ${statusBadgeClass}">
                ${statusLabel}
              </span>
            </td>
            <td class="table-actions">
              <i class="bi bi-eye text-primary" 
                 title="View Details"
                 style="cursor:pointer;margin-right:8px;"
                 data-action="view-police"
                 data-id="${p.police_id}"></i>

              <i class="bi bi-pencil-square text-warning" 
                 title="Edit Officer"
                 style="cursor:pointer;margin-right:8px;"
                 data-action="edit-police"
                 data-id="${p.police_id}"></i>

              <i class="bi ${toggleIcon} btn-toggle-police"
                 title="${toggleTitle}"
                 style="cursor:pointer;margin-right:8px;"
                 data-id="${p.user_id}" data-status="${status}"></i>

              <i class="bi bi-trash text-danger" 
                 title="Delete Officer"
                 style="cursor:pointer;"
                 data-action="delete-police"
                 data-id="${p.user_id}"
                 data-name="${escapeHtml(p.fullname || 'Unknown')}"></i>
            </td>
          </tr>
        `;
      });

      tbody.innerHTML = html;
    })
    .catch(err => {
      console.error("Load Police Error:", err);
      document.getElementById("policeTable").innerHTML =
        '<tr><td colspan="6" class="text-center text-danger">Error loading police data</td></tr>';
    });
}

/**
 * View Police Details
 */
function viewPolice(policeId) {
  if (!policeId) {
    Swal.fire('Error', 'Invalid police ID', 'error');
    return;
  }

  fetch(API_BASE + `getSinglePolice.php?police_id=${encodeURIComponent(policeId)}`)
    .then(res => {
      if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
      return res.json();
    })
    .then(r => {
      if (r.status !== "success") {
        Swal.fire('Error', 'Could not load police details', 'error');
        return;
      }

      const p = r.data;
      const status = p.status || 'blocked';

      // Populate view modal fields
      document.getElementById("viewPoliceId").innerText = '#P' + (p.police_id || '-');
      document.getElementById("viewFullname").innerText = escapeHtml(p.fullname || 'Unknown');
      document.getElementById("viewEmail").innerText = escapeHtml(p.email || '-');
      document.getElementById("viewBadgeNumber").innerText = escapeHtml(p.badge_number || '-');
      document.getElementById("viewStation").innerText = escapeHtml(p.station_name || '-');
      document.getElementById("viewDistrict").innerText = escapeHtml(p.district || '-');
      document.getElementById("viewState").innerText = escapeHtml(p.state || '-');
      document.getElementById("viewStatus").innerHTML = 
        `<span class="badge bg-${getStatusClass(status)}">
          ${getStatusLabel(status)}
        </span>`;
      document.getElementById("viewCreatedAt").innerText = p.created_at || '-';

      // Show view modal
      const viewPoliceModal = getModal('viewPolice');
      if (viewPoliceModal) viewPoliceModal.show();
    })
    .catch(err => {
      console.error("View Police Error:", err);
      Swal.fire('Error', 'Failed to load police details', 'error');
    });
}

/**
 * Open Edit Police Modal
 */
function openEditPoliceModal(policeId) {
  if (!policeId) {
    Swal.fire('Error', 'Invalid police ID', 'error');
    return;
  }

  fetch(API_BASE + `getSinglePolice.php?police_id=${encodeURIComponent(policeId)}`)
    .then(res => {
      if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
      return res.json();
    })
    .then(r => {
      if (r.status !== "success") {
        Swal.fire('Error', 'Could not load police data for editing', 'error');
        return;
      }

      const p = r.data;

      // Populate edit form
      document.getElementById("editPoliceId").value = p.police_id || '';
      document.getElementById("editFullname").value = p.fullname || '';
      document.getElementById("editBadgeNumber").value = p.badge_number || '';
      document.getElementById("editStationName").value = p.station_name || '';
      document.getElementById("editDistrict").value = p.district || '';
      document.getElementById("editState").value = p.state || '';

      // Show edit modal
      const editPoliceModal = getModal('editPolice');
      if (editPoliceModal) editPoliceModal.show();
    })
    .catch(err => {
      console.error("Open Edit Police Error:", err);
      Swal.fire('Error', 'Failed to load police data', 'error');
    });
}

/**
 * Handle Edit Police Form
 */
document.addEventListener("DOMContentLoaded", function () {
  const editForm = document.getElementById("editPoliceForm");
  if (!editForm) return;

  editForm.addEventListener("submit", function (e) {
    e.preventDefault();

    Swal.showLoading();

    const police_id = document.getElementById("editPoliceId").value;
    const formData = {
      police_id: police_id,
      fullname: document.getElementById("editFullname").value,
      badge_number: document.getElementById("editBadgeNumber").value,
      station_name: document.getElementById("editStationName").value,
      district: document.getElementById("editDistrict").value,
      state: document.getElementById("editState").value
    };

    fetch(API_BASE + "updatePolice.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: new URLSearchParams(formData)
    })
    .then(res => {
      if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
      return res.json();
    })
    .then(data => {
      Swal.hideLoading();
      
      if (data.status === "success") {
        Swal.fire('Success!', 'Police officer updated successfully', 'success');
        
        const editPoliceModal = getModal('editPolice');
        if (editPoliceModal) editPoliceModal.hide();
        
        editForm.reset();
        loadPolice();
      } else {
        Swal.fire('Error', data.message || 'Failed to update police', 'error');
      }
    })
    .catch(err => {
      console.error("Edit Police Error:", err);
      Swal.hideLoading();
      Swal.fire('Error', 'An error occurred while updating', 'error');
    });
  });
});

/**
 * Handle Toggle Police Status
 */
function handleTogglePoliceStatus(event) {
  const blockIcon = event.target.closest('.btn-toggle-police');
  if (!blockIcon) return;

  const userId = blockIcon.dataset.id;
  const currentStatus = blockIcon.dataset.status;
  
  if (!userId) return;

  const action = currentStatus === "active" ? "Block" : "Activate";

  Swal.fire({
    title: `${action} Officer?`,
    text: `Do you want to ${action.toLowerCase()} this police officer?`,
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Yes",
    cancelButtonText: "No"
  }).then((result) => {
    if (!result.isConfirmed) return;

    Swal.showLoading();

    fetch(API_BASE + "togglePoliceStatus.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: "id=" + encodeURIComponent(userId)
    })
    .then(res => {
      if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
      return res.json();
    })
    .then(data => {
      Swal.hideLoading();
      
      if (!data.status || data.status === "error") {
        Swal.fire("Error", data.message || "Failed to update status", "error");
        return;
      }

      const newStatus = data.status || data;
      
      // Update data attribute
      blockIcon.dataset.status = newStatus;
      // Update icon class
      blockIcon.className = `bi ${getToggleIconClass(newStatus)} btn-toggle-police`;
      blockIcon.setAttribute('title', getToggleTitle(newStatus));
      
      // Update badge in same row
      const badge = blockIcon.closest("tr").querySelector(".badge");
      if (badge) {
        badge.textContent = getStatusLabel(newStatus);
        badge.className = `badge bg-${getStatusClass(newStatus)}`;
      }
      
      Swal.fire("Success!", `Officer has been ${action.toLowerCase()}ed`, "success");
    })
    .catch(err => {
      console.error("Toggle Police Status Error:", err);
      Swal.hideLoading();
      Swal.fire("Error", "Failed to update status", "error");
    });
  });
}

/**
 * Delete Police
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

    Swal.showLoading();

    fetch(API_BASE + "deletePolice.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: "user_id=" + encodeURIComponent(userId)
    })
    .then(res => {
      if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
      return res.json();
    })
    .then(data => {
      Swal.hideLoading();
      
      if (data.status === "success") {
        Swal.fire('Deleted!', 'Police officer has been removed', 'success');
        loadPolice();
      } else {
        Swal.fire('Error', data.message || 'Failed to delete police officer', 'error');
      }
    })
    .catch(err => {
      console.error("Delete Police Error:", err);
      Swal.hideLoading();
      Swal.fire('Error', 'An error occurred while deleting', 'error');
    });
  });
}

/**
 * Handle Add Police Form
 */
document.addEventListener("DOMContentLoaded", function () {
  const addPoliceForm = document.getElementById("addPoliceForm");
  if (!addPoliceForm) return;

  addPoliceForm.addEventListener("submit", function (e) {
    e.preventDefault();

    Swal.showLoading();
    const formData = new FormData(this);

    fetch(API_BASE + "addPolice.php", {
      method: "POST",
      body: formData
    })
    .then(res => {
      if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
      return res.json();
    })
    .then(data => {
      Swal.hideLoading();
      
      if (data.status === "success") {
        Swal.fire('Success!', 'Police officer added successfully', 'success');
        
        const addPoliceModal = getModal('addPolice');
        if (addPoliceModal) addPoliceModal.hide();
        
        this.reset();
        loadPolice();
      } else {
        Swal.fire('Error', data.message || 'Failed to add police officer', 'error');
      }
    })
    .catch(err => {
      console.error("Add Police Error:", err);
      Swal.hideLoading();
      Swal.fire('Error', 'An error occurred while adding officer', 'error');
    });
  });
});

// Unified police action handler
document.addEventListener('click', function(e) {
  const viewBtn = e.target.closest('[data-action="view-police"]');
  if (viewBtn) {
    viewPolice(viewBtn.dataset.id);
  }
  
  const editBtn = e.target.closest('[data-action="edit-police"]');
  if (editBtn) {
    openEditPoliceModal(editBtn.dataset.id);
  }
  
  const deleteBtn = e.target.closest('[data-action="delete-police"]');
  if (deleteBtn) {
    deletePolice(deleteBtn.dataset.id, deleteBtn.dataset.name);
  }
});

// Load police on page load
if (document.getElementById("policeTable")) {
  document.addEventListener("DOMContentLoaded", loadPolice);
}

// ==========================================
// MISSING REPORTS MANAGEMENT
// ==========================================

/**
 * Load Reports
 */
function loadReports() {
fetch(API_BASE + "getReports.php")
    .then(res => res.json())
    .then(response => {
      if (response.status !== 'success') {
        document.getElementById("reportsTable").innerHTML = 
          '<tr><td colspan="7" class="text-center text-danger">Error loading reports</td></tr>';
        return;
      }
      
      const reports = response.data;
      const tbody = document.getElementById("reportsTable");
      
      if (!reports || !reports.length) {
        tbody.innerHTML = 
          '<tr><td colspan="7" class="text-center text-muted">No reports found</td></tr>';
        return;
      }
      
      let html = "";
      reports.forEach(r => {
        const statusBadge = getReportStatusBadge(r.status);
        const assignedTo = r.assigned_police_name 
          ? `${escapeHtml(r.assigned_police_name || 'Unknown')}<br><small class="text-muted">${escapeHtml(r.police_station || 'Unassigned')}</small>` 
          : '<span class="text-muted">-</span>';
        
        html += `
          <tr data-report-id="${r.report_id}">
            <td><strong>#R${r.report_id || '-'}</strong></td>
            <td>${escapeHtml(r.missing_person_name || 'Unknown')}</td>
            <td>${escapeHtml(r.reporter_name || 'Unknown')}</td>
            <td>${r.date_reported || '-'}</td>
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
                 data-name="${escapeHtml(r.missing_person_name || 'Unknown')}"></i>
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
 * Get Report Status Badge
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
 */
function viewReport(reportId) {
  if (!reportId) {
    Swal.fire('Error', 'Invalid report ID', 'error');
    return;
  }

  fetch(API_BASE + `getSingleReport.php?report_id=${encodeURIComponent(reportId)}`)
    .then(res => {
      if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
      return res.json();
    })
    .then(response => {
      if (response.status !== 'success') {
        Swal.fire('Error', response.message || 'Failed to load report', 'error');
        return;
      }

      const r = response.data;
      
      // Populate view report modal
      document.getElementById('viewReportId').textContent = `#R${r.report_id || '-'}`;
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
      const policeSectionElement = document.getElementById('viewPoliceSection');
      if (policeSectionElement) {
        if (r.assigned_police) {
          policeSectionElement.style.display = 'block';
          const policeName = document.getElementById('viewPoliceName');
          const policeBadge = document.getElementById('viewPoliceBadge');
          const policeStation = document.getElementById('viewPoliceStation');
          
          if (policeName) policeName.textContent = r.assigned_police.name || 'Unknown';
          if (policeBadge) policeBadge.textContent = r.assigned_police.badge || 'N/A';
          if (policeStation) policeStation.textContent = r.assigned_police.station || 'Unassigned';
        } else {
          policeSectionElement.style.display = 'none';
        }
      }
      
      // PDF download link
      const downloadLink = document.getElementById('viewReportDownloadLink');
      if (downloadLink) {
        if (r.pdf_path) {
          downloadLink.href = `../../${r.pdf_path}`;
          downloadLink.style.display = 'inline-block';
        } else {
          downloadLink.style.display = 'none';
        }
      }
      
      // Store report ID for PDF preview
      const viewModalElement = document.getElementById('viewReportModal');
      if (viewModalElement) {
        viewModalElement.dataset.reportId = reportId;
      }
      
      // Show modal
      const viewReportModal = getModal('viewReport');
      if (viewReportModal) viewReportModal.show();
    })
    .catch(err => {
      console.error("View Report Error:", err);
      Swal.fire('Error', 'Failed to load report details', 'error');
    });
}

/**
 * Open Report PDF in Preview
 */
function openReportPdf(reportId) {
  if (!reportId) return;
  
  fetch(API_BASE + `getReportPdf.php?report_id=${encodeURIComponent(reportId)}`)
    .then(res => {
      if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
      return res.json();
    })
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

    Swal.showLoading();

    fetch(API_BASE + "verifyReport.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: `report_id=${encodeURIComponent(reportId)}`
    })
    .then(res => {
      if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
      return res.json();
    })
    .then(data => {
      Swal.hideLoading();
      
      if (data.status === 'success') {
        Swal.fire('Verified!', 'Report has been verified successfully', 'success');
        loadReports();
      } else {
        Swal.fire('Error', data.message || 'Failed to verify report', 'error');
      }
    })
    .catch(err => {
      console.error("Verify Report Error:", err);
      Swal.hideLoading();
      Swal.fire('Error', 'Failed to verify report', 'error');
    });
  });
}

/**
 * Assign Police to Report
 */
function assignPolice(reportId) {
  if (!reportId) {
    Swal.fire('Error', 'Invalid report ID', 'error');
    return;
  }

  fetch(API_BASE + `getSingleReport.php?report_id=${encodeURIComponent(reportId)}`)
    .then(res => {
      if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
      return res.json();
    })
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
      const assignModal = document.getElementById('assignPoliceModal');
      if (assignModal) {
        assignModal.dataset.reportId = reportId;
        document.getElementById('assignReportId').textContent = `#R${reportId}`;
        document.getElementById('assignMissingPerson').textContent = report.missing_person_name || 'Unknown';
      }
      
      // Load police list
      loadPoliceForAssignment();
      
      const assignPoliceModal = getModal('assignPolice');
      if (assignPoliceModal) assignPoliceModal.show();
    })
    .catch(err => {
      console.error("Check Report Error:", err);
      Swal.fire('Error', 'Failed to verify report status', 'error');
    });
}

/**
 * Load Police for Assignment
 */
function loadPoliceForAssignment() {
  fetch(API_BASE + "getPolice.php")
    .then(res => {
      if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
      return res.json();
    })
    .then(policeList => {
      const select = document.getElementById('assignPoliceSelect');
      if (!select) return;
      
      select.innerHTML = '<option value="">-- Select Police Officer --</option>';
      
      const activePolice = Array.isArray(policeList) 
        ? policeList.filter(p => p.status === 'active')
        : [];
      
      if (!activePolice.length) {
        select.innerHTML = '<option value="">No active police officers available</option>';
        return;
      }
      
      activePolice.forEach(p => {
        const option = document.createElement('option');
        option.value = p.police_id;
        option.textContent = `${p.fullname || 'Unknown'} - ${p.station_name || 'N/A'}, ${p.district || 'N/A'}`;
        select.appendChild(option);
      });
    })
    .catch(err => {
      console.error("Load Police Error:", err);
      const select = document.getElementById('assignPoliceSelect');
      if (select) {
        select.innerHTML = '<option value="">Error loading police</option>';
      }
    });
}

/**
 * Confirm Police Assignment
 */
function confirmPoliceAssignment() {
  const modal = document.getElementById('assignPoliceModal');
  if (!modal) return;
  
  const reportId = modal.dataset.reportId;
  const policeSelect = document.getElementById('assignPoliceSelect');
  
  if (!policeSelect || !policeSelect.value) {
    Swal.fire('Select Police', 'Please select a police officer', 'warning');
    return;
  }
  
  const policeId = policeSelect.value;
  const policeName = policeSelect.options[policeSelect.selectedIndex].text;
  
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

    Swal.showLoading();
    
    fetch(API_BASE + "assignPolice.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: `report_id=${encodeURIComponent(reportId)}&police_id=${encodeURIComponent(policeId)}`
    })
    .then(res => {
      if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
      return res.json();
    })
    .then(data => {
      Swal.hideLoading();
      
      if (data.status === 'success') {
        Swal.fire('Assigned!', 'Police has been assigned to the report', 'success');
        
        const assignPoliceModal = getModal('assignPolice');
        if (assignPoliceModal) assignPoliceModal.hide();
        
        loadReports();
      } else {
        Swal.fire('Error', data.message || 'Failed to assign police', 'error');
      }
    })
    .catch(err => {
      console.error("Assign Police Error:", err);
      Swal.hideLoading();
      Swal.fire('Error', 'Failed to assign police', 'error');
    });
  });
}

/**
 * Delete Report
 */
function deleteReport(reportId, personName) {
  if (!reportId) {
    Swal.fire('Error', 'Invalid report ID', 'error');
    return;
  }

  Swal.fire({
    title: 'Delete Report?',
    html: `<p>This will permanently delete the report for <strong>${escapeHtml(personName || 'Unknown')}</strong>.</p>
           <p class="text-danger small">This action cannot be undone!</p>`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Yes, Delete',
    cancelButtonText: 'No, Cancel',
    confirmButtonColor: '#dc3545'
  }).then((result) => {
    if (!result.isConfirmed) return;

    Swal.showLoading();

    fetch(API_BASE + "deleteReport.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: `report_id=${encodeURIComponent(reportId)}`
    })
    .then(res => {
      if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
      return res.json();
    })
    .then(data => {
      Swal.hideLoading();
      
      if (data.status === 'success') {
        Swal.fire('Deleted!', 'Report has been deleted', 'success');
        loadReports();
      } else {
        Swal.fire('Error', data.message || 'Failed to delete report', 'error');
      }
    })
    .catch(err => {
      console.error("Delete Report Error:", err);
      Swal.hideLoading();
      Swal.fire('Error', 'Failed to delete report', 'error');
    });
  });
}

/**
 * Filter Reports
 */
function filterReports(searchTerm) {
  const rows = document.querySelectorAll('#reportsTable tr[data-report-id]');
  const term = searchTerm.toLowerCase();
  
  rows.forEach(row => {
    const text = row.textContent.toLowerCase();
    row.style.display = text.includes(term) ? '' : 'none';
  });
}

/**
 * Unified report actions handler
 */
document.addEventListener('click', function(e) {
  const viewBtn = e.target.closest('[data-action="view-report"]');
  if (viewBtn) {
    viewReport(viewBtn.dataset.id);
  }
  
  const assignBtn = e.target.closest('[data-action="assign-police"]');
  if (assignBtn) {
    assignPolice(assignBtn.dataset.id);
  }
  
  const verifyBtn = e.target.closest('[data-action="verify-report"]');
  if (verifyBtn) {
    verifyReport(verifyBtn.dataset.id);
  }
  
  const deleteBtn = e.target.closest('[data-action="delete-report"]');
  if (deleteBtn) {
    deleteReport(deleteBtn.dataset.id, deleteBtn.dataset.name);
  }
});

/**
 * Report Search Handler
 */
document.addEventListener("DOMContentLoaded", function() {
  const reportSearch = document.getElementById('reportSearch');
  if (reportSearch) {
    reportSearch.addEventListener('input', function() {
      filterReports(this.value);
    });
  }
  
  // Load reports on page load
  if (document.getElementById("reportsTable")) {
    loadReports();
  }
});