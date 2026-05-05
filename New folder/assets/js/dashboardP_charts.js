document.addEventListener("DOMContentLoaded", async function () {

  // STEP 1: CREATE CHARTS FIRST
  createCharts();

  // STEP 2: LOAD DATA
  await loadCharts();
});


// ===== CREATE CHARTS =====
function createCharts() {

  // REPORTS TREND
  const ctx1 = document.getElementById("reportsChart");
  if (ctx1) {
    new Chart(ctx1, {
      type: "line",
      data: {
        labels: [],
        datasets: [{
          label: "Reports",
          data: [],
          borderWidth: 2,
          tension: 0.4
        }]
      }
    });
  }

  // STATUS CHART
  const ctx2 = document.getElementById("statusChart");
  if (ctx2) {
    new Chart(ctx2, {
      type: "doughnut",
      data: {
        labels: ["Pending", "Verified", "Found"],
        datasets: [{
          data: [0, 0, 0]
        }]
      }
    });
  }

  // FOUND VS MISSING
  const ctx3 = document.getElementById("foundVsMissingChart");
  if (ctx3) {
    new Chart(ctx3, {
      type: "bar",
      data: {
        labels: ["Missing", "Found"],
        datasets: [{
          data: [0, 0]
        }]
      }
    });
  }
}


// ===== LOAD DATA =====
async function loadCharts() {
  try {
    const res = await fetch("/HopeFinder/police/api/get_dashboard_stats.php");
    const data = await res.json();

    console.log("CHART DATA:", data);

    setTimeout(() => {

      // REPORTS TREND
      let reportsChart = Chart.getChart("reportsChart");
      if (reportsChart) {
        reportsChart.data.labels = data.trend_data.map(d => d.date);
        reportsChart.data.datasets[0].data = data.trend_data.map(d => Number(d.count));
        reportsChart.update();
      }

      // STATUS CHART
      let statusChart = Chart.getChart("statusChart");
      if (statusChart) {
        statusChart.data.datasets[0].data = [
          Number(data.status_data.pending),
          Number(data.status_data.verified),
          Number(data.status_data.found)
        ];
        statusChart.update();

        // small numbers below chart
        document.getElementById("pending-count").innerText = data.status_data.pending;
        document.getElementById("verified-count").innerText = data.status_data.verified;
        document.getElementById("matched-count").innerText = data.status_data.found;
      }

      // FOUND VS MISSING
      let compareChart = Chart.getChart("foundVsMissingChart");
      if (compareChart) {
        compareChart.data.datasets[0].data = [
          Number(data.comparison.missing),
          Number(data.comparison.found)
        ];
        compareChart.update();
      }

    }, 500);

  } catch (err) {
    console.log("Chart load error:", err);
  }
}