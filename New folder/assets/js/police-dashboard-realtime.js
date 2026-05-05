/* ===========================================
   POLICE DASHBOARD - PREMIUM JS
   Fully Interactive • Animated • Modern
   =========================================== */

document.addEventListener('DOMContentLoaded', function() {
    initDashboard();
});

function initDashboard() {
    // Initialize components
    initSidebar();
    // initAnimations();
    initInteractions();
    initRealtimeUpdates();
}

// ===== SIDEBAR =====
function initSidebar() {
    const sidebarCollapse = document.getElementById('sidebarCollapse');
    const sidebar = document.getElementById('sidebar');
    const content = document.getElementById('content');
    
    // Toggle sidebar
    if (sidebarCollapse) {
        sidebarCollapse.addEventListener('click', function() {
            document.body.classList.toggle('sidebar-collapsed');
            
            if (document.body.classList.contains('sidebar-collapsed')) {
                sidebar.style.width = '80px';
                if (content) content.style.marginLeft = '80px';
            } else {
                sidebar.style.width = '280px';
                if (content) content.style.marginLeft = '280px';
            }
        });
    }
    
    // Smooth scroll navigation
    document.querySelectorAll('#sidebar a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href').substring(1);
            const target = document.getElementById(targetId);
            
            if (target) {
                // Update active state
                document.querySelectorAll('#sidebar a').forEach(a => a.classList.remove('active'));
                this.classList.add('active');
                
                // Smooth scroll to target
                const headerOffset = 20;
                const elementPosition = target.getBoundingClientRect().top;
                const offsetPosition = elementPosition + window.pageYOffset - headerOffset;
                
                window.scrollTo({
                    top: offsetPosition,
                    behavior: 'smooth'
                });
                
                // Close mobile sidebar if open
                if (window.innerWidth < 992) {
                    sidebar.classList.remove('show');
                }
            }
        });
    });
    
    // Update active link on scroll
    window.addEventListener('scroll', function() {
        const sections = document.querySelectorAll('section[id]');
        const scrollPos = window.pageYOffset + 100;
        
        sections.forEach(section => {
            const top = section.offsetTop;
            const height = section.offsetHeight;
            const id = section.getAttribute('id');
            const link = document.querySelector(`#sidebar a[href="#${id}"]`);
            
            if (link && scrollPos >= top && scrollPos < top + height) {
                document.querySelectorAll('#sidebar a').forEach(a => a.classList.remove('active'));
                link.classList.add('active');
            }
        });
    });
}



// ===== ANIMATIONS =====
function initAnimations() {
    // Staggered card animations
    const cards = document.querySelectorAll('.stat-card, .chart-container, .card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        setTimeout(() => {
            card.style.transition = 'all 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 80);
    });
    
    // Number counter animation for stat values
    animateCounters();
}

function animateCounters() {
    const statIds = ['total-reports', 'pending-verification', 'verified-cases', 'matches-found'];
    statIds.forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            const finalValue = parseInt(el.textContent);
            animateValue(el, 0, finalValue, 1500);
        }
    });
}

function animateValue(element, start, end, duration) {
    const range = end - start;
    const startTime = performance.now();
    
    function update(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        const easeProgress = 1 - Math.pow(1 - progress, 3);
        const current = Math.floor(start + range * easeProgress);
        element.textContent = current;
        
        if (progress < 1) {
            requestAnimationFrame(update);
        }
    }
    
    requestAnimationFrame(update);
}

// ===== INTERACTIONS =====
function initInteractions() {
    // Card hover effects
    document.querySelectorAll('.stat-card').forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
        });
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
    
    // Button click effects
    document.querySelectorAll('.btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            const rect = this.getBoundingClientRect();
            const ripple = document.createElement('span');
            ripple.style.cssText = `
                position: absolute;
                background: rgba(255, 255, 255, 0.3);
                border-radius: 50%;
                transform: scale(0);
                animation: ripple 0.6s ease-out;
                pointer-events: none;
                width: 100px;
                height: 100px;
                left: ${e.clientX - rect.left - 50}px;
                top: ${e.clientY - rect.top - 50}px;
            `;
            this.style.position = 'relative';
            this.style.overflow = 'hidden';
            this.appendChild(ripple);
            setTimeout(() => ripple.remove(), 600);
        });
    });
    
    // Add ripple animation
    const style = document.createElement('style');
    style.textContent = `
        @keyframes ripple {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(style);
    
    // Table row hover
    document.querySelectorAll('.table tbody tr').forEach(row => {
        row.style.transition = 'background 0.2s ease';
    });
    
    // Search focus
    const searchInput = document.getElementById('policeSearch');
    if (searchInput) {
        searchInput.addEventListener('focus', function() {
            this.parentElement.style.transform = 'scale(1.02)';
        });
        searchInput.addEventListener('blur', function() {
            this.parentElement.style.transform = 'scale(1)';
        });
    }
}

// ===== REAL-TIME UPDATES =====
function initRealtimeUpdates() {
    // Simulate real-time updates
    setInterval(() => {
        updateNotificationBadge();
    }, 15000);
}

function updateNotificationBadge() {
    const badge = document.querySelector('.topbar .badge');
    if (badge) {
        const newCount = Math.floor(Math.random() * 5);
        badge.textContent = newCount;
        badge.style.animation = 'none';
        badge.offsetHeight; // Trigger reflow
        badge.style.animation = 'pulse 2s infinite';
    }
}

// ===== HELPER FUNCTIONS =====
function showNotification(type, message) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: type,
            title: type === 'success' ? 'Success!' : 'Info',
            text: message,
            timer: 2500,
            showConfirmButton: false,
            background: '#16161e',
            color: '#fff'
        });
    } else {
        alert(message);
    }
}

function refreshCharts() {
    createReportsChart();
    createStatusChart();
    createFoundVsMissingChart();
    showNotification('info', 'Charts refreshed');
}

function refreshAllData() {
    showNotification('info', 'Refreshing data...');
    setTimeout(() => {
        showNotification('success', 'Data refreshed!');
    }, 1000);
}

// ===== OVERRIDE FUNCTIONS =====
window.openCase = function(id) {
    const caseSection = document.getElementById('case-details');
    if (caseSection) {
        caseSection.scrollIntoView({ behavior: 'smooth' });
    }
    showNotification('info', `Loading case ${id}...`);
};

window.verifyCase = function(id) {
    if (confirm(`Verify case ${id}?`)) {
        showNotification('success', `Case ${id} verified!`);
    }
};

window.invalidateCase = function(id) {
    if (confirm(`Invalidate case ${id}?`)) {
        showNotification('success', `Case ${id} marked invalid!`);
    }
};

window.saveCaseUpdate = function() {
    showNotification('success', 'Case updated!');
};

window.refreshReports = function() {
    showNotification('info', 'Refreshing reports...');
};

window.refreshMatches = function() {
    showNotification('info', 'Refreshing matches...');
};

window.exportMatchesCSV = function() {
    showNotification('info', 'Exporting CSV...');
};

window.downloadReportPDF = function() {
    showNotification('info', 'Downloading PDF...');
};

window.editProfile = function() {
    showNotification('info', 'Edit profile');
};

// ===== SIDEBAR BUTTON FUNCTIONS =====
window.toggleDutyStatus = function() {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'question',
            title: 'Duty Status',
            text: 'Toggle your duty status (On Duty / Off Duty)',
            showCancelButton: true,
            confirmButtonText: 'On Duty',
            cancelButtonText: 'Off Duty',
            background: '#16161e',
            color: '#fff',
            confirmButtonColor: '#22c55e',
            cancelButtonColor: '#ef4444'
        }).then((result) => {
            if (result.isConfirmed) {
                showNotification('success', 'You are now On Duty');
            } else {
                showNotification('info', 'You are now Off Duty');
            }
        });
    } else {
        alert('Toggle Duty Status clicked');
    }
};

window.triggerEmergencyReport = function() {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'warning',
            title: 'Emergency Report',
            text: 'This will alert all nearby units. Continue?',
            showCancelButton: true,
            confirmButtonText: 'Send Emergency',
            cancelButtonText: 'Cancel',
            background: '#16161e',
            color: '#fff',
            confirmButtonColor: '#ef4444',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                showNotification('success', 'Emergency alert sent to all units!');
            }
        });
    } else {
        alert('Emergency Report clicked');
    }
};

// ===== KEYBOARD SHORTCUTS =====
document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + K for search
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        const searchInput = document.getElementById('policeSearch');
        if (searchInput) searchInput.focus();
    }
});

// Add CSS for notification badge pulse
const badgeStyle = document.createElement('style');
badgeStyle.textContent = `
    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.1); }
    }
`;
document.head.appendChild(badgeStyle);



async function loadFoundCases() {
  try {
    const res = await fetch("/HopeFinder/police/api/get_found_cases.php");
    const data = await res.json();

    const table = document.querySelector("#ai-table tbody");
    if (!table) return;

    table.innerHTML = "";

    data.forEach(item => {
      table.innerHTML += `
        <tr>
          <td>#${item.report_id}</td>
          <td>${item.missing_name}</td>
          <td>${item.address || 'N/A'}</td>
          <td>${formatDate(item.timestamp)}</td>
          <td>${item.confidence || 0}%</td>
          <td>
            <a href="../../police/api/generate_report_pdf.php?report_id=${item.report_id}" 
               target="_blank" 
               class="btn btn-sm btn-primary">
               View PDF
            </a>
          </td>
        </tr>
      `;
    });

  } catch (err) {
    console.log("AI load error:", err);
  }
}

// Date format helper
function formatDate(dateStr) {
  const d = new Date(dateStr);
  return d.toLocaleString();
}


document.getElementById("reportSearchInput")?.addEventListener("input", async function () {
  const value = this.value;

  try {
    const res = await fetch(`/HopeFinder/police/api/search_reports.php?search=${value}`);
    const data = await res.json();

    const table = document.querySelector("#reportsTable tbody");
    if (!table) return;

    table.innerHTML = "";

    data.forEach(r => {
      table.innerHTML += `
        <tr>
          <td>#${r.report_id}</td>
          <td>${r.missing_name}</td>
          <td>${r.last_seen_location}</td>
          <td>${r.status}</td>
        </tr>
      `;
    });

  } catch (err) {
    console.log("Search error:", err);
  }
});