// AI Matches - Real-time Dashboard
function escapeHtml(value) {
  return String(value ?? '').replace(/[&<>"']/g, ch => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;'
  }[ch]));
}

async function loadMatches() {
  try {
    const res = await fetch('../admin/api/getAIMatches.php');
    const data = await res.json();
    if (data.status === 'success') {
      const tbody = document.getElementById('matchesTable');
      tbody.innerHTML = data.data.map(m => `
        <tr>
          <td>#M${m.match_id}</td>
          <td>${escapeHtml(m.missing_name)}</td>
          <td>#F${m.found_id}</td>
          <td>${m.match_percentage}%</td>
          <td>${m.suggested_by}</td>
          <td><span class="badge bg-${m.status === 'pending' ? 'warning' : m.status === 'confirmed' ? 'success' : 'danger'}">${m.status}</span></td>
          <td>
            <i class="bi bi-check2-circle text-success" onclick="approveMatch(${m.match_id})" title="Approve"></i>
            <i class="bi bi-x-circle text-danger" onclick="rejectMatch(${m.match_id})" title="Reject"></i>
          </td>
        </tr>
      `).join('') || '<tr><td colspan="7">No matches</td></tr>';
    }
  } catch (e) {
    console.error('Load matches error', e);
  }
}

setInterval(loadMatches, 5000); // Auto-refresh
loadMatches();
