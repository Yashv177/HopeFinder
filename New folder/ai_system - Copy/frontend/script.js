// HopeFinder AI Frontend - Production Ready
// API Base (relative)
// Dynamic API base for XAMPP
let API_BASE;
if (window.location.hostname === '127.0.0.1' || window.location.hostname === 'localhost') {
    API_BASE = '/HopeFinder/ai_system/backend/api/';
} else {
    API_BASE = '../backend/api/';
}
console.log('API_BASE:', API_BASE);
const STREAM_URL = 'http://127.0.0.1:5001/video_feed';
const APP_ROOT = '../../';
const POLL_INTERVAL = 2000; // 2s

let selectedReports = [];
let pollInterval;
let statusCheckInterval;
let seenDetections = new Set();

// DOM
const missingList = document.getElementById('missing-list');
const startBtn = document.getElementById('start-ai');
const stopBtn = document.getElementById('stop-ai');
const statusEl = document.getElementById('status');
const videoStream = document.getElementById('video-stream');
const streamStatus = document.getElementById('stream-status');
const detectionsList = document.getElementById('detections-list');
const detectionsCount = document.getElementById('detections-count');
const selectAllBtn = document.getElementById('select-all');

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, ch => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    }[ch]));
}

function assetUrl(path) {
    if (!path) return '';
    if (/^https?:\/\//i.test(path)) return path;
    return APP_ROOT + String(path).replace(/^\/+/, '');
}

async function readJson(res) {
    const data = await res.json();
    if (!res.ok) {
        throw new Error(data.message || data.error || `Request failed (${res.status})`);
    }
    return data;
}

function formatConfidence(value) {
    const confidence = Number(value);
    return Number.isFinite(confidence) ? `${confidence.toFixed(1)}%` : '0.0%';
}

// Toast notification + sound
function showToast(message, type = 'success') {
  const toastContainer = document.getElementById('toast-container');
  const toast = document.createElement('div');
  toast.className = `p-4 rounded-xl shadow-2xl border-l-4 bg-white max-w-sm mx-auto transform transition-all duration-300 ${
    type === 'found' ? 'border-green-500 bg-green-50 text-green-900' :
    type === 'alert' ? 'border-orange-500 bg-orange-50 text-orange-900' :
    'border-blue-500 bg-blue-50 text-blue-900'
  }`;
  toast.innerHTML = `
    <div class="flex items-center gap-3">
      <i class="bi bi-bell-fill text-xl flex-shrink-0"></i>
      <div class="flex-1">
        <strong class="block font-semibold text-lg">MATCH DETECTED!</strong>
        <div class="text-sm">${message}</div>
      </div>
    </div>
  `;
  toastContainer.appendChild(toast);
  
  // Animate in
  requestAnimationFrame(() => toast.classList.add('translate-x-0', 'opacity-100'));
  
  // Auto remove
  setTimeout(() => {
    toast.style.animation = 'slideOut 0.3s ease-out forwards';
    setTimeout(() => toast.remove(), 300);
  }, 6000);
  
  // Play sound
  const beepSound = document.getElementById('beep-sound');
  beepSound?.play().catch(() => {}); // Silent fail
}

// Load missing persons
async function loadMissing() {
    try {
        const res = await fetch(API_BASE + 'get_missing.php');
        const data = await readJson(res);
        const missing = Array.isArray(data) ? data : [];
        
        missingList.innerHTML = missing.map(person => {
            const name = escapeHtml(person.name);
            const photo = person.photo ? assetUrl(person.photo) : '';
            return `
            <label class="list-group-item d-flex justify-content-between align-items-center">
                <div>
                    <strong>${name}</strong>
                    ${photo ? `<img src="${photo}" class="img-thumbnail ms-2" style="width:40px;" alt="${name}">` : ''}
                </div>
                <input type="checkbox" value="${person.id}" data-name="${name}">
            </label>
        `;
        }).join('') || '<p class="text-muted text-center mb-0">No active missing reports</p>';
        
        // Events
        missingList.querySelectorAll('input[type=checkbox]').forEach(cb => {
            cb.addEventListener('change', updateSelection);
        });
        
        updateSelectAll();
    } catch (e) {
        console.error('Load missing error:', e);
        missingList.innerHTML = '<p class="text-danger text-center mb-0">Could not load missing reports</p>';
    }
}

// Update selection
function updateSelection() {
    selectedReports = Array.from(missingList.querySelectorAll('input:checked'))
        .map(cb => Number(cb.value))
        .filter(Boolean);
    startBtn.disabled = selectedReports.length === 0;
    updateSelectAll();
}

function updateSelectAll() {
    const allCbs = missingList.querySelectorAll('input[type=checkbox]');
    const boxes = Array.from(allCbs);
    const checked = boxes.length > 0 && boxes.every(cb => cb.checked);
    const checkboxes = boxes.some(cb => cb.checked);
    
    if (checked) {
        selectAllBtn.textContent = 'Select None';
    } else if (checkboxes) {
        selectAllBtn.textContent = `Select All (${selectedReports.length})`;
    } else {
        selectAllBtn.textContent = 'Select All';
    }
}

selectAllBtn.addEventListener('click', () => {
    const allCbs = missingList.querySelectorAll('input[type=checkbox]');
    const isAllSelected = Array.from(allCbs).every(cb => cb.checked);
    
    allCbs.forEach(cb => cb.checked = !isAllSelected);
    updateSelection();
});

// Start AI
startBtn.addEventListener('click', async () => {
    try {
        startBtn.disabled = true;
        stopBtn.disabled = false;
        statusEl.textContent = 'Starting...';
        statusEl.className = 'text-warning';
        
        const res = await fetch(API_BASE + 'start_ai.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({report_ids: selectedReports})
        });
        const data = await readJson(res);
        
        if (data.status === 'success') {
            statusEl.textContent = 'Running';
            statusEl.className = 'text-success';
            startStream();
        } else {
            throw new Error(data.message);
        }
    } catch (e) {
        alert('Start failed: ' + e.message);
        resetButtons();
    }
});

// Stop AI
stopBtn.addEventListener('click', async () => {
    try {
        const res = await fetch(API_BASE + 'stop_ai.php', {method: 'POST'});
        const data = await readJson(res);
        
        if (data.status === 'success') {
            statusEl.textContent = 'Stopped';
            statusEl.className = 'text-danger';
            stopStream();
        }
    } catch (e) {
        console.error('Stop error:', e);
    }
    resetButtons();
});

function resetButtons() {
    startBtn.disabled = selectedReports.length === 0;
    stopBtn.disabled = true;
    statusEl.textContent = 'Offline';
    statusEl.className = 'text-secondary';
}

function startStream() {
    videoStream.src = STREAM_URL + '?t=' + Date.now();
    videoStream.style.display = 'block';
    streamStatus.style.display = 'none';
}

function stopStream() {
    videoStream.src = '';
    videoStream.style.display = 'none';
    streamStatus.style.display = 'block';
}

// Poll detections
async function pollDetections() {
    try {
        const res = await fetch(API_BASE + 'get_detections.php?limit=10');
        const data = await readJson(res);
        const detections = Array.isArray(data) ? data : [];
        
        // Check for new high-confidence matches
        detections.forEach(det => {
            if (!seenDetections.has(det.id) && (det.confidence_level === 'found' || det.confidence_level === 'alert')) {
                const level = det.confidence_level === 'found' ? 'FOUND' : 'ALERT';
                showToast(
                    `Report #${det.report_id} - ${det.confidence_pct || formatConfidence(det.confidence)} (${level})`, 
                    det.confidence_level || 'success'
                );
                seenDetections.add(det.id);
            }
        });
        
        detectionsCount.textContent = detections.length;
        detectionsList.innerHTML = detections.map(det => {
            const confLevel = det.confidence_level;
            const badgeClass = confLevel === 'found' ? 'bg-success border-success' :
                              confLevel === 'alert' ? 'bg-warning border-warning' :
                              'bg-primary';
            const name = `Report #${escapeHtml(det.report_id)}`;
            return `
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="card h-100 detection-card ${confLevel === 'found' || confLevel === 'alert' ? 'animate-pulse border-start border-3' : ''}" style="border-left-color: var(--bs-${badgeClass.split('-')[1]});">
                        <img src="${assetUrl(det.image_path)}" class="card-img-top" style="height: 150px; object-fit: cover;" alt="${name}">
                        <div class="card-body">
                            <h6 class="card-title">${name}</h6>
                            <div class="d-flex justify-content-between">
                                <span class="badge ${badgeClass} fw-bold">${formatConfidence(det.confidence)}</span>
                                <small class="text-muted">${new Date(det.timestamp).toLocaleString()}</small>
                            </div>
                            ${confLevel ? `<div class="mt-1"><small class="badge bg-secondary">${confLevel.toUpperCase()}</small></div>` : ''}
                        </div>
                    </div>
                </div>
            `;
        }).join('') || '<div class="col-12"><p class="text-muted text-center">No detections yet</p></div>';
        
        // Cleanup old seen (keep last 100)
        if (seenDetections.size > 100) {
            seenDetections = new Set([...seenDetections].slice(-100));
        }
    } catch (e) {
        console.error('Poll error:', e);
    }
}

// Status check
async function checkStatus() {
    try {
        const res = await fetch('http://127.0.0.1:5001/health');
        const data = await res.json();
        if (data.status === 'ok') {
            statusEl.textContent = data.running ? 'Running' : 'Ready';
            statusEl.className = data.running ? 'text-success' : 'text-info';
        }
    } catch {
        statusEl.textContent = 'Offline';
        statusEl.className = 'text-secondary';
    }
}

// Stream status
videoStream.addEventListener('load', () => {
    streamStatus.style.display = 'none';
});
videoStream.addEventListener('error', () => {
    if (!videoStream.src.includes(STREAM_URL)) return;
    streamStatus.style.display = 'block';
});

// Init
loadMissing();
pollInterval = setInterval(pollDetections, POLL_INTERVAL);
statusCheckInterval = setInterval(checkStatus, 3000);
checkStatus();
