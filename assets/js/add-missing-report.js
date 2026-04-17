/**
 * Add Missing Report Functionality
 * Handles submitting missing person reports
 */

document.addEventListener('DOMContentLoaded', function() {
    initAddMissingReport();
    // Load missing reports table
    loadMissingReports();
});

/**
 * Show new missing report form
 */
function showNewMissingReportForm() {
    const form = document.getElementById('addMissingReportForm');
    if (form) {
        form.reset();
        const preview = document.getElementById('missingPhotoPreview');
        if (preview) {
            preview.style.display = 'none';
            preview.src = '';
        }
        form.scrollIntoView({ behavior: 'smooth' });
    }
}

/**
 * Initialize add missing report functionality
 */
function initAddMissingReport() {
    const form = document.getElementById('addMissingReportForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            saveMissingReport();
        });
    }
    
    // Initialize photo preview
    const photoInput = document.getElementById('missingPhoto');
    if (photoInput) {
        photoInput.addEventListener('change', function(e) {
            previewImage(e.target, 'missingPhotoPreview');
        });
    }
}

/**
 * Preview uploaded image
 */
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    if (!preview) return;
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

/**
 * Save Missing Report to database
 */
function saveMissingReport() {
    const form = document.getElementById('addMissingReportForm');
    if (!form) {
        showNotification('error', 'Form not found');
        return;
    }
    
    const formData = new FormData(form);
    
    // Add current datetime if not set
    if (!formData.get('last_seen_datetime')) {
        formData.set('last_seen_datetime', new Date().toISOString().slice(0, 16));
    }
    
    // Show loading
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Submitting...';
    submitBtn.disabled = true;
    
    // Send to API
    fetch('../admin/api/addMissingReport.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            Swal.fire({
                icon: 'success',
                title: 'Report Submitted!',
                html: `<p>Missing report added successfully.</p>
                       <p class="text-info">Report ID: #R${data.report_id}</p>`,
                confirmButtonText: 'OK',
                cancelButtonText: 'Close',
                confirmButtonColor: '#0d6efd',
                background: '#16161e',
                color: '#fff'
            }).then((result) => {
                form.reset();
                // Clear photo preview
                const preview = document.getElementById('missingPhotoPreview');
                if (preview) {
                    preview.style.display = 'none';
                    preview.src = '';
                }
            });
            
            // Refresh reports table
            loadMissingReports();
        } else {
            showNotification('error', data.message || 'Failed to submit report');
        }
    })
    .catch(error => {
        console.error('Error submitting report:', error);
        showNotification('error', 'An error occurred while submitting');
    })
    .finally(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
}

/**
 * View missing report details
 */
function viewMissingReport(reportId) {
    if (!reportId) return;
    
    fetch(`../admin/api/getSingleReport.php?report_id=${reportId}`)
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            const report = data.data;
            
            const html = `
                <div class="row">
                    <div class="col-md-4 text-center">
                        <img src="${report.photo ? '../' + report.photo : 'https://ui-avatars.com/api/?name=Missing&background=667eea&color=fff&size=200'}"
                             class="img-fluid rounded" style="max-width:200px;" />
                    </div>
                    <div class="col-md-8">
                        <h6>${report.missing_name || 'Unknown'}</h6>
                        <p><strong>Age:</strong> ${report.age || 'N/A'} | <strong>Gender:</strong> ${report.gender || 'N/A'}</p>
                        <p><strong>Last Seen:</strong> ${report.last_seen_location || 'Not specified'}</p>
                        <p><strong>Date Missing:</strong> ${formatDate(report.last_seen_datetime)}</p>
                        <p><strong>Description:</strong> ${report.description || 'None'}</p>
                        <p><strong>Status:</strong> <span class="badge bg-${getStatusColor(report.status)}">${report.status || 'Unknown'}</span></p>
                        <p><strong>Reported On:</strong> ${formatDate(report.created_at)}</p>
                    </div>
                </div>
            `;
            
            Swal.fire({
                title: 'Report Details',
                html: html,
                confirmButtonText: 'Close',
                background: '#16161e',
                color: '#fff',
                width: '600px'
            });
        } else {
            showNotification('error', 'Failed to load details');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('error', 'An error occurred');
    });
}

/**
 * Delete missing report
 */
function deleteMissingReport(reportId, personName) {
    if (!reportId) return;
    
    Swal.fire({
        title: 'Delete Report?',
        html: `<p>This will permanently delete the report for <strong>${personName || 'this person'}</strong>.</p>
               <p class="text-danger small">This action cannot be undone!</p>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, Delete',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#dc3545',
        background: '#16161e',
        color: '#fff'
    }).then((result) => {
        if (!result.isConfirmed) return;
        
        fetch('../admin/api/deleteReport.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'report_id=' + reportId
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showNotification('success', 'Report deleted successfully');
                loadMissingReports();
            } else {
                showNotification('error', data.message || 'Failed to delete');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('error', 'An error occurred');
        });
    });
}

/**
 * Load all missing reports into table
 */
function loadMissingReports() {
    const tableBody = document.getElementById('missingReportsTableBody');
    if (!tableBody) return;
    
    tableBody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">Loading...</td></tr>';
    
    fetch('../admin/api/getReports.php')
    .then(response => response.json())
    .then(data => {
        if (data.status !== 'success' || !data.data || data.data.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No reports submitted yet</td></tr>';
            return;
        }
        
        let html = '';
        data.data.forEach(report => {
            html += `
                <tr>
                    <td><strong>#R${report.report_id}</strong></td>
                    <td>${escapeHtml(report.missing_name || 'Unknown')}</td>
                    <td>${report.age || 'N/A'} / ${report.gender || 'N/A'}</td>
                    <td>${escapeHtml(report.last_seen_location || 'N/A')}</td>
                    <td>${formatDate(report.last_seen_datetime)}</td>
                    <td><span class="badge bg-${getStatusColor(report.status)}">${report.status || 'Pending'}</span></td>
                    <td>
                        <div class="inline-actions">
                            <i class="bi bi-eye text-info" title="View Details" 
                               onclick="viewMissingReport(${report.report_id})"></i>
                            <i class="bi bi-trash text-danger" title="Delete" 
                               onclick="deleteMissingReport(${report.report_id}, '${escapeHtml(report.missing_name)}')"></i>
                        </div>
                    </td>
                </tr>
            `;
        });
        
        tableBody.innerHTML = html;
    })
    .catch(error => {
        console.error('Error loading reports:', error);
        tableBody.innerHTML = '<tr><td colspan="7" class="text-center text-danger">Error loading data</td></tr>';
    });
}

/**
 * Get status badge color
 */
function getStatusColor(status) {
    const colors = {
        'pending': 'warning',
        'verified': 'info',
        'assigned': 'primary',
        'closed': 'success'
    };
    return colors[status] || 'secondary';
}

// Helper function to escape HTML
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Helper function to format dates
function formatDate(dateStr) {
    if (!dateStr) return 'N/A';
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

// Show notification using SweetAlert
function showNotification(type, message) {
    Swal.fire({
        icon: type,
        title: message,
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        background: '#16161e',
        color: '#fff'
    });
}

// Expose functions globally
window.saveMissingReport = saveMissingReport;
window.viewMissingReport = viewMissingReport;
window.deleteMissingReport = deleteMissingReport;
window.loadMissingReports = loadMissingReports;
window.showNewMissingReportForm = showNewMissingReportForm;

