/* ===========================================
   MISSING REPORTS CRUD OPERATIONS
   Police Dashboard - Complete CRUD with Actions
   =========================================== */

document.addEventListener('DOMContentLoaded', function() {
    initReportsManager();
});

/**
 * Initialize Reports Manager
 */
function initReportsManager() {
    loadReports();
    initReportFilters();
}

/**
 * Load all reports from database
 */
async function loadReports() {
    const tbody = document.getElementById('reportsTableBody');
    if (!tbody) return;
    
    tbody.innerHTML = `
        <tr>
            <td colspan="8" class="text-center text-muted py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Loading reports...</p>
            </td>
        </tr>
    `;
    
    try {
        const response = await fetch('../admin/api/getReports.php');
        const data = await response.json();
        
        if (data.status === 'error') {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center text-danger py-4">
                        <i class="bi bi-exclamation-triangle fs-1"></i>
                        <p class="mt-2">${data.message}</p>
                        <button class="btn btn-primary btn-sm mt-2" onclick="loadReports()">
                            <i class="bi bi-arrow-clockwise"></i> Retry
                        </button>
                    </td>
                </tr>
            `;
            return;
        }
        
        const reports = data.data || [];
        
        if (reports.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">
                        <i class="bi bi-inbox fs-1"></i>
                        <p class="mt-2">No reports found</p>
                    </td>
                </tr>
            `;
            return;
        }
        
        renderReports(reports);
        
    } catch (error) {
        console.error('Error loading reports:', error);
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center text-danger py-4">
                    <i class="bi bi-x-circle fs-1"></i>
                    <p class="mt-2">Error loading reports. Please try again.</p>
                    <button class="btn btn-primary btn-sm mt-2" onclick="loadReports()">
                        <i class="bi bi-arrow-clockwise"></i> Retry
                    </button>
                </td>
            </tr>
        `;
    }
}

/**
 * Render reports table
 */
function renderReports(reports) {
    const tbody = document.getElementById('reportsTableBody');
    if (!tbody) return;
    
    let html = '';
    
    reports.forEach((report, index) => {
        const statusBadge = getStatusBadge(report.status);
        const ageGender = `${report.age || 'N/A'} / ${report.gender || 'N/A'}`;
        const lastSeen = report.last_seen_location || 'Not specified';
        const date = formatDate(report.created_at);
        const reportId = '#R' + report.report_id;
        
        html += `
            <tr data-report-id="${report.report_id}" class="report-row" style="animation: fadeIn 0.3s ease ${index * 0.05}s both;">
                <td><strong>${reportId}</strong></td>
                <td>${escapeHtml(report.missing_name || 'Unknown')}</td>
                <td>${escapeHtml(ageGender)}</td>
                <td>${escapeHtml(lastSeen)}</td>
                <td>${date}</td>
                <td>${statusBadge}</td>
                <td>
                    <div class="inline-actions">
                        <i class="bi bi-eye text-info" title="View Details" 
                           onclick="viewReport(${report.report_id}); event.stopPropagation();"></i>
                        <i class="bi bi-check-circle text-success" title="Verify" 
                           onclick="verifyReport(${report.report_id}); event.stopPropagation();"></i>
                        <i class="bi bi-download text-secondary" title="Download PDF" 
                           onclick="downloadReport(${report.report_id}); event.stopPropagation();"></i>
                        <i class="bi bi-trash text-danger" title="Delete" 
                           onclick="deleteReport(${report.report_id}, '${escapeHtml(report.missing_name)}'); event.stopPropagation();"></i>
                    </div>
                </td>
            </tr>
        `;
    });
    
    tbody.innerHTML = html;
}

/**
 * Get status badge HTML
 */
function getStatusBadge(status) {
    const statusConfig = {
        'pending': { class: 'bg-warning text-dark', label: 'Pending' },
        'verified': { class: 'bg-info text-white', label: 'Verified' },
        'assigned': { class: 'bg-primary', label: 'Assigned' },
        'in_progress': { class: 'bg-primary', label: 'In Progress' },
        'closed': { class: 'bg-success', label: 'Closed' },
        'resolved': { class: 'bg-success', label: 'Resolved' }
    };
    
    const config = statusConfig[status?.toLowerCase()] || { class: 'bg-secondary', label: capitalizeFirst(status || 'Unknown') };
    return `<span class="badge ${config.class}">${config.label}</span>`;
}

/**
 * View report details
 */
async function viewReport(reportId) {
    if (!reportId) return;
    
    try {
        const response = await fetch(`../admin/api/getSingleReport.php?report_id=${reportId}`);
        const data = await response.json();
        
        if (data.status !== 'success') {
            Swal.fire('Error', data.message || 'Failed to load report', 'error');
            return;
        }
        
        const r = data.data;
        
        // Populate case details section
        const caseDetailsSection = document.getElementById('case-details');
        if (caseDetailsSection) {
            // Store report ID in data attribute
            caseDetailsSection.dataset.reportId = reportId;
            
            // Update name
            const caseName = caseDetailsSection.querySelector('#case-name');
            if (caseName) caseName.textContent = r.missing_name || 'Unknown';
            
            // Update photo
            const casePhoto = caseDetailsSection.querySelector('#case-photo');
            if (casePhoto) {
            casePhoto.src = r.photo ? '../' + r.photo : 'https://ui-avatars.com/api/?name=Missing&background=667eea&color=fff&size=300';
            }
            
            // Update paragraph elements with case info
            const ageGender = caseDetailsSection.querySelector('#case-age-gender');
            if (ageGender) ageGender.innerHTML = `<strong>Age/Gender:</strong> ${r.age || 'N/A'} / ${r.gender || 'N/A'}`;
            
            const lastSeen = caseDetailsSection.querySelector('#case-last-seen');
            if (lastSeen) lastSeen.innerHTML = `<strong>Last Seen:</strong> ${r.last_seen_location || 'Not specified'}`;
            
            const dateMissing = caseDetailsSection.querySelector('#case-date-missing');
            if (dateMissing) dateMissing.innerHTML = `<strong>Date Missing:</strong> ${formatDate(r.last_seen_datetime) || 'Not specified'}`;
            
            const reportedBy = caseDetailsSection.querySelector('#case-reported-by');
            if (reportedBy) reportedBy.innerHTML = `<strong>Reported By:</strong> ${r.reporter?.name || 'Unknown'}`;
            
            const contact = caseDetailsSection.querySelector('#case-contact');
            if (contact) contact.innerHTML = `<strong>Contact:</strong> ${r.reporter?.phone || 'Not provided'}`;
            
            // Update status dropdown
            const caseStatus = caseDetailsSection.querySelector('#case-status');
            if (caseStatus) {
                caseStatus.value = r.status || 'pending';
            }
            
            // Update remarks textarea
            const caseRemarks = caseDetailsSection.querySelector('#case-remarks');
            if (caseRemarks) {
                caseRemarks.value = r.officer_remarks || r.remarks || '';
            }
            
            // Scroll to case details
            caseDetailsSection.scrollIntoView({ behavior: 'smooth' });
        }
        
        // Show notification
        showNotification('info', `Loading report #R${reportId}...`);
        
    } catch (error) {
        console.error('Error viewing report:', error);
        Swal.fire('Error', 'Failed to load report details', 'error');
    }
}

/**
 * Save case update
 */
async function saveCaseUpdate() {
    const caseDetailsSection = document.getElementById('case-details');
    if (!caseDetailsSection) return;
    
    const reportId = caseDetailsSection.dataset.reportId;
    if (!reportId) {
        showNotification('warning', 'Please select a report first');
        return;
    }
    
    const status = document.getElementById('case-status')?.value || 'pending';
    const remarks = document.getElementById('case-remarks')?.value || '';
    
    try {
        const formData = new FormData();
        formData.append('report_id', reportId);
        formData.append('status', status);
        formData.append('remarks', remarks);
        
        const response = await fetch('../admin/api/updateCaseStatus.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.status === 'success') {
            showNotification('success', 'Case updated successfully!');
            loadReports(); // Refresh table to show updated status
        } else {
            Swal.fire('Error', data.message || 'Failed to update case', 'error');
        }
        
    } catch (error) {
        console.error('Error updating case:', error);
        showNotification('error', 'Failed to update case');
    }
}

/**
 * Notify reporter
 */
function notifyReporter() {
    const caseDetailsSection = document.getElementById('case-details');
    if (!caseDetailsSection) return;
    
    const reportId = caseDetailsSection.dataset.reportId;
    if (!reportId) {
        showNotification('warning', 'Please select a report first');
        return;
    }
    
    Swal.fire({
        title: 'Notify Reporter',
        html: `<p>Send notification to the person who reported case #R${reportId}</p>
               <input type="text" id="notification-message" class="form-control mt-3" placeholder="Enter notification message..." />`,
        icon: 'info',
        showCancelButton: true,
        confirmButtonText: 'Send',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#0d6efd',
        background: '#16161e',
        color: '#fff',
        preConfirm: () => {
            const message = document.getElementById('notification-message').value;
            if (!message) {
                Swal.showValidationMessage('Please enter a message');
                return false;
            }
            return message;
        }
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            // In a real app, this would send to the server
            showNotification('success', 'Notification sent to reporter!');
        }
    });
}

/**
 * Verify a report
 */
function verifyReport(reportId) {
    if (!reportId) return;
    
    Swal.fire({
        title: 'Verify Report?',
        html: `<p>This will change the report status from <strong>Pending</strong> to <strong>Verified</strong>.</p>
               <p class="text-muted small">Verified reports can be assigned to police officers.</p>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Verify',
        cancelButtonText: 'No, Cancel',
        confirmButtonColor: '#198754',
        background: '#16161e',
        color: '#fff'
    }).then(async (result) => {
        if (!result.isConfirmed) return;
        
        try {
            const formData = new FormData();
            formData.append('report_id', reportId);
            
            const response = await fetch('../admin/api/verifyReport.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.status === 'success') {
                Swal.fire('Verified!', 'Report has been verified successfully', 'success');
                loadReports(); // Refresh table
            } else {
                Swal.fire('Error', data.message || 'Failed to verify report', 'error');
            }
            
        } catch (error) {
            console.error('Error verifying report:', error);
            Swal.fire('Error', 'Failed to verify report', 'error');
        }
    });
}

/**
 * Assign police to report
 */
async function assignPoliceToReport(reportId) {
    if (!reportId) return;
    
    // First check if report is verified
    try {
        const checkResponse = await fetch(`../admin/api/getSingleReport.php?report_id=${reportId}`);
        const checkData = await checkResponse.json();
        
        if (checkData.status !== 'success') {
            Swal.fire('Error', 'Failed to check report status', 'error');
            return;
        }
        
        const report = checkData.data;
        
        if (report.status !== 'verified') {
            Swal.fire({
                title: 'Cannot Assign Police',
                html: `<p>Only <strong>verified</strong> reports can be assigned to police.</p>
                       <p class="text-muted small">Current status: ${getStatusBadge(report.status)}</p>`,
                icon: 'warning',
                background: '#16161e',
                color: '#fff'
            });
            return;
        }
        
        // Fetch available police officers
        const policeResponse = await fetch('../admin/api/getPolice.php');
        const policeData = await policeResponse.json();
        const policeList = Array.isArray(policeData) ? policeData : (policeData.data || []);
        const activePolice = policeList.filter(p => p.status === 'active');
        
        // Build police selection HTML
        let policeOptions = '<option value="">-- Select Police Officer --</option>';
        activePolice.forEach(p => {
            policeOptions += `<option value="${p.police_id}">${p.fullname} - ${p.station_name}</option>`;
        });
        
        if (activePolice.length === 0) {
            policeOptions = '<option value="">No active police officers available</option>';
        }
        
        // Show assignment dialog
        const { value: formValues } = await Swal.fire({
            title: 'Assign Police Officer',
            html: `
                <p class="text-muted small mb-3">Assign a police officer to investigate report #R${reportId}</p>
                <div class="mb-3">
                    <label class="form-label">Select Police Officer</label>
                    <select class="form-select" id="swal-police-select">
                        ${policeOptions}
                    </select>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Assign',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#0d6efd',
            background: '#16161e',
            color: '#fff',
            preConfirm: () => {
                const policeId = document.getElementById('swal-police-select').value;
                if (!policeId) {
                    Swal.showValidationMessage('Please select a police officer');
                    return false;
                }
                return policeId;
            }
        });
        
        if (formValues) {
            assignPolice(reportId, formValues);
        }
        
    } catch (error) {
        console.error('Error in assign flow:', error);
        Swal.fire('Error', 'An error occurred', 'error');
    }
}

/**
 * Execute police assignment
 */
async function assignPolice(reportId, policeId) {
    try {
        const formData = new FormData();
        formData.append('report_id', reportId);
        formData.append('police_id', policeId);
        
        const response = await fetch('../admin/api/assignPolice.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.status === 'success') {
            Swal.fire('Assigned!', 'Police officer has been assigned to the report', 'success');
            loadReports(); // Refresh table
        } else {
            Swal.fire('Error', data.message || 'Failed to assign police', 'error');
        }
        
    } catch (error) {
        console.error('Error assigning police:', error);
        Swal.fire('Error', 'Failed to assign police officer', 'error');
    }
}

/**
 * Download report as PDF
 */
function downloadReport(reportId) {
    if (!reportId) return;
    
    showNotification('info', `Checking PDF for report #R${reportId}...`);
    
    // First check if PDF exists
    fetch(`../admin/api/getReportPdf.php?report_id=${reportId}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success' && data.pdf_path) {
                // PDF exists, open it
                window.open(`../../${data.pdf_path}`, '_blank');
                showNotification('success', 'PDF opened successfully!');
            } else {
                // No PDF attached, show options
                Swal.fire({
                    title: 'No PDF Attached',
                    html: `<p>Report #R${reportId} doesn't have a PDF file attached.</p>
                           <p class="text-muted small">Would you like to generate a PDF report?</p>`,
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonText: 'Generate PDF',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#0d6efd',
                    background: '#16161e',
                    color: '#fff'
                }).then((result) => {
                    if (result.isConfirmed) {
                        generateReportPDF(reportId);
                    }
                });
            }
        })
        .catch(error => {
            console.error('Error checking PDF:', error);
            showNotification('error', 'Failed to check for PDF');
        });
}

/**
 * Generate a PDF report dynamically
 */
function generateReportPDF(reportId) {
    showNotification('info', 'Generating PDF report...');
    
    // Fetch report data and create a simple print view
    fetch(`../admin/api/getSingleReport.php?report_id=${reportId}`)
        .then(response => response.json())
        .then(data => {
            if (data.status !== 'success') {
                throw new Error('Failed to load report');
            }
            
            const report = data.data;
            
            // Create printable content
            const printContent = `
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Report #R${reportId}</title>
                    <style>
                        body { font-family: Arial, sans-serif; padding: 40px; max-width: 800px; margin: 0 auto; }
                        h1 { color: #4f46e5; border-bottom: 2px solid #4f46e5; padding-bottom: 10px; }
                        .field { margin: 15px 0; }
                        .label { font-weight: bold; color: #333; }
                        .value { color: #666; margin-left: 10px; }
                        .status { display: inline-block; padding: 5px 15px; border-radius: 20px; background: #e0e7ff; color: #4f46e5; }
                        .footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 12px; color: #999; }
                        @media print { body { padding: 20px; } }
                    </style>
                </head>
                <body>
                    <h1>Missing Person Report</h1>
                    <div class="field">
                        <span class="label">Report ID:</span>
                        <span class="value">#R${report.report_id}</span>
                    </div>
                    <div class="field">
                        <span class="label">Missing Person Name:</span>
                        <span class="value">${report.missing_name || 'Not specified'}</span>
                    </div>
                    <div class="field">
                        <span class="label">Age/Gender:</span>
                        <span class="value">${report.age || 'N/A'} / ${report.gender || 'N/A'}</span>
                    </div>
                    <div class="field">
                        <span class="label">Last Seen Location:</span>
                        <span class="value">${report.last_seen_location || 'Not specified'}</span>
                    </div>
                    <div class="field">
                        <span class="label">Last Seen Date:</span>
                        <span class="value">${formatDate(report.last_seen_datetime) || 'Not specified'}</span>
                    </div>
                    <div class="field">
                        <span class="label">Description:</span>
                        <span class="value">${report.description || 'No description provided'}</span>
                    </div>
                    <div class="field">
                        <span class="label">Status:</span>
                        <span class="status">${report.status ? report.status.toUpperCase() : 'UNKNOWN'}</span>
                    </div>
                    <div class="field">
                        <span class="label">Date Reported:</span>
                        <span class="value">${formatDate(report.created_at) || 'N/A'}</span>
                    </div>
                    <div class="field">
                        <span class="label">Reported By:</span>
                        <span class="value">${report.reporter?.name || 'Unknown'} (${report.reporter?.email || 'No email'})</span>
                    </div>
                    <div class="footer">
                        <p>Generated on: ${new Date().toLocaleString()}</p>
                        <p>HopeFinder Police Dashboard - Lost People Finding System</p>
                    </div>
                    <script>window.onload = function() { window.print(); }</script>
                </body>
                </html>
            `;
            
            // Open print window
            const printWindow = window.open('', '_blank');
            printWindow.document.write(printContent);
            printWindow.document.close();
            
        })
        .catch(error => {
            console.error('Error generating PDF:', error);
            showNotification('error', 'Failed to generate PDF');
        });
}

/**
 * Delete a report
 */
function deleteReport(reportId, reportName) {
    if (!reportId) return;
    
    Swal.fire({
        title: 'Delete Report?',
        html: `<p>This will permanently delete the report for <strong>${escapeHtml(reportName || 'Unknown')}</strong>.</p>
               <p class="text-danger small">This action cannot be undone!</p>
               <p class="text-muted small">All associated data will also be deleted.</p>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, Delete',
        cancelButtonText: 'No, Cancel',
        confirmButtonColor: '#dc3545',
        background: '#16161e',
        color: '#fff'
    }).then(async (result) => {
        if (!result.isConfirmed) return;
        
        try {
            const formData = new FormData();
            formData.append('report_id', reportId);
            
            const response = await fetch('../admin/api/deleteReport.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.status === 'success') {
                Swal.fire('Deleted!', 'Report has been deleted successfully', 'success');
                loadReports(); // Refresh table
            } else {
                Swal.fire('Error', data.message || 'Failed to delete report', 'error');
            }
            
        } catch (error) {
            console.error('Error deleting report:', error);
            Swal.fire('Error', 'Failed to delete report', 'error');
        }
    });
}

/**
 * Initialize report filters
 */
function initReportFilters() {
    const statusFilter = document.getElementById('reportStatusFilter');
    if (statusFilter) {
        statusFilter.addEventListener('change', filterReports);
    }
    
    const searchInput = document.getElementById('reportSearchInput');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(filterReports, 300));
    }
}

/**
 * Filter reports based on search and status
 */
function filterReports() {
    const statusFilter = document.getElementById('reportStatusFilter')?.value || '';
    const searchTerm = document.getElementById('reportSearchInput')?.value?.toLowerCase() || '';
    
    const rows = document.querySelectorAll('.report-row');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        const status = row.querySelector('.badge')?.textContent?.toLowerCase() || '';
        
        const matchesStatus = !statusFilter || status.includes(statusFilter.toLowerCase());
        const matchesSearch = !searchTerm || text.includes(searchTerm);
        
        row.style.display = matchesStatus && matchesSearch ? '' : 'none';
    });
}

/**
 * Refresh reports
 */
function refreshReports() {
    showNotification('info', 'Refreshing reports...');
    loadReports();
}

// ===========================================
// HELPER FUNCTIONS
// ===========================================

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
 * Format date for display
 */
function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

/**
 * Show notification using SweetAlert2
 */
function showNotification(type, message) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: type,
            title: type === 'success' ? 'Success!' : type === 'error' ? 'Error!' : 'Info',
            text: message,
            timer: 2000,
            showConfirmButton: false,
            background: '#16161e',
            color: '#fff'
        });
    } else {
        console.log(`${type}: ${message}`);
    }
}

/**
 * Debounce utility function
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func.apply(this, args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Export functions to window
window.loadReports = loadReports;
window.viewReport = viewReport;
window.verifyReport = verifyReport;
window.assignPoliceToReport = assignPoliceToReport;
window.deleteReport = deleteReport;
window.downloadReport = downloadReport;
window.generateReportPDF = generateReportPDF;
window.refreshReports = refreshReports;
window.filterReports = filterReports;

