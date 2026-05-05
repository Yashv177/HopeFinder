// document.addEventListener('DOMContentLoaded', function () {
//   initAnimatedCounters();
//   init3DCardEffects();
//   initAnimatedProgressBars();
//   initRealTimeUpdates();
//   initInteractiveGraph();

//   loadDashboardData(); // 🔥 MAIN FUNCTION
// });

// /* ===============================
//    🔥 FETCH DATA FROM BACKEND
// =================================*/
// function loadDashboardData() {
//   fetch('../admin/api/dashboard_charts_data.php')
//     .then(res => res.json())
//     .then(data => {

//       initResolutionTrendChart(data);
//       initStatusDistributionChart(data);
//       initWeeklyActivityChart(data);
//       init3DGaugeCharts(data);

//     })
//     .catch(err => {
//       console.error("Dashboard Data Error:", err);
//     });
// }

// /* ===============================
//    📈 RESOLUTION TREND CHART
// =================================*/
// function initResolutionTrendChart(apiData) {
//   const ctx = document.getElementById('resolutionTrendChart');
//   if (!ctx) return;

//   new Chart(ctx, {
//     type: 'bar',
//     data: {
//       labels: apiData.trend_labels || ["No Data"],
//       datasets: [{
//         label: 'Resolved Cases',
//         data: apiData.trend || [0],
//         backgroundColor: 'rgba(40, 167, 69, 0.8)',
//         borderRadius: 8
//       }]
//     },
//     options: {
//       responsive: true,
//       maintainAspectRatio: false
//     }
//   });
// }

// /* ===============================
//    🍩 STATUS DISTRIBUTION
// =================================*/
// function initStatusDistributionChart(apiData) {
//   const ctx = document.getElementById('statusDistributionChart');
//   if (!ctx) return;

//   new Chart(ctx, {
//     type: 'doughnut',
//     data: {
//       labels: Object.keys(apiData.status || {}),
//       datasets: [{
//         data: Object.values(apiData.status || {}),
//         backgroundColor: [
//           '#ffc107',
//           '#0d6efd',
//           '#28a745',
//           '#dc3545',
//           '#6c757d'
//         ]
//       }]
//     },
//     options: {
//       responsive: true,
//       cutout: '60%'
//     }
//   });
// }

// /* ===============================
//    🕸️ WEEKLY ACTIVITY
// =================================*/
// function initWeeklyActivityChart(apiData) {
//   const ctx = document.getElementById('weeklyActivityChart');
//   if (!ctx) return;

//   const days = ["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"];
//   const weeklyData = days.map(day => (apiData.weekly && apiData.weekly[day]) || 0);

//   new Chart(ctx, {
//     type: 'radar',
//     data: {
//       labels: days,
//       datasets: [{
//         label: 'Reports',
//         data: weeklyData,
//         backgroundColor: 'rgba(13,110,253,0.2)',
//         borderColor: '#0d6efd'
//       }]
//     },
//     options: {
//       responsive: true
//     }
//   });
// }

// /* ===============================
//    ⚡ GAUGE CHART (FIXED)
// =================================*/
// function init3DGaugeCharts(apiData) {
//   const gaugeContainer = document.getElementById('gaugeChart');
//   if (!gaugeContainer) return;

//   gaugeContainer.innerHTML = ""; // clear

//   const value = apiData.efficiency || 0;

//   const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
//   svg.setAttribute('width', '250');
//   svg.setAttribute('height', '150');

//   const text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
//   text.setAttribute('x', '125');
//   text.setAttribute('y', '100');
//   text.setAttribute('text-anchor', 'middle');
//   text.setAttribute('font-size', '32');
//   text.setAttribute('font-weight', 'bold');
//   text.textContent = value + "%";

//   svg.appendChild(text);
//   gaugeContainer.appendChild(svg);
// }

// /* ===============================
//    🔢 COUNTER ANIMATION
// =================================*/
// function initAnimatedCounters() {
//   const counters = document.querySelectorAll('.counter-animate');

//   counters.forEach(el => {
//     const target = parseInt(el.getAttribute('data-count')) || 0;
//     let count = 0;

//     const interval = setInterval(() => {
//       count += Math.ceil(target / 50);
//       if (count >= target) {
//         count = target;
//         clearInterval(interval);
//       }
//       el.innerText = count;
//     }, 30);
//   });
// }

// /* ===============================
//    🎯 UI EFFECTS (UNCHANGED)
// =================================*/
// function init3DCardEffects() {
//   const cards = document.querySelectorAll('.card-3d, .stat-card-3d');

//   cards.forEach(card => {
//     card.addEventListener('mousemove', e => {
//       const rect = card.getBoundingClientRect();
//       const x = e.clientX - rect.left;
//       const y = e.clientY - rect.top;

//       const rotateX = (y - rect.height / 2) / 20;
//       const rotateY = (rect.width / 2 - x) / 20;

//       card.style.transform = `rotateX(${rotateX}deg) rotateY(${rotateY}deg)`;
//     });

//     card.addEventListener('mouseleave', () => {
//       card.style.transform = 'rotateX(0) rotateY(0)';
//     });
//   });
// }

// function initAnimatedProgressBars() {
//   document.querySelectorAll('.progress-3d').forEach(bar => {
//     const val = bar.getAttribute('data-progress') || 0;
//     setTimeout(() => {
//       bar.style.width = val + '%';
//     }, 500);
//   });
// }

// function initRealTimeUpdates() {
//   setInterval(() => {
//     document.querySelectorAll('.live-stat').forEach(el => {
//       let val = parseInt(el.innerText) || 0;
//       val += Math.floor(Math.random() * 5);
//       el.innerText = val;
//     });
//   }, 5000);
// }

// function initInteractiveGraph() {
//   // leave as it is
// }



// ===============================
// 🚀 DASHBOARD MAIN INIT
// ===============================
document.addEventListener('DOMContentLoaded', function () {

  loadDashboardData(); // 🔥 main function

});


// ===============================
// 🔥 FETCH DATA FROM BACKEND
// ===============================
function loadDashboardData() {
  fetch('../admin/api/dashboard_charts_data.php')
    .then(res => res.json())
    .then(data => {

      // =====================
      // 🔹 CARDS (REAL DATA)
      // =====================
      updateCard("totalMissing", data.totalMissing);
      updateCard("totalFound", data.totalFound);
      updateCard("todayDetections", data.todayDetections);
      updateCard("aiSuccess", data.aiSuccess);

      // =====================
      // 🔹 CHARTS
      // =====================
      initResolutionTrendChart(data);
      initStatusDistributionChart(data);
      initWeeklyActivityChart(data);
      init3DGaugeCharts(data);

    })
    .catch(err => {
      console.error("Dashboard Error:", err);
    });
}


// ===============================
// 🔢 CARD ANIMATION
// ===============================
function updateCard(id, value) {
  const el = document.getElementById(id);
  if (!el) return;

  let start = 0;
  const end = parseInt(value) || 0;
  const step = Math.ceil(end / 30) || 1;

  const interval = setInterval(() => {
    start += step;
    if (start >= end) {
      start = end;
      clearInterval(interval);
    }
    el.innerText = start;
  }, 20);
}


// ===============================
// 📈 RESOLUTION TREND CHART
// ===============================
let resolutionChart;
function initResolutionTrendChart(data) {

  const ctx = document.getElementById('resolutionTrendChart');
  if (!ctx) return;

  if (resolutionChart) resolutionChart.destroy();

  resolutionChart = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: data.trend_labels || ["No Data"],
      datasets: [{
        label: 'Resolved Cases',
        data: data.trend || [0],
        backgroundColor: 'rgba(40, 167, 69, 0.8)',
        borderRadius: 8
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false
    }
  });
}


// ===============================
// 🍩 STATUS DISTRIBUTION
// ===============================
let statusChart;
function initStatusDistributionChart(data) {

  const ctx = document.getElementById('statusDistributionChart');
  if (!ctx) return;

  if (statusChart) statusChart.destroy();

  statusChart = new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: Object.keys(data.status || {}),
      datasets: [{
        data: Object.values(data.status || {}),
        backgroundColor: [
          '#ffc107',
          '#0d6efd',
          '#28a745',
          '#dc3545',
          '#6c757d'
        ]
      }]
    },
    options: {
      responsive: true,
      cutout: '60%'
    }
  });
}


// ===============================
// 🕸️ WEEKLY ACTIVITY
// ===============================
let weeklyChart;
function initWeeklyActivityChart(data) {

  const ctx = document.getElementById('weeklyActivityChart');
  if (!ctx) return;

  if (weeklyChart) weeklyChart.destroy();

  const days = ["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"];
  const weeklyData = days.map(day => (data.weekly && data.weekly[day]) || 0);

  weeklyChart = new Chart(ctx, {
    type: 'radar',
    data: {
      labels: days,
      datasets: [{
        label: 'Reports',
        data: weeklyData,
        backgroundColor: 'rgba(13,110,253,0.2)',
        borderColor: '#0d6efd'
      }]
    },
    options: {
      responsive: true
    }
  });
}


let monthlyChart;

function initMonthlyReportsChart(data) {

  const ctx = document.getElementById('monthlyReportsChart');
  if (!ctx) return;

  if (monthlyChart) monthlyChart.destroy();

  const gradient = ctx.getContext('2d').createLinearGradient(0, 0, 0, 300);
  gradient.addColorStop(0, 'rgba(13,110,253,0.6)');
  gradient.addColorStop(1, 'rgba(13,110,253,0.05)');

  monthlyChart = new Chart(ctx, {
    type: 'line',
    data: {
      labels: data.monthly_labels,
      datasets: [{
        label: 'Monthly Reports',
        data: data.monthly_data,
        borderColor: '#0d6efd',
        backgroundColor: gradient,
        fill: true,
        tension: 0.4,
        borderWidth: 3,
        pointRadius: 5,
        pointHoverRadius: 8
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: true
        }
      },
      scales: {
        y: {
          beginAtZero: true
        }
      }
    }
  });
}

// ===============================
// ⚡ GAUGE CHART
// ===============================
function init3DGaugeCharts(data) {

  const gaugeContainer = document.getElementById('gaugeChart');
  if (!gaugeContainer) return;

  gaugeContainer.innerHTML = `
    <div style="font-size:28px;font-weight:bold;color:#28a745">
      ${data.efficiency || 0}%
    </div>
    <small>System Efficiency</small>
  `;
}


// ===============================
// 🔄 OPTIONAL AUTO REFRESH
// ===============================
// setInterval(loadDashboardData, 5000);