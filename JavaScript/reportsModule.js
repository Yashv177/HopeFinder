/**
 * Missing Reports Management - Dashboard JavaScript
 * Uses your database schema: missing_reports, report_assignments
 */

// Initialize modals when DOM is ready
let viewReportModal, assignPoliceModal;

document.addEventListener("DOMContentLoaded", function() {
  // Initialize Bootstrap Modals
  viewReportModal = new bootstrap.Modal(document.getElementById('viewReportModal'));
  assignPoliceModal = new bootstrap.Modal(document.getElementById('assignPoliceModal'));
  
  // Report search input handler
  const reportSearch = document.getElementById('reportSearch');
  if (reportSearch) {
    reportSearch.addEventListener('input', function() {
      filterReports(this.value);
    });
  }
  
  // Load reports on page load
  loadReports();
});

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
            <td><input type="checkbox" class="form-check-input target-select" data-report-id="${r.report_id}"></td>
            <td><strong>#R${r.report_id}</strong></td>
            <td>${escapeHtml(r.missing_name || 'Unknown')}</td>
            <td>${ageGender}</td>
            <td>${escapeHtml(r.last_seen_location || 'Unknown')}</td>
            <td>${reporter}</td>
            <td>${formatDate(r.created_at)}</td>
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
                 onclick="deleteReport(${r.report_id}, '${escapeHtml(r.missing_name || 'Unknown')}')"></i>
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
      document.getElementById('viewLocation').textContent = r.last_seen_location || 'Not specified';
      document.getElementById('viewDateMissing').textContent = r.last_seen_datetime || 'Not specified';
      document.getElementById('viewDescription').textContent = r.description || 'No description';
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
      
      // Handle PDF download link
      const downloadLink = document.getElementById('viewReportDownloadLink');
      if (r.document_pdf) {
        downloadLink.href = `../../${r.document_pdf}`;
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
      openPdfPreview(pdfUrl, `Report #R${reportId} - ${response.missing_name}`);
    })
    .catch(err => {
      console.error("Get PDF Error:", err);
      Swal.fire('Error', 'Failed to load PDF', 'error');
    });
}

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
        loadReports();
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
      
      if (report.assigned_police) {
        Swal.fire({
          title: 'Already Assigned',
          html: `<p>This report is already assigned to:</p>
                 <strong>${report.assigned_police.name}</strong><br>
                 <small>${report.assigned_police.station}</small>`,
          icon: 'info'
        });
        return;
      }
      
      // Store report ID and show assign modal
      document.getElementById('assignPoliceModal').dataset.reportId = reportId;
      document.getElementById('assignReportId').textContent = `#R${reportId}`;
      document.getElementById('assignMissingName').textContent = report.missing_name;
      
      // Load police list
      loadPoliceForAssignment();
      assignPoliceModal.show();
    })
    .catch(err => {
      console.error("Check Report Error:", err);
      Swal.fire('Error', 'Failed to verify report status', 'error');
    });
}

function loadPoliceForAssignment() {
  fetch("../admin/api/getPolice.php")
    .then(res => res.json())
    .then(policeList => {
      const select = document.getElementById('assignPoliceSelect');
      select.innerHTML = '<option value="">-- Select Police Officer --</option>';
      
      // Show all active police officers (status comes from users table via u.status)
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
        loadReports();
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
        const row = document.querySelector(`tr[data-report-id="${reportId}"]`);
        if (row) row.remove();
        
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

function filterReports(searchTerm) {
  const rows = document.querySelectorAll('#reportsTable tr[data-report-id]');
  const term = searchTerm.toLowerCase();
  
  rows.forEach(row => {
    const text = row.textContent.toLowerCase();
    row.style.display = text.includes(term) ? '' : 'none';
  });
}

function formatDate(dateStr) {
  if (!dateStr) return '-';
  const date = new Date(dateStr);
  return date.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

function capitalizeFirst(str) {
  if (!str) return '';
  return str.charAt(0).toUpperCase() + str.slice(1);
}

function escapeHtml(text) {
  if (!text) return '';
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

