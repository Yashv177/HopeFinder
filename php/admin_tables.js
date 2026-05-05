// Admin Dashboard Tables - Dynamic Loading with Pagination & Search
// Compatible with get_users.php, get_police.php, get_reports.php

const PAGE_SIZE = 10;
const API_BASE = './admin/api/';

let currentTables = {};

// Render table rows
function renderTableRows(containerId, data) {
  const container = document.getElementById(containerId);
  container.innerHTML = '';

  if (!data || data.length === 0) {
    container.innerHTML = '<tr><td colspan="100%" class="text-center text-muted py-4">No data found</td></tr>';
    return;
  }

  data.forEach(row => {
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td>${row.user_id || row.police_id || row.report_id || ''}</td>
      <td>${row.fullname || row.missing_name || ''}</td>
      <td>${row.email || row.last_seen_location || ''}</td>
      <td>${row.location || new Date(row.created_at).toLocaleDateString() || ''}</td>
      <td>${row.status_label || row.status || ''}</td>
      <td>
        <button class="btn btn-sm btn-outline-primary me-1" onclick="viewRecord('${row.user_id || row.police_id || row.report_id}')">
          <i class="bi bi-eye"></i>
        </button>
        <button class="btn btn-sm btn-outline-danger" onclick="deleteRecord('${row.user_id || row.police_id || row.report_id}')">
          <i class="bi bi-trash"></i>
        </button>
      </td>
    `;
    container.appendChild(tr);
  });
}

// Render pagination
function renderPagination(containerId, pagination, apiEndpoint, searchTerm = '') {
  const container = document.getElementById(containerId);
  if (!container) {
    console.error('Pagination container not found:', containerId);
    return;
  }
  container.innerHTML = '';

  if (pagination.total_pages <= 1) return;

  const currentPage = pagination.current_page;

  const currentPage = pagination.current_page;
  
  // Previous button
  const prevBtn = document.createElement('li');
  prevBtn.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
  prevBtn.innerHTML = `<a class="page-link" href="#" onclick="loadTable('${apiEndpoint}', ${currentPage - 1}, '${searchTerm}');return false;">Previous</a>`;
  container.appendChild(prevBtn);

  // Page numbers (show 5 pages max)
  const startPage = Math.max(1, currentPage - 2);
  const endPage = Math.min(pagination.total_pages, currentPage + 2);

  for (let i = startPage; i <= endPage; i++) {
    const li = document.createElement('li');
    li.className = `page-item ${i === currentPage ? 'active' : ''}`;
    li.innerHTML = `<a class="page-link" href="#" onclick="loadTable('${apiEndpoint}', ${i}, '${searchTerm}');return false;">${i}</a>`;
    container.appendChild(li);
  }

  // Next button
  const nextBtn = document.createElement('li');
  nextBtn.className = `page-item ${currentPage === pagination.total_pages ? 'disabled' : ''}`;
  nextBtn.innerHTML = `<a class="page-link" href="#" onclick="loadTable('${apiEndpoint}', ${currentPage + 1}, '${searchTerm}');return false;">Next</a>`;
  container.appendChild(nextBtn);
  
  // Info
  const info = document.createElement('li');
  info.className = 'page-item disabled';
  info.innerHTML = `<span class="page-link">${pagination.total_records} total records</span>`;
  container.appendChild(info);
}

// Load table data
async function loadTable(apiEndpoint, page = 1, search = '') {
  const url = `${API_BASE}${apiEndpoint}?page=${page}&search=${encodeURIComponent(search)}`;
  
  try {
    const response = await fetch(url);
    const result = await response.json();
    
    if (result.status === 'success') {
      // Update table based on endpoint
      if (apiEndpoint === 'get_users.php') {
        renderTableRows('usersTable', result.data);
        renderPagination('usersPagination', result.pagination, apiEndpoint, search);
      } else if (apiEndpoint === 'get_police.php') {
        renderTableRows('policeTable', result.data);
        renderPagination('policePagination', result.pagination, apiEndpoint, search);
      } else if (apiEndpoint === 'get_reports.php') {
        renderTableRows('reportsTable', result.data);
        renderPagination('reportsPagination', result.pagination, apiEndpoint, search);
      }
    }
  } catch (error) {
    console.error('Load error:', error);
  }
}

// Search handlers
function setupSearch(tableType) {
  const searchInput = document.getElementById(`${tableType}Search`);
  let timeout;
  
  searchInput.addEventListener('input', (e) => {
    clearTimeout(timeout);
    timeout = setTimeout(() => {
      loadTable(`get_${tableType}.php`, 1, e.target.value);
    }, 500);
  });
}

// View record placeholder
function viewRecord(id) {
  alert(`View record ID: ${id}`);
}

// Delete record placeholder
function deleteRecord(id) {
  if (confirm('Delete this record?')) {
    alert(`Delete record ID: ${id}`);
  }
}

// Initialize all tables on page load
document.addEventListener('DOMContentLoaded', () => {
  loadTable('get_users.php');
  loadTable('get_police.php'); 
  loadTable('get_reports.php');
  
  setupSearch('user');
  setupSearch('police');
  setupSearch('report');
});

// Export pagination HTML helper
function getPaginationHTML(id) {
  return `<nav>
    <ul class="pagination justify-content-center" id="${id}">
    </ul>
  </nav>`;
}

