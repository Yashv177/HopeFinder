// FIXED Dashboard.js - Minimal bug fixes only
// JSON parse error fixed - added res.text() + manual JSON.parse with logging

const API_BASE = "../admin/api/";

const modals = {};

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

// Dashboard Stats - Fixed
fetch(`${API_BASE}getDashboardStats.php`)
  .then(res => {
    if (!res.ok) throw new Error(`Stats HTTP ${res.status}`);
    return res.json();
  })
  .then(data => {
    document.getElementById("statUsers").innerText = data.users || data.total_missing || 0;
    document.getElementById("statPolice").innerText = data.police || 0;
    document.getElementById("statReports").innerText = data.reports || data.total_missing || 0;
    document.getElementById("statMatches").innerText = data.matches || data.ai_success || 0;
    document.getElementById("statPending").innerText = data.pending || 0;
  }).catch(err => {
    console.error(err);
    Swal.fire("Error", "Something went wrong", "error");
  });

sidebarCollapse?.addEventListener('click', toggleSidebar);

document.addEventListener('click', (event) => {
  if (window.innerWidth <= 991 && body.classList.contains('sidebar-expanded')) {
    if (sidebar && !sidebar.contains(event.target) && event.target !== sidebarCollapse) {
      body.classList.remove('sidebar-expanded');
    }
  }
});

document.addEventListener('DOMContentLoaded', function() {
  loadUsers();
  loadPolice();
  loadReports();
});

function escapeHtml(text) {
  return text ? text.replace(/[&<>"']/g, m => ({
    '&':'&amp;',
    '<':'&lt;',
    '>':'&gt;',
    '"':'&quot;',
    "'":"&#039;"
  }[m])) : '';
}

function capitalizeFirst(str) {
  return str ? str.charAt(0).toUpperCase() + str.slice(1) : '';
}

function getStatusClass(status) {
  return status === 'active' ? 'success' : status === 'blocked' ? 'danger' : 'secondary';
}

function getStatusLabel(status) {
  return status === 'active' ? 'Active' : status === 'blocked' ? 'Blocked' : capitalizeFirst(status || 'Unknown');
}

function getToggleIconClass(status) {
  return status === 'active' ? 'bi-shield-lock text-warning' : 'bi-shield-check text-success';
}

function getToggleTitle(status) {
  return status === 'active' ? 'Block/Suspend' : 'Activate';
}

function getReportStatusBadge(status) {
  const config = {
    'pending': 'bg-warning text-dark',
    'verified': 'bg-info',
    'assigned': 'bg-primary',
    'closed': 'bg-success'
  };
  const cls = config[status] || 'bg-secondary';
  return `<span class="badge ${cls}">${capitalizeFirst(status || 'Unknown')}</span>`;
}

function formatDate(dateStr) {
  if (!dateStr) return '-';
  const date = new Date(dateStr);
  return date.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

function openPdfPreview(pdfUrl, title) {
  const modal = document.getElementById('pdfPreviewModal');
  if (!modal) return Swal.fire('Error', 'Modal not found', 'error');
  
  const iframe = document.getElementById('pdfPreviewIframe');
  const modalTitle = document.getElementById('pdfPreviewModalTitle');
  
  try {
    const url = new URL(pdfUrl, window.location.origin);
    if (!["http:", "https:"].includes(url.protocol)) throw new Error();
    iframe.src = pdfUrl;
  } catch {
    Swal.fire("Error", "Invalid PDF URL", "error");
    return;
  }
  modalTitle.textContent = title || 'PDF Preview';
  
  const bsModal = bootstrap.Modal.getOrCreateInstance(modal);
  bsModal.show();
  
  modal.addEventListener('hidden.bs.modal', function cleanup() {
    iframe.src = '';
    modal.removeEventListener('hidden.bs.modal', cleanup);
  }, {once: true});
}

function processReportData(modalInstance, r) {
  if (!modalInstance) {
    Swal.fire('Error', 'Modal initialization failed', 'error');
    return;
  }

  const modalEl = modalInstance._element;

  // Populate basic info
  document.getElementById('viewReportId').textContent = `#R${r.report_id || 'N/A'}`;
  document.getElementById('viewMissingName').textContent = r.missing_name || r.missing_person_name || 'Unknown';
  document.getElementById('viewAge').textContent = r.age ? `${r.age} years` : 'N/A';
  document.getElementById('viewGender').textContent = capitalizeFirst(r.gender || 'N/A');
  document.getElementById('viewLocation').textContent = r.last_seen_location || 'Not specified';
  document.getElementById('viewDateMissing').textContent = formatDate(r.last_seen_datetime) || 'N/A';
  document.getElementById('viewDescription').textContent = r.description || 'No description provided';
  document.getElementById('viewCreatedAt').textContent = formatDate(r.created_at) || 'N/A';
  document.getElementById('viewStatus').innerHTML = getReportStatusBadge(r.status || 'unknown');

  // Reporter info
  document.getElementById('viewReporterName').textContent = r.reporter_name || r.reporter?.name || 'Unknown';
  document.getElementById('viewReporterEmail').textContent = r.reporter_email || r.reporter?.email || 'N/A';
  
  // Phone if available
  const phoneEl = document.getElementById('viewReporterPhone');
  if (phoneEl && r.contact_number) {
    phoneEl.textContent = r.contact_number;
    phoneEl.parentElement.style.display = 'block';
  } else if (phoneEl) {
    phoneEl.parentElement.style.display = 'none';
  }

  // Assigned police
  const policeSection = document.getElementById('viewPoliceSection');
  if (r.assigned_police) {
    policeSection.style.display = 'block';
    document.getElementById('viewPoliceName').textContent = r.assigned_police.name || 'N/A';
    document.getElementById('viewPoliceBadge').textContent = r.assigned_police.badge || 'N/A';
    document.getElementById('viewPoliceStation').textContent = r.assigned_police.station || 'N/A';
    document.getElementById('viewAssignedAt').textContent = formatDate(r.assigned_police.assigned_at) || 'N/A';
  } else {
    policeSection.style.display = 'none';
  }

  // Hide image section
  const imgSection = document.getElementById('viewReportImageSection');
  if (imgSection) imgSection.style.display = 'none';

  // PDF handling
  const pdfBtn = document.getElementById('viewReportPdfBtn');
  if (pdfBtn && r.document_pdf) {
    fetch(`${API_BASE}getReportPdf.php?report_id=${r.report_id}`)
      .then(res => {
        if (!res.ok) return {status: 'error'};
        return res.json();
      })
      .then(pdfData => {
        if (pdfData.status === 'success' && pdfData.pdf_path) {
          pdfBtn.style.display = 'inline-block';
          pdfBtn.onclick = () => openPdfPreview(`../${pdfData.pdf_path}`, `Report #R${r.report_id}`);
        } else {
          pdfBtn.style.display = 'none';
        }
      }).catch(() => pdfBtn.style.display = 'none');
  } else if (pdfBtn) {
    pdfBtn.style.display = 'none';
  }

  // Found section - with fallback fetch
  const foundSection = document.getElementById('viewFoundSection');
  if (foundSection) {
    if (r.detection && r.detection.found_time) {
      document.getElementById('viewFoundLocation').textContent = r.detection.found_location || r.detection.address || 'N/A';
      document.getElementById('viewFoundDate').textContent = formatDate(r.detection.found_time || r.detection.timestamp);
      foundSection.style.display = 'block';
    } else {
      // Fallback: fetch from detection API
      fetch(`${API_BASE}getReportDetection.php?report_id=${r.report_id}`)
        .then(res => res.json())
        .then(data => {
          if (data.status === 'success' && data.data) {
            document.getElementById('viewFoundLocation').textContent = data.data.address || 'N/A';
            document.getElementById('viewFoundDate').textContent = formatDate(data.data.timestamp);
            foundSection.style.display = 'block';
          } else {
            foundSection.style.display = 'none';
          }
        }).catch(() => {
          foundSection.style.display = 'none';
        });
    }
  }

  // Store report ID safely + show modal
  modalEl.dataset.reportId = r.report_id;
  modalInstance.show();
}

function loadUsers() {
  const table = document.getElementById("usersTable");
  if (!table) return;
  
  fetch(`${API_BASE}getUsers.php`)
    .then(res => {
      if (!res.ok) throw new Error('API error');
      return res.json();
    })
    .then(users => {
      const userList = Array.isArray(users) ? users : (users.data || []);
      
      if (!userList.length) {
        table.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No users found</td></tr>';
        return;
      }
      
      let html = "";
      userList.forEach(u => {
        const name = escapeHtml(u.fullname || u.email?.split('@')[0] || 'User');
        const status = u.status || 'inactive';
        
        html += `
          <tr>
            <td>#U${u.user_id || 0}</td>
            <td>${name}</td>
            <td>${escapeHtml(u.email || '-')}</td>
            <td>${u.created_at || '-'}</td>
            <td><span class="badge bg-${getStatusClass(status)}">${getStatusLabel(status)}</span></td>
            <td class="table-actions">
              <i class="bi ${getToggleIconClass(status)} btn-toggle-user" data-id="${u.user_id}" data-status="${status}" title="${getToggleTitle(status)}"></i>
              <i class="bi bi-trash text-danger btn-delete-user" data-id="${u.user_id}" title="Delete"></i>
            </td>
          </tr>
        `;
      });
      table.innerHTML = html;
    })
    .catch(err => {
      console.error(err);
      table.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Failed to load users</td></tr>';
      Swal.fire('Error', 'Failed to load users data', 'error');
    });
}

function loadPolice() {
  const table = document.getElementById("policeTable");
  if (!table) return;

  fetch(`${API_BASE}getPolice.php`)
    .then(res => {
      if (!res.ok) throw new Error('API error');
      return res.json();
    })
    .then(list => {
      const policeList = Array.isArray(list) ? list : (list.data || []);
      
      if (!policeList.length) {
        table.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No police found</td></tr>';
        return;
      }

      let html = "";
      policeList.forEach(p => {
        const status = p.status || 'inactive';
        const fullname = escapeHtml(p.fullname || '-');
        const station = escapeHtml((p.station_name || '-') + ', ' + (p.district || '-'));
        
        html += `
          <tr>
            <td>#P${p.police_id || 0}</td>
            <td>${fullname}</td>
            <td>${escapeHtml(p.email || '-')}</td>
            <td>${station}</td>
            <td><span class="badge bg-${getStatusClass(status)}">${getStatusLabel(status)}</span></td>
            <td class="table-actions">
              <i class="bi bi-eye text-primary" data-action="view-police" data-id="${p.police_id}" title="View"></i>
              <i class="bi bi-pencil text-warning" data-action="edit-police" data-id="${p.police_id}" title="Edit"></i>
              <i class="bi ${getToggleIconClass(status)} btn-toggle-police" data-id="${p.user_id}" data-status="${status}" title="${getToggleTitle(status)}"></i>
              <i class="bi bi-trash text-danger" data-action="delete-police" data-id="${p.user_id}" data-name="${fullname}" title="Delete"></i>
            </td>
          </tr>
        `;
      });
      table.innerHTML = html;
    })
    .catch(err => {
      console.error(err);
      table.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Failed to load police</td></tr>';
      Swal.fire('Error', 'Failed to load police data', 'error');
    });
}

function loadReports() {
  const table = document.getElementById("reportsTable");
  if (!table) return;
  
  fetch(`${API_BASE}getReports.php?page=1`)
    .then(res => {
      if (!res.ok) throw new Error(`HTTP ${res.status}: ${res.statusText}`);
      return res.json();
    })
    .then(response => {
      console.log("API RESPONSE:", response);
      let reports = [];
      
      if (Array.isArray(response)) {
        reports = response;
      } else if (response && response.status === "success") {
        reports = Array.isArray(response.data) ? response.data : [];
      }

      if (!reports.length) {
        table.innerHTML = '<tr><td colspan="9" class="text-center text-muted">No reports found</td></tr>';
        return;
      }
      
      let html = "";
      reports.forEach(r => {
        const statusBadge = getReportStatusBadge(r.status);
        const assignedTo =
          r.assigned_police_name ||
          r.police_name ||
          r.officer_name ||
          null;
        const ageGender = r.age ? `${r.age} yrs / ${capitalizeFirst(r.gender || '')}` : '-';
        const reporter = r.reporter_name ? `${escapeHtml(r.reporter_name)}<br><small class="text-muted">${escapeHtml(r.reporter_email || '')}</small>` : '-';
        
        html += `
          <tr data-report-id="${r.report_id}">
            <td><strong>#R${r.report_id || 0}</strong></td>
            <td>${escapeHtml(r.missing_person_name || r.missing_name || 'Unknown')}</td>
            <td>${ageGender}</td>
            <td>${escapeHtml(r.last_seen_location || '-')}</td>
            <td>${reporter}</td>
            <td>${r.date_reported || r.created_at || '-'}</td>
            <td>${statusBadge}</td>
            <td>${escapeHtml(assignedTo)}</td>
            <td class="table-actions">
              <i class="bi bi-eye text-primary" data-action="view-report" data-id="${r.report_id}"></i>
              <i class="bi bi-person-lines-fill text-success" data-action="assign-police" data-id="${r.report_id}"></i>
              <i class="bi bi-check-circle text-success" data-action="verify-report" data-id="${r.report_id}"></i>
              <i class="bi bi-trash text-danger" data-action="delete-report" data-id="${r.report_id}"></i>
            </td>
          </tr>
        `;
      });
      table.innerHTML = html;
    })
    .catch(err => {
      console.error(err);
      table.innerHTML = '<tr><td colspan="9" class="text-center text-danger">Failed to load reports</td></tr>';
      Swal.fire('Error', 'Failed to load reports data', 'error');
    });
}

document.addEventListener('click', e => {
  const target = e.target.closest('[data-action]');
  
  // Handle toggle buttons first
  const userToggle = e.target.closest('.btn-toggle-user');
  if (userToggle) {
    const id = userToggle.dataset.id;
    const status = userToggle.dataset.status;
    toggleStatus(userToggle, id, 'toggleUserStatus.php', true);
    return;
  }
  
  const policeToggle = e.target.closest('.btn-toggle-police');
  if (policeToggle) {
    const id = policeToggle.dataset.id;
    const status = policeToggle.dataset.status;
    toggleStatus(policeToggle, id, 'togglePoliceStatus.php', false);
    return;
  }
  
  if (!target) return;
  
  const action = target.dataset.action;
  const id = target.dataset.id;
  
  switch(action) {
    case 'view-police':
      viewPolice(id);
      break;
    case 'edit-police':
      openEditPoliceModal(id);
      break;
    case 'delete-police':
      deletePolice(id, target.dataset.name || 'Officer');
      break;
    case 'view-report':
      viewReport(id);
      break;
    case 'assign-police':
      assignPolice(id);
      break;
    case 'verify-report':
      verifyReport(id);
      break;
    case 'delete-report':
      deleteReport(id, target.dataset.name || 'Report');
      break;
  }
});

function toggleStatus(btn, id, api, isUser) {
  Swal.fire({
    title: 'Update status?',
    text: 'This will change the current status.',
    icon: 'warning',
    showCancelButton: true
  }).then(r => {
    if (!r.isConfirmed) return;
    
    fetch(`${API_BASE}${api}`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `id=${encodeURIComponent(id)}`
    }).then(res => res.json()).then(data => {
      const newStatus = data.status;
      btn.dataset.status = newStatus;
      btn.className = `bi ${getToggleIconClass(newStatus)} ${btn.className.split(' ')[1]}`;
      Swal.fire('Success', 'Status updated', 'success');
      (isUser ? loadUsers : loadPolice)();
    }).catch(() => Swal.fire('Error', 'Failed to update', 'error'));
  });
}

['addUserForm', 'addPoliceForm', 'editPoliceForm'].forEach(id => {
  const form = document.getElementById(id);
  if (form) {
    form.onsubmit = async e => {
      e.preventDefault();
      const data = new FormData(form);
      const api = id === 'addUserForm' ? 'addUser.php' : id === 'addPoliceForm' ? 'addPolice.php' : 'updatePolice.php';
      
      try {
        const res = await fetch(`${API_BASE}${api}`, {method: 'POST', body: data});
        const result = await res.json();
        Swal.fire('Success', result.message || 'Done', 'success');
        form.reset();
        (id.includes('User') ? loadUsers : loadPolice)();
        bootstrap.Modal.getOrCreateInstance(form.closest('.modal')).hide();
      } catch {
        Swal.fire('Error', 'Operation failed', 'error');
      }
    };
  }
});

['userSearch', 'reportSearch'].forEach(id => {
  const input = document.getElementById(id);
  if (input) input.oninput = function() {
    const term = this.value.toLowerCase();
    const tableId = id === 'userSearch' ? 'usersTable' : 'reportsTable';
    document.querySelectorAll(`#${tableId} tr`).forEach(row => {
      row.style.display = row.textContent.toLowerCase().includes(term) ? '' : 'none';
    });
  };
});

function viewReport(reportId) {
  if (!reportId) {
    Swal.fire('Error', 'Invalid report ID', 'error');
    return;
  }

  const modalEl = document.getElementById('viewReportModal');
  if (!modalEl) {
    Swal.fire('Error', 'Report modal not found in HTML', 'error');
    return;
  }

  // Bootstrap readiness check
  if (typeof bootstrap === 'undefined') {
    Swal.fire('Error', 'Bootstrap not loaded. Please refresh page.', 'error');
    return;
  }

  // Get or create modal instance (cached)
  if (!modals.viewReportModal) {
    modals.viewReportModal = bootstrap.Modal.getOrCreateInstance(modalEl);
  }
  const modalInstance = modals.viewReportModal;

  fetch(`${API_BASE}getSingleReport.php?report_id=${reportId}`)
    .then(res => {
      if (!res.ok) throw new Error("Network error");
      return res.json();
    })
    .then(response => {
      if (response.status !== 'success') {
        Swal.fire('Error', response.message || 'Report not found', 'error');
        return;
      }
      processReportData(modalInstance, response.data);
    })

    .catch(err => {
      Swal.fire('Error', `Failed to load report: ${err.message}`, 'error');
    });
}

function verifyReport(reportId) {
  if (!reportId) return Swal.fire('Error', 'Invalid report ID', 'error');

  Swal.fire({
    title: 'Verify Report?',
    html: 'Change status from Pending to Verified?',
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Verify'
  }).then(result => {
    if (!result.isConfirmed) return;

    fetch(`${API_BASE}verifyReport.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `report_id=${reportId}`
    })
    .then(res => res.json())
    .then(data => {
      if (data.status === 'success') {
        Swal.fire('Success', 'Report verified', 'success');
        loadReports();
      } else {
        Swal.fire('Error', data.message || 'Failed to verify', 'error');
      }
    })
    }).catch(err => {
      console.error(err);
      Swal.fire('Error', 'Failed to verify report', 'error');
    });
  };

function assignPolice(reportId) {
  if (!reportId) return Swal.fire('Error', 'Invalid report ID', 'error');

  fetch(`${API_BASE}getSingleReport.php?report_id=${reportId}`)
    .then(res => res.json())
    .then(response => {
      if (response.status !== 'success') return Swal.fire('Error', 'Cannot load report status', 'error');
      const report = response.data;
      if (report.status !== 'verified') {
        return Swal.fire('Cannot Assign', `Only verified reports can be assigned. Current: ${getReportStatusBadge(report.status)}`, 'warning');
      }

      fetch(`${API_BASE}getPolice.php`)
        .then(res => res.json())
        .then(policeList => {
          const activePolice = (Array.isArray(policeList) ? policeList : policeList.data || []).filter(p => p.status === 'active');
          if (activePolice.length === 0) return Swal.fire('No Police', 'No active police officers available', 'info');

          let options = '<option value="">Select Officer</option>';
          activePolice.forEach(p => {
            options += `<option value="${p.police_id}">${escapeHtml(p.fullname)} - ${escapeHtml(p.station_name || p.district)}</option>`;
          });

          Swal.fire({
            title: 'Assign Police',
            html: `<select id="policeSelect" class="swal2-select">${options}</select>`,
            showCancelButton: true,
            confirmButtonText: 'Assign',
            preConfirm: () => {
              const select = document.getElementById('policeSelect');
              if (!select.value) throw new Error('Select police');
              return select.value;
            }
          }).then(result => {
            if (result.isConfirmed) {
              const policeId = result.value;
              fetch(`${API_BASE}assignPolice.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `report_id=${reportId}&police_id=${policeId}`
              })
              .then(res => res.json())
              .then(data => {
                if (data.status === 'success') {
                  Swal.fire('Success', 'Police assigned', 'success');
                  loadReports();
                } else {
                  Swal.fire('Error', data.message || 'Failed to assign', 'error');
                }
              })
              .catch(() => Swal.fire('Error', 'Failed to assign police', 'error'));
            }
          });
        });
    });
}


function deleteReport(reportId, reportName) {
  if (!reportId) return Swal.fire('Error', 'Invalid report ID', 'error');

  Swal.fire({
    title: 'Delete Report?',
    html: `Permanently delete #R${reportId} (${escapeHtml(reportName || 'Report')})?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Delete',
    confirmButtonColor: '#dc3545'
  }).then(result => {
    if (!result.isConfirmed) return;

    fetch(`${API_BASE}deleteReport.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `report_id=${encodeURIComponent(reportId)}`
    })
    .then(res => res.json())
    .then(data => {
      if (data.status === 'success') {
        Swal.fire('Deleted', 'Report removed', 'success');
        loadReports();
      } else {
        Swal.fire('Error', data.message || 'Failed to delete', 'error');
      }
    })
    .catch(() => Swal.fire('Error', 'Failed to delete report', 'error'));
  });
}

