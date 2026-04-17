// AI Integration for Dashboard2 - Optimized with toasts, colors
// Uses consistent showToast, get_matches.php

let aiTargetCount = 0;

// Toggle all
function toggleAllTargets() {
  const master = document.getElementById('selectAllTargets');
  document.querySelectorAll('.target-select').forEach(cb => cb.checked = master.checked);
}

// Update AI Targets
async function updateAITargets() {
  const ids = Array.from(document.querySelectorAll('#reportsTable .target-select:checked')).map(cb => parseInt(cb.dataset.reportId)).filter(Boolean);
  if (!ids.length) return showToast('Select reports', 'warning');
  
  try {
    const res = await fetch('http://127.0.0.1:5001/update_targets', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({report_ids: ids})
    });
    const data = await res.json();
    if (data.status === 'success') {
      aiTargetCount = data.updated;
      updateAIStatusDisplay();
      showToast(`${data.updated} targets to AI ✓`, 'success');
    } else {
      showToast(data.message || 'AI error', 'error');
    }
  } catch {
    showToast('Start ai_system/run_ai.bat', 'error');
  }
}

// Match actions
async function approveMatch(id) {
  try {
    const res = await fetch('../admin/api/updateMatchStatus.php', {
      method: 'POST',
      body: `match_id=${id}&status=confirmed`
    });
    const data = await res.json();
    if (data.status === 'success') {
      showToast('Approved ✓', 'success');
      loadMatches();
    }
  } catch {
    showToast('Error', 'error');
  }
}

async function rejectMatch(id) {
  try {
    const res = await fetch('../admin/api/updateMatchStatus.php', {
      method: 'POST',
      body: `match_id=${id}&status=rejected`
    });
    const data = await res.json();
    if (data.status === 'success') {
      showToast('Rejected', 'info');
      loadMatches();
    }
  } catch {
    showToast('Error', 'error');
  }
}

// Load matches with colors
async function loadMatches() {
  try {
    const res = await fetch('../admin/api/get_matches.php');
    const data = await res.json();
    const tbody = document.getElementById('matchesTable');
    if (data.status === 'success') {
      tbody.innerHTML = data.data.map(m => {
        const perc = m.match_percentage || 0;
        const color = perc >= 80 ? 'success' : perc >= 60 ? 'warning' : 'danger';
        return `
          <tr>
            <td>#M${m.match_id}</td>
            <td>${m.missing_name}</td>
            <td>#F${m.found_id}</td>
            <td><span class="badge bg-${color}">${perc.toFixed(1)}%</span></td>
            <td>${m.status}</td>
            <td>
              <i class="bi bi-check-circle text-success me-2" onclick="approveMatch(${m.match_id})" title="Approve"></i>
              <i class="bi bi-x-circle text-danger" onclick="rejectMatch(${m.match_id})" title="Reject"></i>
            </td>
          </tr>
        `;
      }).join('') || '<tr><td colspan="6" class="text-center text-muted">No matches</td></tr>';
    }
  } catch (e) {
    console.error(e);
  }
}

// Status
function updateAIStatusDisplay() {
  let el = document.getElementById('aiStatus');
  if (!el) {
    const header = document.querySelector('#manage-reports h4');
    el = document.createElement('span');
    el.id = 'aiStatus';
    header.appendChild(el);
  }
  el.textContent = `AI Targets: ${aiTargetCount}`;
  el.className = aiTargetCount > 0 ? 'badge bg-success ms-2' : 'badge bg-secondary ms-2';
}

// Toast (global)
let toastContainer;
function showToast(msg, type = 'info') {
  if (!toastContainer) {
    toastContainer = document.createElement('div');
    toastContainer.id = 'toastContainer';
    toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
    document.body.appendChild(toastContainer);
  }
  const toast = document.createElement('div');
  toast.className = `toast text-bg-${type} border-0`;
  toast.innerHTML = `
    <div class="d-flex">
      <div class="toast-body">${msg}</div>
      <button class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  `;
  toastContainer.appendChild(toast);
  new bootstrap.Toast(toast).show();
  toast.addEventListener('hidden.bs.toast', () => toast.remove());
}

// Init polling
document.addEventListener('DOMContentLoaded', () => {
  setInterval(loadMatches, 5000);
  loadMatches();
});
