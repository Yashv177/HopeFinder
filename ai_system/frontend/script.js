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
let locationCache = {};
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

// Start AI with GPS
// 🔥 GLOBAL
let gpsData = {};

startBtn.addEventListener('click', () => {
    if (!navigator.geolocation) {
        alert('GPS not supported');
        return;
    }

    startBtn.disabled = true;
    statusEl.textContent = 'Getting GPS...';

    navigator.geolocation.getCurrentPosition(
        async (position) => {

            // ✅ GLOBAL VALUE
            gpsData = {
                report_ids: selectedReports,
                latitude: position.coords.latitude,
                longitude: position.coords.longitude,
                accuracy: position.coords.accuracy
            };

            console.log("GPS:", gpsData);

            const res = await fetch(API_BASE + 'start_ai.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(gpsData)
            });

            const data = await res.json();

           if (data.status === 'success') {
    statusEl.textContent = 'Running';

    startBtn.disabled = true;
    stopBtn.disabled = false; // ⭐ यही missing है

    startStream();
}
        }
    );
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


// fetch location
async function getAddress(lat, lng) {
    const key = lat + "," + lng;

    // cache hit
    if (locationCache[key]) return locationCache[key];

    try {
        const res = await fetch(
            `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`,
            {
                headers: {
                    "User-Agent": "HopeFinderApp/1.0" // 🔥 IMPORTANT
                }
            }
        );

        if (!res.ok) throw new Error("API blocked");

        const data = await res.json();

        const address = data.display_name || "Unknown Location";

        locationCache[key] = address; // save cache

        return address;

    } catch (err) {
        console.error("Location error:", err);
        return "Location unavailable";
    }
}

// Poll detections
// ---------------- DETECTIONS ----------------
async function pollDetections() {
    try {
        const res = await fetch(API_BASE + 'get_detections.php?limit=10');
        const data = await readJson(res);
        const detections = Array.isArray(data) ? data : [];

        detectionsCount.textContent = detections.length;

        let rows = "";

        for (const det of detections) {

            // 🔥 LOCATION NAME
            let address = "Loading...";

            if (det.latitude && det.longitude) {
                address = await getAddress(det.latitude, det.longitude);
            }

            // 🔥 CONFIDENCE UI
            let badge = "bg-secondary";
            if (det.confidence >= 0.85) badge = "bg-success";
            else if (det.confidence >= 0.60) badge = "bg-warning";

            rows += `
                <tr class="align-middle text-center">
                    <td>${det.id}</td>

                    <td>
                        <img src="${assetUrl(det.image_path)}"
                        style="width:80px;height:60px;object-fit:cover;border-radius:6px">
                    </td>

                    <td><strong>${escapeHtml(det.report_id)}</strong></td>

                    <td>${address}</td>

                    <td>
                        ${new Date(det.timestamp).toLocaleString()}
                    </td>
                </tr>
            `;
        }

        detectionsList.innerHTML = rows || `
            <tr>
                <td colspan="6" class="text-center text-muted">
                    No detections yet
                </td>
            </tr>
        `;

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


startBtn.disabled = true;
stopBtn.disabled = true;