/**
 * Add Found Person Functionality
 * Saves found person records and triggers AI matching
 */

document.addEventListener('DOMContentLoaded', function() {
    initAddFoundPerson();
    // Load found persons table
    loadFoundPersons();
    // Load AI matches
    loadAIMatches();
});

/**
 * Show new found person form
 */
function showNewFoundForm() {
    const form = document.getElementById('addFoundPersonForm');
    if (form) {
        form.reset();
        const preview = document.getElementById('foundPhotoPreview');
        if (preview) {
            preview.style.display = 'none';
            preview.src = '';
        }
        form.scrollIntoView({ behavior: 'smooth' });
    }
}

/**
 * Initialize add found person functionality
 */
function initAddFoundPerson() {
    const form = document.getElementById('addFoundPersonForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            saveFoundPerson();
        });
    }
    
    // Initialize photo preview
    const photoInput = document.getElementById('foundPhoto');
    if (photoInput) {
        photoInput.addEventListener('change', function(e) {
            previewImage(e.target, 'foundPhotoPreview');
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
 * Save Found Person to database
 */
function saveFoundPerson() {
    const form = document.getElementById('addFoundPersonForm');
    if (!form) {
        showNotification('error', 'Form not found');
        return;
    }
    
    const formData = new FormData(form);
    
    // Add current date if not set
    if (!formData.get('date_found')) {
        formData.set('date_found', new Date().toISOString().split('T')[0]);
    }
    
    // Show loading
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Saving...';
    submitBtn.disabled = true;
    
    // Send to API
    fetch('../admin/api/addFoundPerson.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            Swal.fire({
                icon: 'success',
                title: 'Found Person Saved!',
                html: `<p>Record added successfully (ID: #F${data.found_id})</p>
                       <p class="text-info">AI is now searching for potential matches...</p>`,
                confirmButtonText: 'View Matches',
                cancelButtonText: 'Close',
                confirmButtonColor: '#0d6efd',
                background: '#16161e',
                color: '#fff'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Navigate to AI suggestions
                    window.location.href = '#ai-suggestions';
                } else {
                    form.reset();
                    // Clear photo preview
                    const preview = document.getElementById('foundPhotoPreview');
                    if (preview) {
                        preview.style.display = 'none';
                        preview.src = '';
                    }
                }
            });
            
            // Refresh found persons table
            loadFoundPersons();
            
            // Trigger AI matching
            if (data.found_id) {
                triggerAIMatching(data.found_id);
            }
        } else {
            showNotification('error', data.message || 'Failed to save record');
        }
    })
    .catch(error => {
        console.error('Error saving found person:', error);
        showNotification('error', 'An error occurred while saving');
    })
    .finally(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
}

/**
 * Trigger AI matching for a newly added found person
 */
function triggerAIMatching(foundId) {
    fetch('../admin/api/aiMatch.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'found_id=' + foundId
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            console.log('AI matching triggered for found person #' + foundId);
            if (data.matches_found > 0) {
                showNotification('success', `${data.matches_found} potential matches found!`);
                // Refresh AI matches table
                loadAIMatches();
            } else {
                showNotification('info', 'No matches found for this person');
            }
        } else {
            showNotification('error', data.message || 'AI matching failed');
        }
    })
    .catch(error => {
        console.error('AI matching error:', error);
        showNotification('error', 'An error occurred during AI matching');
    });
}

/**
 * Load AI matches into the table
 */
function loadAIMatches() {
    const tableBody = document.querySelector('#ai-suggestions tbody');
    if (!tableBody) return;
    
    tableBody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Loading matches...</td></tr>';
    
    fetch('../admin/api/getAIMatches.php')
    .then(response => response.json())
    .then(data => {
        if (data.status !== 'success' || !data.data || data.data.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No AI match suggestions yet</td></tr>';
            return;
        }
        
        let html = '';
        data.data.forEach(match => {
            const matchPercent = match.match_percent || 0;
            const percentClass = matchPercent >= 80 ? 'text-success' : (matchPercent >= 60 ? 'text-warning' : 'text-danger');
            
            html += `
                <tr>
                    <td><strong>#M${match.match_id}</strong></td>
                    <td>${escapeHtml(match.missing_name || 'Unknown')} <small class="text-muted">(#R${match.report_id})</small></td>
                    <td>#F${match.found_id} - ${escapeHtml(match.found_name || 'Unknown')}</td>
                    <td><strong class="${percentClass}">${matchPercent}%</strong></td>
                    <td><span class="badge bg-${match.verified_by === 'AI' ? 'info' : 'primary'}">${match.verified_by || 'AI'}</span></td>
                    <td>
                        <div class="inline-actions">
                            <i class="bi bi-check2-square text-success" title="Approve Match" 
                               onclick="approveMatch(${match.match_id})"></i>
                            <i class="bi bi-x-square text-danger" title="Reject Match" 
                               onclick="rejectMatch(${match.match_id})"></i>
                            <i class="bi bi-eye text-info" title="View Details" 
                               onclick="viewMatchDetails(${match.match_id})"></i>
                        </div>
                    </td>
                </tr>
            `;
        });
        
        tableBody.innerHTML = html;
    })
    .catch(error => {
        console.error('Error loading AI matches:', error);
        tableBody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Error loading matches</td></tr>';
    });
}

/**
 * Approve a match
 */
function approveMatch(matchId) {
    if (!matchId) return;
    
    Swal.fire({
        title: 'Approve Match?',
        html: `<p>This will confirm the match between the missing person and found person.</p>
               <p class="text-success">The reporter will be notified.</p>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Approve',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#198754',
        background: '#16161e',
        color: '#fff'
    }).then((result) => {
        if (!result.isConfirmed) return;
        
        updateMatchStatus(matchId, 'approved');
    });
}

/**
 * Reject a match
 */
function rejectMatch(matchId) {
    if (!matchId) return;
    
    Swal.fire({
        title: 'Reject Match?',
        html: `<p>This will reject the match suggestion.</p>
               <p class="text-danger">The match will be removed from suggestions.</p>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, Reject',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#dc3545',
        background: '#16161e',
        color: '#fff'
    }).then((result) => {
        if (!result.isConfirmed) return;
        
        updateMatchStatus(matchId, 'rejected');
    });
}

/**
 * Update match status
 */
function updateMatchStatus(matchId, status) {
    fetch('../admin/api/updateMatchStatus.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `match_id=${matchId}&status=${status}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            showNotification('success', `Match ${status} successfully`);
            loadAIMatches();
        } else {
            showNotification('error', data.message || 'Failed to update match');
        }
    })
    .catch(error => {
        console.error('Error updating match:', error);
        showNotification('error', 'An error occurred');
    });
}

/**
 * View match details
 */
function viewMatchDetails(matchId) {
    fetch(`../admin/api/getAIMatches.php?match_id=${matchId}`)
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success' && data.data && data.data.length > 0) {
            const match = data.data[0];
            
            const html = `
                <div class="row">
                    <div class="col-md-6 text-center">
                        <h6 class="text-muted">Missing Person</h6>
                        <img src="${match.missing_photo || 'https://via.placeholder.com/150'}" 
                             class="img-fluid rounded mb-2" style="max-width:150px;" />
                        <p><strong>${escapeHtml(match.missing_name || 'Unknown')}</strong></p>
                        <p>Report #R${match.report_id}</p>
                    </div>
                    <div class="col-md-6 text-center">
                        <h6 class="text-muted">Found Person</h6>
                        <img src="${match.found_photo || 'https://via.placeholder.com/150'}" 
                             class="img-fluid rounded mb-2" style="max-width:150px;" />
                        <p><strong>${escapeHtml(match.found_name || 'Unknown')}</strong></p>
                        <p>Found #F${match.found_id}</p>
                    </div>
                </div>
                <hr/>
                <div class="text-center">
                    <h4 class="${match.match_percent >= 80 ? 'text-success' : 'text-warning'}">
                        ${match.match_percent}% Match
                    </h4>
                    <p class="text-muted">Suggested by: ${match.verified_by || 'AI'}</p>
                    <p>Status: <span class="badge bg-${getMatchStatusColor(match.status)}">${match.status || 'pending'}</span></p>
                </div>
            `;
            
            Swal.fire({
                title: 'Match Details',
                html: html,
                confirmButtonText: 'Close',
                background: '#16161e',
                color: '#fff',
                width: '500px'
            });
        } else {
            showNotification('error', 'Failed to load match details');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('error', 'An error occurred');
    });
}

/**
 * Get status badge color for matches
 */
function getMatchStatusColor(status) {
    const colors = {
        'pending': 'warning',
        'approved': 'success',
        'rejected': 'danger'
    };
    return colors[status] || 'secondary';
}

/**
 * View found person details
 */
function viewFoundPerson(foundId) {
    if (!foundId) return;
    
    fetch(`../admin/api/getFoundPerson.php?found_id=${foundId}`)
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            const person = data.data;
            
            const html = `
                <div class="row">
                    <div class="col-md-4 text-center">
                        <img src="${person.photo_path ? '../' + person.photo_path : 'https://ui-avatars.com/api/?name=Found&background=28a745&color=fff&size=200'}"
                             class="img-fluid rounded" style="max-width:200px;" />
                    </div>
                    <div class="col-md-8">
                        <h6>${person.name || 'Unknown'}</h6>
                        <p><strong>Age:</strong> ${person.age || 'N/A'} | <strong>Gender:</strong> ${person.gender || 'N/A'}</p>
                        <p><strong>Found Location:</strong> ${person.found_location || 'Not specified'}</p>
                        <p><strong>Date Found:</strong> ${formatDate(person.date_found)}</p>
                        <p><strong>Remarks:</strong> ${person.remarks || 'None'}</p>
                        <p><strong>Status:</strong> <span class="badge bg-${getStatusColor(person.status)}">${person.status || 'Unknown'}</span></p>
                    </div>
                </div>
            `;
            
            Swal.fire({
                title: 'Found Person Details',
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
 * Delete found person
 */
function deleteFoundPerson(foundId, personName) {
    if (!foundId) return;
    
    Swal.fire({
        title: 'Delete Found Person?',
        html: `<p>This will permanently delete the record for <strong>${personName || 'this person'}</strong>.</p>
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
        
        fetch('../admin/api/deleteFoundPerson.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'found_id=' + foundId
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showNotification('success', 'Record deleted successfully');
                // Refresh table
                loadFoundPersons();
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
 * Load all found persons into table
 */
function loadFoundPersons() {
    const tableBody = document.getElementById('foundPersonsTableBody');
    if (!tableBody) return;
    
    tableBody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">Loading...</td></tr>';
    
    fetch('../admin/api/getFoundPersons.php')
    .then(response => response.json())
    .then(data => {
        if (data.status !== 'success' || !data.data || data.data.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No found persons recorded yet</td></tr>';
            return;
        }
        
        let html = '';
        data.data.forEach(person => {
            html += `
                <tr>
                    <td><strong>#F${person.found_id}</strong></td>
                    <td>${escapeHtml(person.name || 'Unknown')}</td>
                    <td>${person.age || 'N/A'} / ${person.gender || 'N/A'}</td>
                    <td>${escapeHtml(person.found_location || 'N/A')}</td>
                    <td>${formatDate(person.date_found)}</td>
                    <td><span class="badge bg-${getStatusColor(person.status)}">${person.status || 'Pending'}</span></td>
                    <td>
                        <div class="inline-actions">
                            <i class="bi bi-eye text-info" title="View Details" 
                               onclick="viewFoundPerson(${person.found_id})"></i>
                            <i class="bi bi-cpu text-warning" title="Check AI Matches" 
                               onclick="triggerAIMatching(${person.found_id})"></i>
                            <i class="bi bi-trash text-danger" title="Delete" 
                               onclick="deleteFoundPerson(${person.found_id}, '${escapeHtml(person.name)}')"></i>
                        </div>
                    </td>
                </tr>
            `;
        });
        
        tableBody.innerHTML = html;
    })
    .catch(error => {
        console.error('Error loading found persons:', error);
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
        'matched': 'success',
        'closed': 'secondary'
    };
    return colors[status] || 'secondary';
}

/**
 * Refresh AI matches
 */
function refreshMatches() {
    loadAIMatches();
    showNotification('success', 'Matches refreshed');
}

/**
 * Export matches to CSV
 */
function exportMatchesCSV() {
    fetch('../admin/api/getAIMatches.php')
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success' && data.data && data.data.length > 0) {
            let csv = 'Match ID,Missing Person,Found Person,Match %,Suggested By,Status,Date\n';
            data.data.forEach(match => {
                csv += `${match.match_id},"${match.missing_name || 'Unknown'}","${match.found_name || 'Unknown'}",${match.match_percent}%,${match.verified_by || 'AI'},${match.status || 'pending'},"${match.created_at || ''}"\n`;
            });
            
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'ai_matches_' + new Date().toISOString().split('T')[0] + '.csv';
            a.click();
            window.URL.revokeObjectURL(url);
        } else {
            showNotification('info', 'No matches to export');
        }
    })
    .catch(error => {
        console.error('Error exporting matches:', error);
        showNotification('error', 'Failed to export matches');
    });
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
window.saveFoundPerson = saveFoundPerson;
window.viewFoundPerson = viewFoundPerson;
window.deleteFoundPerson = deleteFoundPerson;
window.loadFoundPersons = loadFoundPersons;
window.triggerAIMatching = triggerAIMatching;
window.loadAIMatches = loadAIMatches;
window.refreshMatches = refreshMatches;
window.exportMatchesCSV = exportMatchesCSV;
window.approveMatch = approveMatch;
window.rejectMatch = rejectMatch;
window.viewMatchDetails = viewMatchDetails;

