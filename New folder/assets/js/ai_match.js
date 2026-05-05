document.addEventListener("DOMContentLoaded", loadMatches);

async function loadMatches() {
  try {
    const res = await fetch("/HopeFinder/police/api/get_found_cases.php");
    const data = await res.json();

    console.log("AI DATA:", data);

    const table = document.querySelector("#ai-suggestions table tbody");

    if (!table) {
      console.log("Table not found ❌");
      return;
    }

    // REMOVE dummy row
    table.innerHTML = "";

    if (!data || data.length === 0) {
      table.innerHTML = `<tr><td colspan="6">No AI match suggestions yet</td></tr>`;
      return;
    }

    // REMOVE DUPLICATES (important)
    const unique = {};
    data.forEach(item => {
      if (!unique[item.report_id]) {
        unique[item.report_id] = item;
      }
    });

    const cleanData = Object.values(unique);

    // INSERT DATA
    cleanData.forEach((item, index) => {
      table.innerHTML += `
        <tr>
          <td>#M${1000 + index}</td>
          <td>${item.missing_name}</td>
          <td>#${item.report_id}</td>
          <td style="color:${item.confidence > 85 ? 'green' : 'orange'}; font-weight:bold;">
            ${item.confidence}%
          </td>
          <td>AI</td>
          <td class="table-actions">
            <i class="bi bi-check2-square text-success" title="Approve"></i>
            <i class="bi bi-x-square text-danger" title="Reject"></i>
            <a href="/HopeFinder/police/api/generate_report_pdf.php?report_id=${item.report_id}" target="_blank">
              <i class="bi bi-eye text-info" title="View"></i>
            </a>
          </td>
        </tr>
      `;
    });

  } catch (err) {
    console.log("AI error:", err);
  }
}