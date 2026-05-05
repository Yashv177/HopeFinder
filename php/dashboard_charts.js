// HopeFinder Admin Dashboard - Charts & Real-time Data
// Auto-refresh every 5 seconds

const API_BASE = 'admin/api/';
const REFRESH_INTERVAL = 5000;

let charts = {};

// Show loading spinner
function showLoading(id) {
  document.getElementById(id)?.classList.remove('d-none');
}

// Hide loading spinner
function hideLoading(id) {
  document.getElementById(id)?.classList.add('d-none');
}

// Animate counter
function animateCounter(element, target) {
  const start = parseInt(element.textContent) || 0;
  const duration = 1000;
  const step = (target - start) / (duration / 16);
  
  let current = start;
  const timer = setInterval(() => {
    current += step;
    if (step > 0 && current > target) current = target;
    if (step < 0 && current < target) current = target;
    element.textContent = Math.floor(current).toLocaleString();
    
    if (Math.abs(target - current) < Math.abs(step)) {
      clearInterval(timer);
      element.textContent = target.toLocaleString();
    }
  }, 16);
}

// Fetch dashboard stats and update cards
async function fetchStats() {
  showLoading('cardsLoading');
  
  try {
    const response = await fetch(API_BASE + 'getDashboardStats.php');
    const data = await response.json();
    
    if (data.status === 'success') {
      animateCounter(document.getElementById('totalMissing'), data.total_missing);
      animateCounter(document.getElementById('totalFound'), data.total_found);
      animateCounter(document.getElementById('todayDetections'), data.today_detections);
      animateCounter(document.getElementById('aiSuccess'), data.ai_success);
    }
  } catch (error) {
    console.error('Stats error:', error);
  } finally {
    hideLoading('cardsLoading');
  }
}

// Create line chart for monthly data
function createMonthlyChart(canvasId, missingData, foundData) {
  const ctx = document.getElementById(canvasId).getContext('2d');
  
  // Merge months
  const months = [...new Set([...missingData.map(d => d.month), ...foundData.map(d => d.month)])].sort();
  
  const missingLabels = months.map(m => missingData.find(d => d.month === m)?.missing_count || 0);
  const foundLabels = months.map(m => foundData.find(d => d.month === m)?.found_count || 0);
  
  charts.monthly = new Chart(ctx, {
    type: 'line',
    data: {
      labels: months,
      datasets: [
        {
          label: 'Missing Reports',
          data: missingLabels,
          borderColor: '#667eea',
          backgroundColor: 'rgba(102, 126, 234, 0.1)',
          tension: 0.4,
          fill: true
        },
        {
          label: 'Found Cases', 
          data: foundLabels,
          borderColor: '#43e97b',
          backgroundColor: 'rgba(67, 233, 123, 0.1)',
          tension: 0.4,
          fill: true
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { position: 'top' }
      },
      scales: {
        y: { beginAtZero: true }
      }
    }
  });
}

// Daily detections line chart
function createDetectionChart(canvasId, data) {
  const ctx = document.getElementById(canvasId).getContext('2d');
  
  charts.detection = new Chart(ctx, {
    type: 'line',
    data: {
      labels: data.map(d => new Date(d.day).toLocaleDateString()),
      datasets: [{
        label: 'AI Detections',
        data: data.map(d => d.detection_count),
        borderColor: '#f093fb',
        backgroundColor: 'rgba(240, 147, 251, 0.2)',
        tension: 0.4,
        fill: true
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { position: 'top' } },
      scales: { y: { beginAtZero: true } }
    }
  });
}

// Location pie chart
function createLocationChart(canvasId, data) {
  const ctx = document.getElementById(canvasId).getContext('2d');
  
  const labels = data.map(d => d.last_seen_location);
  const values = data.map(d => parseInt(d.case_count));
  const colors = ['#667eea', '#764ba2', '#f093fb', '#f5576c', '#4facfe', '#00f2fe', '#43e97b', '#38f9d7', '#fa709a', '#fee140'];
  
  charts.location = new Chart(ctx, {
    type: 'pie',
    data: {
      labels: labels,
      datasets: [{
        data: values,
        backgroundColor: colors.slice(0, data.length)
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { position: 'right' }
      }
    }
  });
}

// Match status doughnut
function createMatchChart(canvasId, data) {
  const ctx = document.getElementById(canvasId).getContext('2d');
  
  const labels = data.map(d => d.status);
  const values = data.map(d => parseInt(d.count));
  const colors = {
    'pending': '#f093fb',
    'approved': '#43e97b', 
    'confirmed': '#43e97b',
    'rejected': '#f5576c',
    'default': '#667eea'
  };
  
  charts.match = new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: labels,
      datasets: [{
        data: values,
        backgroundColor: labels.map(status => colors[status] || colors.default)
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { position: 'bottom' }
      }
    }
  });
}

// Load all charts
async function loadAllCharts() {
  try {
    // Monthly data
    const monthlyRes = await fetch(API_BASE + 'get_chart_monthly.php');
    const monthlyData = await monthlyRes.json();
    if (monthlyData.status === 'success') {
      showLoading('monthlyLoading');
      createMonthlyChart('monthlyChart', monthlyData.data.missing || [], monthlyData.data.found || []);
      hideLoading('monthlyLoading');
    }

    // Detections
    const detectionRes = await fetch(API_BASE + 'get_detection_daily.php');
    const detectionData = await detectionRes.json();
    if (detectionData.status === 'success') {
      showLoading('detectionLoading');
      createDetectionChart('detectionChart', detectionData.data);
      hideLoading('detectionLoading');
    }

    // Locations
    const locationRes = await fetch(API_BASE + 'get_location_data.php');
    const locationData = await locationRes.json();
    if (locationData.status === 'success') {
      showLoading('locationLoading');
      createLocationChart('locationChart', locationData.data);
      hideLoading('locationLoading');
    }

    // Matches
    const matchRes = await fetch(API_BASE + 'get_match_status.php');
    const matchData = await matchRes.json();
    if (matchData.status === 'success') {
      showLoading('matchLoading');
      createMatchChart('matchChart', matchData.data);
      hideLoading('matchLoading');
    }
  } catch (error) {
    console.error('Charts load error:', error);
  }
}

// Refresh countdown
function updateCountdown() {
  let count = 5;
  const countdownEl = document.getElementById('refreshCountdown');
  const interval = setInterval(() => {
    count--;
    if (countdownEl) countdownEl.textContent = count;
    if (count <= 0) {
      clearInterval(interval);
      refreshDashboard();
      updateCountdown(); // restart
    }
  }, 1000);
}

// Full dashboard refresh
async function refreshDashboard() {
  await Promise.all([fetchStats(), loadAllCharts()]);
}

// Initialize dashboard
document.addEventListener('DOMContentLoaded', () => {
  fetchStats();
  loadAllCharts();
  
  // Auto-refresh
  setInterval(refreshDashboard, REFRESH_INTERVAL);
  updateCountdown();
  
  // Animate existing counters on load
  document.querySelectorAll('.counter-animate').forEach(el => {
    const target = parseInt(el.dataset.count);
    animateCounter(el, target);
  });
});

