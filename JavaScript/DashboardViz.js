/**
 * DashboardViz.js - Interactive Admin Dashboard Visualizations
 * Features: Animated Charts, 3D Effects, Interactive Graphs, Real-time Updates
 */

// Wait for DOM to be ready
document.addEventListener('DOMContentLoaded', function() {
  // Initialize visualization modules
  initAnimatedCounters();
  initInteractiveCharts();
  init3DCardEffects();
  initAnimatedProgressBars();
  initRealTimeUpdates();
  initInteractiveGraph();
  init3DGaugeCharts();
});

/**
 * Animated Counters with 3D Flip Effect
 */
function initAnimatedCounters() {
  const counterElements = document.querySelectorAll('.counter-animate');
  
  counterElements.forEach(element => {
    const finalValue = parseInt(element.getAttribute('data-count')) || 0;
    animateCounter(element, finalValue);
  });
}

function animateCounter(element, target) {
  let current = 0;
  const increment = target / 50;
  const duration = 1500;
  const stepTime = duration / 50;
  
  // Add 3D flip effect
  element.style.transform = 'perspective(500px) rotateX(0deg)';
  element.style.transition = `transform 0.3s ease`;
  
  const timer = setInterval(() => {
    current += increment;
    if (current >= target) {
      current = target;
      clearInterval(timer);
      // 3D flip animation on completion
      element.style.transform = 'perspective(500px) rotateX(360deg)';
      setTimeout(() => {
        element.style.transform = 'perspective(500px) rotateX(0deg)';
      }, 300);
    }
    element.textContent = formatNumber(Math.floor(current));
  }, stepTime);
}

function formatNumber(num) {
  return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

/**
 * Interactive Charts using Chart.js with Animations
 */
function initInteractiveCharts() {
  // Wait for Chart.js to load
  if (typeof Chart === 'undefined') {
    setTimeout(initInteractiveCharts, 100);
    return;
  }
  
  initMonthlyReportsChart();
  initResolutionTrendChart();
  initStatusDistributionChart();
  initWeeklyActivityChart();
}

function initMonthlyReportsChart() {
  const ctx = document.getElementById('monthlyReportsChart');
  if (!ctx) return;
  
  const gradient = ctx.getContext('2d').createLinearGradient(0, 0, 0, 400);
  gradient.addColorStop(0, 'rgba(13, 110, 253, 0.8)');
  gradient.addColorStop(1, 'rgba(13, 110, 253, 0.1)');
  
  new Chart(ctx, {
    type: 'line',
    data: {
      labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
      datasets: [{
        label: 'New Reports',
        data: [65, 78, 90, 81, 95, 110, 125, 118, 130, 142, 155, 168],
        borderColor: '#0d6efd',
        backgroundColor: gradient,
        fill: true,
        tension: 0.4,
        borderWidth: 3,
        pointBackgroundColor: '#fff',
        pointBorderColor: '#0d6efd',
        pointBorderWidth: 3,
        pointRadius: 6,
        pointHoverRadius: 10
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      animation: {
        duration: 2000,
        easing: 'easeOutQuart'
      },
      plugins: {
        legend: {
          display: true,
          position: 'top',
          labels: { font: { size: 14, weight: 'bold' } }
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          grid: { color: 'rgba(0,0,0,0.05)' }
        },
        x: {
          grid: { display: false }
        }
      },
      interaction: {
        intersect: false,
        mode: 'index'
      }
    }
  });
}

function initResolutionTrendChart() {
  const ctx = document.getElementById('resolutionTrendChart');
  if (!ctx) return;
  
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
      datasets: [{
        label: 'Resolved Cases',
        data: [45, 52, 48, 61],
        backgroundColor: [
          'rgba(40, 167, 69, 0.8)',
          'rgba(40, 167, 69, 0.85)',
          'rgba(40, 167, 69, 0.9)',
          'rgba(40, 167, 69, 0.95)'
        ],
        borderColor: '#28a745',
        borderWidth: 2,
        borderRadius: 8,
        borderSkipped: false
      }, {
        label: 'Pending Cases',
        data: [23, 18, 25, 15],
        backgroundColor: 'rgba(255, 193, 7, 0.8)',
        borderColor: '#ffc107',
        borderWidth: 2,
        borderRadius: 8,
        borderSkipped: false
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      animation: {
        duration: 1500,
        easing: 'easeOutBounce'
      },
      plugins: {
        legend: { position: 'top' }
      },
      scales: {
        y: {
          beginAtZero: true,
          stacked: false,
          grid: { color: 'rgba(0,0,0,0.05)' }
        },
        x: {
          stacked: false,
          grid: { display: false }
        }
      }
    }
  });
}

function initStatusDistributionChart() {
  const ctx = document.getElementById('statusDistributionChart');
  if (!ctx) return;
  
  new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: ['Active Users', 'Pending Reports', 'Resolved Cases', 'Under Investigation'],
      datasets: [{
        data: [8542, 42, 1024, 156],
        backgroundColor: [
          'rgba(13, 110, 253, 0.85)',
          'rgba(255, 193, 7, 0.85)',
          'rgba(40, 167, 69, 0.85)',
          'rgba(220, 53, 69, 0.85)'
        ],
        borderColor: ['#fff', '#fff', '#fff', '#fff'],
        borderWidth: 3,
        hoverOffset: 15
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '60%',
      animation: {
        animateRotate: true,
        animateScale: true,
        duration: 2000,
        easing: 'easeOutQuart'
      },
      plugins: {
        legend: {
          position: 'bottom',
          labels: {
            padding: 20,
            usePointStyle: true,
            font: { size: 12 }
          }
        }
      }
    }
  });
}

function initWeeklyActivityChart() {
  const ctx = document.getElementById('weeklyActivityChart');
  if (!ctx) return;
  
  new Chart(ctx, {
    type: 'radar',
    data: {
      labels: ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
      datasets: [{
        label: 'Reports Filed',
        data: [65, 59, 80, 81, 56, 45, 30],
        backgroundColor: 'rgba(13, 110, 253, 0.2)',
        borderColor: '#0d6efd',
        borderWidth: 2,
        pointBackgroundColor: '#0d6efd',
        pointBorderColor: '#fff',
        pointBorderWidth: 2,
        pointRadius: 5
      }, {
        label: 'Cases Resolved',
        data: [28, 48, 40, 19, 86, 27, 20],
        backgroundColor: 'rgba(40, 167, 69, 0.2)',
        borderColor: '#28a745',
        borderWidth: 2,
        pointBackgroundColor: '#28a745',
        pointBorderColor: '#fff',
        pointBorderWidth: 2,
        pointRadius: 5
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      animation: {
        duration: 2000,
        easing: 'easeOutQuart'
      },
      scales: {
        r: {
          angleLines: { color: 'rgba(0,0,0,0.1)' },
          grid: { color: 'rgba(0,0,0,0.1)' },
          pointLabels: {
            font: { size: 12, weight: 'bold' }
          },
          suggestedMin: 0,
          suggestedMax: 100
        }
      },
      plugins: {
        legend: { position: 'top' }
      }
    }
  });
}

/**
 * 3D Card Effects with Hover Animations
 */
function init3DCardEffects() {
  const cards = document.querySelectorAll('.card-3d, .stat-card-3d');
  
  cards.forEach(card => {
    card.addEventListener('mousemove', handle3DMouseMove);
    card.addEventListener('mouseleave', handle3DMouseLeave);
  });
}

function handle3DMouseMove(e) {
  const card = e.currentTarget;
  const rect = card.getBoundingClientRect();
  const x = e.clientX - rect.left;
  const y = e.clientY - rect.top;
  
  const centerX = rect.width / 2;
  const centerY = rect.height / 2;
  
  const rotateX = (y - centerY) / 20;
  const rotateY = (centerX - x) / 20;
  
  card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale3d(1.02, 1.02, 1.02)`;
  card.style.boxShadow = `${-rotateY * 2}px ${rotateX * 2}px 30px rgba(0,0,0,0.15)`;
}

function handle3DMouseLeave(e) {
  const card = e.currentTarget;
  card.style.transform = 'perspective(1000px) rotateX(0deg) rotateY(0deg) scale3d(1, 1, 1)';
  card.style.boxShadow = '0 4px 15px rgba(0,0,0,0.1)';
}

/**
 * Animated Progress Bars with 3D Effect
 */
function initAnimatedProgressBars() {
  const progressBars = document.querySelectorAll('.progress-3d');
  
  progressBars.forEach(bar => {
    const value = bar.getAttribute('data-progress') || 0;
    setTimeout(() => {
      bar.style.width = value + '%';
    }, 500);
  });
}

/**
 * Real-time Updates with Live Counter
 */
function initRealTimeUpdates() {
  // Update counters every 5 seconds
  setInterval(() => {
    updateLiveStats();
  }, 5000);
}

function updateLiveStats() {
  const elements = document.querySelectorAll('.live-stat');
  elements.forEach(el => {
    const currentValue = parseInt(el.textContent.replace(/,/g, '')) || 0;
    const randomChange = Math.floor(Math.random() * 5) - 2;
    const newValue = Math.max(0, currentValue + randomChange);
    el.textContent = formatNumber(newValue);
    el.style.transform = 'scale(1.1)';
    setTimeout(() => {
      el.style.transform = 'scale(1)';
    }, 200);
  });
}

/**
 * Interactive Network Graph Visualization
 */
function initInteractiveGraph() {
  const graphContainer = document.getElementById('networkGraph');
  if (!graphContainer) return;
  
  // Create SVG-based interactive graph
  createNetworkGraph(graphContainer);
}

function createNetworkGraph(container) {
  const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
  svg.setAttribute('width', '100%');
  svg.setAttribute('height', '300');
  svg.setAttribute('viewBox', '0 0 600 300');
  svg.style.overflow = 'visible';
  
  // Define nodes data
  const nodes = [
    { id: 'admin', x: 300, y: 150, r: 25, color: '#0d6efd', label: 'Admin' },
    { id: 'users', x: 150, y: 80, r: 20, color: '#28a745', label: 'Users' },
    { id: 'police', x: 450, y: 80, r: 20, color: '#ffc107', label: 'Police' },
    { id: 'reports', x: 150, y: 220, r: 20, color: '#dc3545', label: 'Reports' },
    { id: 'matches', x: 450, y: 220, r: 20, color: '#6f42c1', label: 'Matches' }
  ];
  
  // Define connections
  const connections = [
    { from: 'admin', to: 'users' },
    { from: 'admin', to: 'police' },
    { from: 'admin', to: 'reports' },
    { from: 'admin', to: 'matches' },
    { from: 'users', to: 'reports' },
    { from: 'police', to: 'matches' }
  ];
  
  // Create animated connections
  connections.forEach((conn, index) => {
    const fromNode = nodes.find(n => n.id === conn.from);
    const toNode = nodes.find(n => n.id === conn.to);
    
    // Create gradient for connection
    const defs = document.createElementNS('http://www.w3.org/2000/svg', 'defs');
    const gradientId = `grad-${conn.from}-${conn.to}`;
    const gradient = document.createElementNS('http://www.w3.org/2000/svg', 'linearGradient');
    gradient.setAttribute('id', gradientId);
    gradient.setAttribute('x1', '0%');
    gradient.setAttribute('y1', '0%');
    gradient.setAttribute('x2', '100%');
    gradient.setAttribute('y2', '0%');
    
    const stop1 = document.createElementNS('http://www.w3.org/2000/svg', 'stop');
    stop1.setAttribute('offset', '0%');
    stop1.setAttribute('style', `stop-color:${fromNode.color};stop-opacity:1`);
    
    const stop2 = document.createElementNS('http://www.w3.org/2000/svg', 'stop');
    stop2.setAttribute('offset', '100%');
    stop2.setAttribute('style', `stop-color:${toNode.color};stop-opacity:1`);
    
    gradient.appendChild(stop1);
    gradient.appendChild(stop2);
    defs.appendChild(gradient);
    svg.appendChild(defs);
    
    // Draw connection line
    const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
    line.setAttribute('x1', fromNode.x);
    line.setAttribute('y1', fromNode.y);
    line.setAttribute('x2', toNode.x);
    line.setAttribute('y2', toNode.y);
    line.setAttribute('stroke', `url(#${gradientId})`);
    line.setAttribute('stroke-width', '3');
    line.setAttribute('stroke-dasharray', '10,5');
    line.style.animation = `dash 20s linear infinite`;
    
    svg.appendChild(line);
  });
  
  // Create nodes
  nodes.forEach(node => {
    // Outer glow
    const glow = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
    glow.setAttribute('cx', node.x);
    glow.setAttribute('cy', node.y);
    glow.setAttribute('r', node.r + 8);
    glow.setAttribute('fill', node.color);
    glow.setAttribute('opacity', '0.3');
    glow.style.animation = 'pulse 2s ease-in-out infinite';
    svg.appendChild(glow);
    
    // Main node
    const circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
    circle.setAttribute('cx', node.x);
    circle.setAttribute('cy', node.y);
    circle.setAttribute('r', node.r);
    circle.setAttribute('fill', node.color);
    circle.setAttribute('stroke', '#fff');
    circle.setAttribute('stroke-width', '3');
    circle.style.cursor = 'pointer';
    circle.style.transition = 'all 0.3s ease';
    
    circle.addEventListener('mouseenter', function() {
      this.setAttribute('r', node.r + 10);
      this.setAttribute('stroke-width', '4');
    });
    
    circle.addEventListener('mouseleave', function() {
      this.setAttribute('r', node.r);
      this.setAttribute('stroke-width', '3');
    });
    
    svg.appendChild(circle);
    
    // Label
    const text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
    text.setAttribute('x', node.x);
    text.setAttribute('y', node.y + node.r + 20);
    text.setAttribute('text-anchor', 'middle');
    text.setAttribute('fill', '#333');
    text.setAttribute('font-weight', 'bold');
    text.setAttribute('font-size', '12');
    text.textContent = node.label;
    svg.appendChild(text);
  });
  
  // Add animation styles
  const style = document.createElementNS('http://www.w3.org/2000/svg', 'style');
  style.textContent = `
    @keyframes dash {
      to { stroke-dashoffset: -1000; }
    }
    @keyframes pulse {
      0%, 100% { transform: scale(1); opacity: 0.3; }
      50% { transform: scale(1.1); opacity: 0.5; }
    }
  `;
  svg.appendChild(style);
  
  container.appendChild(svg);
}

/**
 * 3D Gauge Charts
 */
function init3DGaugeCharts() {
  const gaugeContainer = document.getElementById('gaugeChart');
  if (!gaugeContainer) return;
  
  createGaugeChart(gaugeContainer);
}

function createGaugeChart(container) {
  const value = 75; // Percentage
  const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
  svg.setAttribute('width', '250');
  svg.setAttribute('height', '150');
  svg.style.overflow = 'visible';
  
  // Background arc
  const bgArc = document.createElementNS('http://www.w3.org/2000/svg', 'path');
  const bgPath = describeArc(125, 125, 80, 180, 360);
  bgArc.setAttribute('d', bgPath);
  bgArc.setAttribute('fill', 'none');
  bgArc.setAttribute('stroke', '#e9ecef');
  bgArc.setAttribute('stroke-width', '20');
  svg.appendChild(bgArc);
  
  // Value arc with 3D effect
  const valueArc = document.createElementNS('http://www.w3.org/2000/svg', 'path');
  const valueAngle = 180 + (value / 100 * 180);
  const valuePath = describeArc(125, 125, 80, 180, valueAngle);
  valueArc.setAttribute('d', valuePath);
  valueArc.setAttribute('fill', 'none');
  valueArc.setAttribute('stroke', 'url(#gaugeGradient)');
  valueArc.setAttribute('stroke-width', '20');
  valueArc.setAttribute('stroke-linecap', 'round');
  valueArc.style.transition = 'stroke-dashoffset 1.5s ease-out';
  svg.appendChild(valueArc);
  
  // Gradient
  const defs = document.createElementNS('http://www.w3.org/2000/svg', 'defs');
  const gradient = document.createElementNS('http://www.w3.org/2000/svg', 'linearGradient');
  gradient.setAttribute('id', 'gaugeGradient');
  gradient.setAttribute('x1', '0%');
  gradient.setAttribute('y1', '0%');
  gradient.setAttribute('x2', '100%');
  gradient.setAttribute('y2', '0%');
  
  const stop1 = document.createElementNS('http://www.w3.org/2000/svg', 'stop');
  stop1.setAttribute('offset', '0%');
  stop1.setAttribute('style', 'stop-color:#28a745');
  
  const stop2 = document.createElementNS('http://www.w3.org/2000/svg', 'stop');
  stop2.setAttribute('offset', '100%');
  stop2.setAttribute('style', 'stop-color:#0d6efd');
  
  gradient.appendChild(stop1);
  gradient.appendChild(stop2);
  defs.appendChild(gradient);
  svg.appendChild(defs);
  
  // Center value text
  const text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
  text.setAttribute('x', '125');
  text.setAttribute('y', '110');
  text.setAttribute('text-anchor', 'middle');
  text.setAttribute('font-size', '32');
  text.setAttribute('font-weight', 'bold');
  text.setAttribute('fill', '#333');
  text.textContent = value + '%';
  svg.appendChild(text);
  
  // Label
  const label = document.createElementNS('http://www.w3.org/2000/svg', 'text');
  label.setAttribute('x', '125');
  label.setAttribute('y', '135');
  label.setAttribute('text-anchor', 'middle');
  label.setAttribute('font-size', '14');
  label.setAttribute('fill', '#6c757d');
  label.textContent = 'Efficiency';
  svg.appendChild(label);
  
  container.appendChild(svg);
}

// Helper function for SVG arc paths
function describeArc(x, y, radius, startAngle, endAngle) {
  const start = polarToCartesian(x, y, radius, endAngle);
  const end = polarToCartesian(x, y, radius, startAngle);
  const largeArcFlag = endAngle - startAngle <= 180 ? '0' : '1';
  return [
    'M', start.x, start.y,
    'A', radius, radius, 0, largeArcFlag, 0, end.x, end.y
  ].join(' ');
}

function polarToCartesian(centerX, centerY, radius, angleInDegrees) {
  const angleInRadians = (angleInDegrees - 90) * Math.PI / 180.0;
  return {
    x: centerX + (radius * Math.cos(angleInRadians)),
    y: centerY + (radius * Math.sin(angleInRadians))
  };
}

/**
 * Real-time Chart with Live Data Updates
 */
function initRealTimeChart() {
  const ctx = document.getElementById('realTimeChart');
  if (!ctx) return;

  const data = {
    labels: [],
    datasets: [{
      label: 'Live Reports',
      data: [],
      borderColor: '#e94560',
      backgroundColor: 'rgba(233, 69, 96, 0.1)',
      fill: true,
      tension: 0.4,
      pointBackgroundColor: '#e94560',
      pointBorderColor: '#fff',
      pointBorderWidth: 2,
      pointRadius: 4
    }]
  };

  const config = {
    type: 'line',
    data: data,
    options: {
      responsive: true,
      maintainAspectRatio: false,
      animation: {
        duration: 1000,
        easing: 'easeOutQuart'
      },
      plugins: {
        legend: { display: false }
      },
      scales: {
        x: {
          type: 'time',
          time: {
            unit: 'minute',
            displayFormats: { minute: 'HH:mm' }
          },
          grid: { display: false }
        },
        y: {
          beginAtZero: true,
          grid: { color: 'rgba(0,0,0,0.05)' }
        }
      },
      interaction: {
        intersect: false,
        mode: 'index'
      }
    }
  };

  const chart = new Chart(ctx, config);

  // Simulate real-time data updates
  setInterval(() => {
    const now = new Date();
    const newValue = Math.floor(Math.random() * 10) + 5;

    if (data.labels.length > 20) {
      data.labels.shift();
      data.datasets[0].data.shift();
    }

    data.labels.push(now);
    data.datasets[0].data.push(newValue);

    chart.update('none'); // Update without animation for smooth real-time effect
  }, 3000);
}

/**
 * Heatmap Chart for Activity Patterns
 */
function initHeatmapChart() {
  const ctx = document.getElementById('heatmapChart');
  if (!ctx) return;

  const data = {
    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
    datasets: [{
      label: 'Morning (6-12)',
      data: [15, 18, 22, 20, 25, 12, 8],
      backgroundColor: 'rgba(102, 126, 234, 0.8)',
      borderColor: '#667eea',
      borderWidth: 1
    }, {
      label: 'Afternoon (12-18)',
      data: [28, 32, 35, 38, 42, 25, 15],
      backgroundColor: 'rgba(102, 126, 234, 0.6)',
      borderColor: '#667eea',
      borderWidth: 1
    }, {
      label: 'Evening (18-24)',
      data: [22, 25, 28, 30, 35, 20, 12],
      backgroundColor: 'rgba(102, 126, 234, 0.4)',
      borderColor: '#667eea',
      borderWidth: 1
    }]
  };

  new Chart(ctx, {
    type: 'bar',
    data: data,
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { position: 'top' }
      },
      scales: {
        x: { stacked: true, grid: { display: false } },
        y: { stacked: true, beginAtZero: true }
      },
      animation: {
        duration: 2000,
        easing: 'easeOutBounce'
      }
    }
  });
}

/**
 * Bubble Chart for Multi-dimensional Data
 */
function initBubbleChart() {
  const ctx = document.getElementById('bubbleChart');
  if (!ctx) return;

  const data = {
    datasets: [{
      label: 'Reports by Location',
      data: [
        { x: 20, y: 30, r: 15, location: 'Delhi' },
        { x: 40, y: 10, r: 25, location: 'Mumbai' },
        { x: 15, y: 45, r: 10, location: 'Bangalore' },
        { x: 35, y: 25, r: 20, location: 'Chennai' },
        { x: 50, y: 35, r: 18, location: 'Kolkata' }
      ],
      backgroundColor: [
        'rgba(102, 126, 234, 0.6)',
        'rgba(240, 147, 251, 0.6)',
        'rgba(79, 172, 254, 0.6)',
        'rgba(67, 233, 123, 0.6)',
        'rgba(250, 112, 154, 0.6)'
      ],
      borderColor: [
        '#667eea',
        '#f093fb',
        '#4facfe',
        '#43e97b',
        '#fa709a'
      ],
      borderWidth: 2
    }]
  };

  new Chart(ctx, {
    type: 'bubble',
    data: data,
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            label: function(context) {
              return `${context.raw.location}: ${context.raw.r} reports`;
            }
          }
        }
      },
      scales: {
        x: {
          title: { display: true, text: 'Population Density' },
          grid: { color: 'rgba(0,0,0,0.05)' }
        },
        y: {
          title: { display: true, text: 'Crime Rate Index' },
          grid: { color: 'rgba(0,0,0,0.05)' }
        }
      },
      animation: {
        duration: 2000,
        easing: 'easeOutElastic'
      }
    }
  });
}

/**
 * Polar Area Chart for Department Performance
 */
function initPolarAreaChart() {
  const ctx = document.getElementById('polarChart');
  if (!ctx) return;

  const data = {
    labels: ['Police Dept', 'Admin', 'Tech Support', 'Investigation', 'Public Relations'],
    datasets: [{
      data: [85, 92, 78, 88, 65],
      backgroundColor: [
        'rgba(102, 126, 234, 0.8)',
        'rgba(240, 147, 251, 0.8)',
        'rgba(79, 172, 254, 0.8)',
        'rgba(67, 233, 123, 0.8)',
        'rgba(250, 112, 154, 0.8)'
      ],
      borderColor: [
        '#667eea',
        '#f093fb',
        '#4facfe',
        '#43e97b',
        '#fa709a'
      ],
      borderWidth: 2
    }]
  };

  new Chart(ctx, {
    type: 'polarArea',
    data: data,
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'right',
          labels: { padding: 20 }
        }
      },
      scales: {
        r: {
          beginAtZero: true,
          grid: { color: 'rgba(0,0,0,0.1)' },
          ticks: {
            backdropColor: 'rgba(255,255,255,0.8)',
            color: '#333'
          }
        }
      },
      animation: {
        animateRotate: true,
        animateScale: true,
        duration: 2000,
        easing: 'easeOutQuart'
      }
    }
  });
}

/**
 * Floating Particles Background Effect
 */
function createFloatingParticles() {
  const container = document.getElementById('particlesContainer');
  if (!container) return;
  
  for (let i = 0; i < 20; i++) {
    const particle = document.createElement('div');
    particle.className = 'floating-particle';
    particle.style.cssText = `
      position: absolute;
      width: ${Math.random() * 10 + 5}px;
      height: ${Math.random() * 10 + 5}px;
      background: rgba(13, 110, 253, ${Math.random() * 0.3 + 0.1});
      border-radius: 50%;
      left: ${Math.random() * 100}%;
      top: ${Math.random() * 100}%;
      animation: float ${Math.random() * 10 + 10}s linear infinite;
      pointer-events: none;
    `;
    container.appendChild(particle);
  }
}

// Add global animation styles
const styleSheet = document.createElement('style');
styleSheet.textContent = `
  @keyframes float {
    0%, 100% { transform: translateY(0) translateX(0); opacity: 0; }
    10% { opacity: 1; }
    90% { opacity: 1; }
    100% { transform: translateY(-100vh) translateX(50px); opacity: 0; }
  }
  
  @keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
  }
  
  @keyframes glow {
    0%, 100% { box-shadow: 0 0 10px rgba(13, 110, 253, 0.3); }
    50% { box-shadow: 0 0 25px rgba(13, 110, 253, 0.6); }
  }
  
  .card-3d, .stat-card-3d {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    transform-style: preserve-3d;
  }
  
  .stat-card-3d {
    background: linear-gradient(145deg, #ffffff, #f0f0f0);
    border-radius: 15px;
    padding: 20px;
    margin-bottom: 20px;
  }
  
  .stat-card-3d:hover {
    box-shadow: 0 10px 40px rgba(0,0,0,0.15);
  }
  
  .chart-container {
    position: relative;
    height: 300px;
    margin: 20px 0;
  }
  
  .viz-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 5px 25px rgba(0,0,0,0.1);
    overflow: hidden;
  }
  
  .viz-card-header {
    background: linear-gradient(135deg, #0d6efd, #0a97e0);
    color: white;
    padding: 15px 20px;
    font-weight: bold;
  }
  
  .viz-card-body {
    padding: 20px;
  }
  
  .progress-3d {
    height: 12px;
    background: #e9ecef;
    border-radius: 6px;
    overflow: hidden;
    box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);
  }
  
  .progress-3d .progress-bar {
    height: 100%;
    border-radius: 6px;
    background: linear-gradient(90deg, #0d6efd, #0a97e0);
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    transition: width 1s ease-out;
  }
  
  .live-indicator {
    display: inline-block;
    width: 8px;
    height: 8px;
    background: #28a745;
    border-radius: 50%;
    margin-right: 8px;
    animation: pulse 1.5s ease-in-out infinite;
  }
  
  .counter-animate {
    font-size: 2.5rem;
    font-weight: 700;
    color: #0d6efd;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
  }
`;
document.head.appendChild(styleSheet);

// Export functions for external use
window.DashboardViz = {
  animateCounter,
  formatNumber,
  updateLiveStats,
  createNetworkGraph,
  createGaugeChart
};

