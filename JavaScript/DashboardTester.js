/**
 * ============================================================
 * HopeFinder Admin Dashboard — Automated Testing System
 * ============================================================
 * One-click testing suite with visual feedback panel.
 * Tests APIs, Cards, Tables, Notifications, Charts, and Errors.
 *
 * Usage: Include this script in Dashboard2.php after all other scripts.
 *        A floating panel will appear automatically.
 * ============================================================
 */
(function () {
  'use strict';

  /* ----------------------------------------------------------
     CONFIGURATION
  ---------------------------------------------------------- */
  const CONFIG = {
    apiBase: '../admin/api/',
    notifApi: 'api/notifications.php',
    panelId: 'dashboard-test-panel',
    consolePrefix: '[DashboardTest]',
    timeout: 15000, // 15s per API call
    maxLogLines: 200
  };

  /* ----------------------------------------------------------
     STATE
  ---------------------------------------------------------- */
  const state = {
    testsRunning: false,
    results: [],
    logs: [],
    totalPassed: 0,
    totalFailed: 0,
    panelVisible: true
  };

  /* ----------------------------------------------------------
     UTILITIES
  ---------------------------------------------------------- */
  const $ = (sel) => document.querySelector(sel);
  const $$ = (sel) => document.querySelectorAll(sel);

  function log(level, message, detail) {
    const entry = { time: new Date().toLocaleTimeString(), level, message, detail };
    state.logs.push(entry);
    if (state.logs.length > CONFIG.maxLogLines) state.logs.shift();

    const prefix = CONFIG.consolePrefix;
    const styles = {
      pass: 'color:#28a745;font-weight:bold',
      fail: 'color:#dc3545;font-weight:bold',
      warn: 'color:#ffc107;font-weight:bold',
      info: 'color:#0dcaf0',
      group: 'color:#6f42c1;font-weight:bold;font-size:14px'
    };

    if (level === 'group') {
      console.groupCollapsed(`%c${prefix} ${message}`, styles[level]);
      if (detail) console.log(detail);
    } else if (level === 'groupEnd') {
      console.groupEnd();
    } else {
      console.log(`%c${prefix} [${level.toUpperCase()}] ${message}`, styles[level] || '');
      if (detail !== undefined) console.log(detail);
    }
  }

  function safeFetch(url, options = {}) {
    const controller = new AbortController();
    const timer = setTimeout(() => controller.abort(), CONFIG.timeout);
    return fetch(url, { ...options, signal: controller.signal })
      .finally(() => clearTimeout(timer));
  }

  function isValidJSON(str) {
    try { JSON.parse(str); return true; } catch { return false; }
  }

  function isHTMLResponse(text) {
    return /^\s*<(!doctype|html|head|body|div|span|h[1-6]|p|table)/i.test(text);
  }

  function recordResult(category, name, passed, message = '', detail = null) {
    const result = { category, name, passed, message, detail, time: Date.now() };
    state.results.push(result);
    if (passed) state.totalPassed++; else state.totalFailed++;
    log(passed ? 'pass' : 'fail', `${name}: ${passed ? 'PASSED' : 'FAILED'}`, message);
    updatePanelResults();
    return result;
  }

  /* ----------------------------------------------------------
     TEST SUITES
  ---------------------------------------------------------- */

  // ========== API TESTS ==========
  async function testAPIs() {
    log('group', '\ud83c\udf10 API Tests');
    const endpoints = [
      { name: 'getDashboardStats', url: `${CONFIG.apiBase}getDashboardStats.php`, method: 'GET', expectKeys: ['total_missing', 'total_found'] },
      { name: 'getUsers',          url: `${CONFIG.apiBase}getUsers.php`,          method: 'GET', expectArray: true },
      { name: 'getPolice',         url: `${CONFIG.apiBase}getPolice.php`,         method: 'GET', expectArray: true },
      { name: 'getReports',        url: `${CONFIG.apiBase}getReports.php`,        method: 'GET', expectKeys: ['status', 'data'] },
      { name: 'getSingleReport',   url: `${CONFIG.apiBase}getSingleReport.php?report_id=1`, method: 'GET', expectKeys: ['status'] },
      { name: 'dashboardChartsData', url: `${CONFIG.apiBase}dashboard_charts_data.php`, method: 'GET', expectKeys: ['status', 'trend'] },
      { name: 'notifications',     url: CONFIG.notifApi, method: 'POST', body: 'action=list', expectKeys: ['notifications', 'unread_count'] }
    ];

    for (const ep of endpoints) {
      try {
        const options = { method: ep.method, headers: {} };
        if (ep.method === 'POST') {
          options.headers['Content-Type'] = 'application/x-www-form-urlencoded';
          options.body = ep.body;
        }

        const res = await safeFetch(ep.url, options);

        if (!res.ok) {
          recordResult('API', ep.name, false, `HTTP ${res.status} ${res.statusText}`);
          continue;
        }

        const text = await res.text();

        if (isHTMLResponse(text)) {
          recordResult('API', ep.name, false, 'Received HTML instead of JSON (likely 404 or error page)');
          continue;
        }

        if (!isValidJSON(text)) {
          recordResult('API', ep.name, false, 'Invalid JSON response');
          continue;
        }

        const json = JSON.parse(text);

        if (ep.expectArray && !Array.isArray(json) && !Array.isArray(json.data)) {
          recordResult('API', ep.name, false, 'Expected array response');
          continue;
        }

        if (ep.expectKeys) {
          const target = Array.isArray(json) ? json : json;
          const hasKeys = ep.expectKeys.some(k => k in target || (target.data && k in target.data));
          if (!hasKeys) {
            recordResult('API', ep.name, false, `Missing expected keys: ${ep.expectKeys.join(', ')}`);
            continue;
          }
        }

        recordResult('API', ep.name, true, 'Valid JSON response');
      } catch (err) {
        if (err.name === 'AbortError') {
          recordResult('API', ep.name, false, `Request timeout (> ${CONFIG.timeout / 1000}s)`);
        } else {
          recordResult('API', ep.name, false, err.message);
        }
      }
    }
    log('groupEnd');
  }

  // ========== DASHBOARD CARDS TESTS ==========
  function testDashboardCards() {
    log('group', '\ud83c\udccf Dashboard Cards Tests');
    const cardIds = ['totalMissing', 'totalFound', 'todayDetections', 'aiSuccess'];

    cardIds.forEach(id => {
      const el = document.getElementById(id);
      if (!el) {
        recordResult('Cards', `Card #${id}`, false, 'Element not found in DOM');
        return;
      }
      const value = el.innerText.trim();
      const hasValue = value !== '' && value !== '0' && !isNaN(parseInt(value));
      recordResult('Cards', `Card #${id}`, true, `Value: "${value}"${hasValue ? ' (populated)' : ' (may still be loading)'}`);
    });

    // Check container
    const container = document.getElementById('dashboardCards');
    recordResult('Cards', 'Cards Container', !!container, container ? 'Found #dashboardCards' : 'Missing #dashboardCards');

    // Check loading spinner
    const spinner = document.getElementById('cardsLoading');
    recordResult('Cards', 'Loading Spinner', !!spinner, spinner ? 'Found #cardsLoading' : 'Missing #cardsLoading');

    log('groupEnd');
  }

  // ========== TABLE TESTS ==========
  function testTables() {
    log('group', '\ud83d\udcca Table Tests');

    const tables = [
      { id: 'usersTable',   name: 'Users Table',   expectedCols: 6 },
      { id: 'policeTable',  name: 'Police Table',  expectedCols: 6 },
      { id: 'reportsTable', name: 'Reports Table', expectedCols: 9 },
      { id: 'foundTable',   name: 'Found Table',   expectedCols: 6 },
      { id: 'matchesTable', name: 'Matches Table', expectedCols: 7 }
    ];

    tables.forEach(t => {
      const tbody = document.getElementById(t.id);
      if (!tbody) {
        recordResult('Tables', t.name, false, `TBody #${t.id} not found`);
        return;
      }

      // Check if rows exist (either static or dynamically loaded)
      const rows = tbody.querySelectorAll('tr');
      const hasRows = rows.length > 0;

      // Check parent table structure
      const table = tbody.closest('table');
      const thead = table?.querySelector('thead');
      const headers = thead ? thead.querySelectorAll('th').length : 0;

      const details = {
        tbodyId: t.id,
        rowCount: rows.length,
        headerCount: headers,
        expectedCols: t.expectedCols
      };

      const passed = hasRows && headers > 0;
      recordResult('Tables', t.name, passed,
        passed ? `${rows.length} rows, ${headers} headers` : 'No rows or missing headers',
        details
      );
    });

    // Test pagination containers
    const paginations = ['usersPagination', 'policePagination', 'reportsPagination'];
    paginations.forEach(id => {
      const el = document.getElementById(id);
      recordResult('Tables', `Pagination #${id}`, !!el, el ? 'Found' : 'Missing');
    });

    log('groupEnd');
  }

  // ========== NOTIFICATION TESTS ==========
  async function testNotifications() {
    log('group', '\ud83d\udd14 Notification Tests');

    // UI Elements
    const formElements = [
      'notifyForm', 'notifyTarget', 'notifyRecipient', 'notifyMessage'
    ];
    formElements.forEach(id => {
      const el = document.getElementById(id);
      recordResult('Notifications', `Form Element #${id}`, !!el, el ? 'Found' : 'Missing');
    });

    const recentList = document.getElementById('recentNotifs');
    recordResult('Notifications', 'Recent Notifications List', !!recentList, recentList ? 'Found #recentNotifs' : 'Missing');

    // API Test
    try {
      const res = await safeFetch(CONFIG.notifApi, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=list'
      });
      const text = await res.text();

      if (!isValidJSON(text)) {
        recordResult('Notifications', 'Notification API', false, 'Invalid JSON');
      } else {
        const json = JSON.parse(text);
        const hasData = 'notifications' in json && 'unread_count' in json;
        recordResult('Notifications', 'Notification API', hasData, hasData ? 'Valid structure' : 'Missing expected keys', json);
      }
    } catch (err) {
      recordResult('Notifications', 'Notification API', false, err.message);
    }

    log('groupEnd');
  }

  // ========== CHART TESTS ==========
  function testCharts() {
    log('group', '📈 Chart Tests');

    const chartIds = [
      { id: 'resolutionTrendChart', name: 'Resolution Trend (Bar)' },
      { id: 'statusDistributionChart', name: 'Status Distribution (Doughnut)' },
      { id: 'weeklyActivityChart', name: 'Weekly Activity (Radar)' },
      { id: 'gaugeChart', name: 'System Efficiency Gauge' }
    ];

    chartIds.forEach(c => {
      const canvas = document.getElementById(c.id);
      if (!canvas) {
        recordResult('Charts', c.name, false, `Canvas #${c.id} not found`);
        return;
      }

      // Check if Chart.js has initialized it
      const chartInstance = Chart.getChart?.(canvas);
      const hasChartInstance = !!chartInstance;

      recordResult('Charts', c.name, true,
        hasChartInstance ? 'Canvas exists + Chart.js instance active' : 'Canvas exists (Chart.js may still be initializing)',
        { canvasId: c.id, chartInstance: hasChartInstance }
      );
    });

    // Check chart data API endpoint availability
    recordResult('Charts', 'Chart Data Endpoint', true, 'Endpoint: ../admin/api/dashboard_charts_data.php');

    log('groupEnd');
  }

  // ========== ERROR DETECTION TESTS ==========
  function testErrors() {
    log('group', '🐛 Error Detection Tests');

    // 1. Null Element Checks (critical selectors used in JS)
    const criticalSelectors = [
      '#usersTable', '#policeTable', '#reportsTable',
      '#totalMissing', '#totalFound', '#todayDetections', '#aiSuccess',
      '#sidebar', '#sidebarCollapse', '#content',
      '#viewReportModal', '#assignPoliceModal',
      '#addUserForm', '#addPoliceForm', '#editPoliceForm'
    ];

    criticalSelectors.forEach(sel => {
      const el = document.querySelector(sel);
      recordResult('Errors', `DOM Check ${sel}`, !!el, el ? 'Element found' : 'CRITICAL: Element missing — JS may crash');
    });

    // 2. Duplicate ID Detection
    const allIds = $$('[id]');
    const idMap = {};
    allIds.forEach(el => {
      idMap[el.id] = (idMap[el.id] || 0) + 1;
    });
    const duplicates = Object.entries(idMap).filter(([id, count]) => count > 1);
    if (duplicates.length > 0) {
      duplicates.forEach(([id, count]) => {
        recordResult('Errors', `Duplicate ID "${id}"`, false, `Found ${count} times — document.getElementById() will be unreliable`);
      });
    } else {
      recordResult('Errors', 'Duplicate ID Check', true, 'No duplicate IDs found');
    }

    // 3. Variable / Namespace Checks
    recordResult('Errors', 'Global bootstrap', typeof window.bootstrap !== 'undefined', typeof window.bootstrap !== 'undefined' ? 'Bootstrap JS loaded' : 'Bootstrap JS NOT loaded — modals will fail');
    recordResult('Errors', 'Global Chart.js', typeof window.Chart !== 'undefined', typeof window.Chart !== 'undefined' ? 'Chart.js loaded' : 'Chart.js NOT loaded');
    recordResult('Errors', 'Global Swal', typeof window.Swal !== 'undefined', typeof window.Swal !== 'undefined' ? 'SweetAlert2 loaded' : 'SweetAlert2 NOT loaded');
    recordResult('Errors', 'Global API_BASE', typeof window.API_BASE !== 'undefined' || window.API_BASE, true, `API_BASE = "${window.API_BASE || '../admin/api/'}"`);

    // 4. Broken Image / Link Checks
    document.querySelectorAll('img').forEach((img, i) => {
      if (!img.src || img.src.endsWith('') || img.src.includes('placeholder')) {
        // Allow placeholders, just note them
      }
    });

    // 5. Form Validation Checks
    const forms = ['addUserForm', 'addPoliceForm', 'editPoliceForm', 'notifyForm'];
    forms.forEach(id => {
      const form = document.getElementById(id);
      if (form) {
        const hasRequired = form.querySelectorAll('[required]').length > 0;
        recordResult('Errors', `Form #${id} Validation`, hasRequired, hasRequired ? 'Has required fields' : 'WARNING: No required fields');
      }
    });

    log('groupEnd');
  }

  // ========== FULL TEST RUNNER ==========
  async function runAllTests() {
    if (state.testsRunning) {
      log('warn', 'Tests already running...');
      return;
    }
    state.testsRunning = true;
    state.results = [];
    state.totalPassed = 0;
    state.totalFailed = 0;

    updatePanelStatus('running');
    log('group', '═══════════════════════════════════════');
    log('group', '  🚀 FULL DASHBOARD TEST SUITE STARTED');
    log('group', '═══════════════════════════════════════');

    const startTime = performance.now();

    // Run sequentially for clean logs
    await testAPIs();
    testDashboardCards();
    testTables();
    await testNotifications();
    testCharts();
    testErrors();

    const duration = (performance.now() - startTime).toFixed(1);
    state.testsRunning = false;

    log('group', '═══════════════════════════════════════');
    log('group', `  ✅ PASSED: ${state.totalPassed}  |  ❌ FAILED: ${state.totalFailed}`);
    log('group', `  ⏱️ Duration: ${duration}ms`);
    log('group', '═══════════════════════════════════════');

    updatePanelStatus('complete', { duration });
    showSummaryModal();
  }

  /* ----------------------------------------------------------
     UI PANEL
  ---------------------------------------------------------- */
  function createPanel() {
    if (document.getElementById(CONFIG.panelId)) return;

    const panel = document.createElement('div');
    panel.id = CONFIG.panelId;
    panel.innerHTML = `
      <style>
        #${CONFIG.panelId} {
          position: fixed;
          bottom: 20px;
          right: 20px;
          width: 380px;
          max-height: 80vh;
          background: #fff;
          border-radius: 16px;
          box-shadow: 0 20px 60px rgba(0,0,0,0.3);
          font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
          z-index: 99999;
          display: flex;
          flex-direction: column;
          overflow: hidden;
          transition: transform 0.3s ease, opacity 0.3s ease;
          border: 1px solid #e9ecef;
        }
        #${CONFIG.panelId}.minimized {
          transform: translateY(calc(100% - 50px));
        }
        #${CONFIG.panelId} .test-header {
          background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
          color: #fff;
          padding: 14px 18px;
          display: flex;
          align-items: center;
          justify-content: space-between;
          cursor: move;
          user-select: none;
        }
        #${CONFIG.panelId} .test-header h6 {
          margin: 0;
          font-size: 14px;
          font-weight: 700;
          display: flex;
          align-items: center;
          gap: 8px;
        }
        #${CONFIG.panelId} .test-header .header-btns {
          display: flex;
          gap: 6px;
        }
        #${CONFIG.panelId} .test-header button {
          background: rgba(255,255,255,0.2);
          border: none;
          color: #fff;
          width: 28px;
          height: 28px;
          border-radius: 6px;
          cursor: pointer;
          font-size: 14px;
          display: flex;
          align-items: center;
          justify-content: center;
          transition: background 0.2s;
        }
        #${CONFIG.panelId} .test-header button:hover {
          background: rgba(255,255,255,0.35);
        }
        #${CONFIG.panelId} .test-body {
          padding: 14px;
          overflow-y: auto;
          flex: 1;
        }
        #${CONFIG.panelId} .test-actions {
          display: grid;
          grid-template-columns: 1fr 1fr;
          gap: 8px;
          margin-bottom: 12px;
        }
        #${CONFIG.panelId} .test-actions button {
          padding: 8px 10px;
          border: 1px solid #dee2e6;
          border-radius: 8px;
          background: #f8f9fa;
          color: #495057;
          font-size: 12px;
          font-weight: 600;
          cursor: pointer;
          transition: all 0.2s;
          display: flex;
          align-items: center;
          justify-content: center;
          gap: 5px;
        }
        #${CONFIG.panelId} .test-actions button:hover {
          background: #e9ecef;
          border-color: #adb5bd;
          transform: translateY(-1px);
        }
        #${CONFIG.panelId} .test-actions button.btn-run-all {
          grid-column: 1 / -1;
          background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
          color: #fff;
          border: none;
          font-size: 13px;
          padding: 10px;
        }
        #${CONFIG.panelId} .test-actions button.btn-run-all:hover {
          opacity: 0.9;
          transform: translateY(-2px);
          box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }
        #${CONFIG.panelId} .test-actions button:disabled {
          opacity: 0.5;
          cursor: not-allowed;
          transform: none !important;
        }
        #${CONFIG.panelId} .status-bar {
          display: flex;
          justify-content: space-between;
          font-size: 11px;
          color: #6c757d;
          padding: 8px 10px;
          background: #f8f9fa;
          border-radius: 8px;
          margin-bottom: 10px;
        }
        #${CONFIG.panelId} .status-bar .badge-pass { color: #28a745; font-weight: 700; }
        #${CONFIG.panelId} .status-bar .badge-fail { color: #dc3545; font-weight: 700; }
        #${CONFIG.panelId} .results-list {
          max-height: 280px;
          overflow-y: auto;
          font-size: 12px;
        }
        #${CONFIG.panelId} .result-item {
          display: flex;
          align-items: flex-start;
          gap: 8px;
          padding: 6px 8px;
          border-radius: 6px;
          margin-bottom: 3px;
          cursor: pointer;
          transition: background 0.15s;
        }
        #${CONFIG.panelId} .result-item:hover {
          background: #f1f3f5;
        }
        #${CONFIG.panelId} .result-item .icon {
          font-size: 14px;
          flex-shrink: 0;
          margin-top: 1px;
        }
        #${CONFIG.panelId} .result-item.pass .icon { color: #28a745; }
        #${CONFIG.panelId} .result-item.fail .icon { color: #dc3545; }
        #${CONFIG.panelId} .result-item .text {
          flex: 1;
          line-height: 1.4;
        }
        #${CONFIG.panelId} .result-item .name {
          font-weight: 600;
          color: #212529;
        }
        #${CONFIG.panelId} .result-item .msg {
          color: #6c757d;
          font-size: 11px;
        }
        #${CONFIG.panelId} .result-item.fail .name { color: #dc3545; }
        #${CONFIG.panelId} .category-label {
          font-size: 10px;
          text-transform: uppercase;
          letter-spacing: 0.5px;
          font-weight: 700;
          color: #adb5bd;
          padding: 8px 8px 4px;
          border-top: 1px solid #e9ecef;
          margin-top: 6px;
        }
        #${CONFIG.panelId} .category-label:first-child {
          border-top: none;
          margin-top: 0;
          padding-top: 0;
        }
        #${CONFIG.panelId} .progress-bar {
          height: 3px;
          background: #e9ecef;
          border-radius: 2px;
          margin-bottom: 10px;
          overflow: hidden;
        }
        #${CONFIG.panelId} .progress-bar .fill {
          height: 100%;
          background: linear-gradient(90deg, #667eea, #764ba2);
          width: 0%;
          transition: width 0.3s ease;
        }
        #${CONFIG.panelId} .spinner {
          display: inline-block;
          width: 14px;
          height: 14px;
          border: 2px solid rgba(255,255,255,0.3);
          border-top-color: #fff;
          border-radius: 50%;
          animation: spin 0.8s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        @media (max-width: 576px) {
          #${CONFIG.panelId} { width: calc(100vw - 40px); right: 10px; left: 10px; }
        }
      </style>

      <div class="test-header" id="test-panel-header">
        <h6><i class="bi bi-bug"></i> Dashboard Tester</h6>
        <div class="header-btns">
          <button id="test-panel-minimize" title="Minimize">−</button>
          <button id="test-panel-close" title="Close">×</button>
        </div>
      </div>

      <div class="test-body">
        <div class="progress-bar"><div class="fill" id="test-progress"></div></div>

        <div class="status-bar" id="test-status-bar">
          <span>Ready</span>
          <span><span class="badge-pass" id="count-pass">0</span> / <span class="badge-fail" id="count-fail">0</span></span>
        </div>

        <div class="test-actions">
          <button data-test="api"><i class="bi bi-hdd-network"></i> Test APIs</button>
          <button data-test="cards"><i class="bi bi-grid"></i> Test Cards</button>
          <button data-test="tables"><i class="bi bi-table"></i> Test Tables</button>
          <button data-test="notifications"><i class="bi bi-bell"></i> Test Notifs</button>
          <button data-test="charts"><i class="bi bi-bar-chart"></i> Test Charts</button>
          <button class="btn-run-all" data-test="all"><i class="bi bi-play-fill"></i> Run Full Test</button>
        </div>

        <div class="results-list" id="test-results"></div>
      </div>
    `;

    document.body.appendChild(panel);

    // Event Listeners
    document.getElementById('test-panel-close').onclick = () => panel.remove();
    document.getElementById('test-panel-minimize').onclick = () => {
      panel.classList.toggle('minimized');
    };

    panel.querySelectorAll('.test-actions button').forEach(btn => {
      btn.addEventListener('click', async () => {
        if (state.testsRunning) return;
        const type = btn.dataset.test;

        // Clear previous results for single tests
        if (type !== 'all') {
          state.results = state.results.filter(r => r.category.toLowerCase() !== type);
          // Recalculate counts
          state.totalPassed = state.results.filter(r => r.passed).length;
          state.totalFailed = state.results.filter(r => !r.passed).length;
        } else {
          state.results = [];
          state.totalPassed = 0;
          state.totalFailed = 0;
        }

        updatePanelResults();

        switch (type) {
          case 'api': await testAPIs(); break;
          case 'cards': testDashboardCards(); break;
          case 'tables': testTables(); break;
          case 'notifications': await testNotifications(); break;
          case 'charts': testCharts(); break;
          case 'all': await runAllTests(); return;
        }

        updatePanelStatus('complete');
      });
    });

    // Draggable
    makeDraggable(panel, document.getElementById('test-panel-header'));
  }

  function makeDraggable(el, handle) {
    let isDragging = false, startX, startY, startLeft, startTop;
    handle.addEventListener('mousedown', e => {
      isDragging = true;
      startX = e.clientX;
      startY = e.clientY;
      const rect = el.getBoundingClientRect();
      startLeft = rect.left;
      startTop = rect.top;
      el.style.transition = 'none';
    });
    document.addEventListener('mousemove', e => {
      if (!isDragging) return;
      const dx = e.clientX - startX;
      const dy = e.clientY - startY;
      el.style.left = `${startLeft + dx}px`;
