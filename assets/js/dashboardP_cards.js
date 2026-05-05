document.addEventListener("DOMContentLoaded", async function () {
  try {
    const res = await fetch("/HopeFinder/police/api/get_dashboard_stats.php");
    const data = await res.json();

    console.log("FINAL CARD DATA:", data);

    // FORCE UPDATE (innerHTML use karo)
    document.getElementById("total-reports").innerHTML = data.total_reports;
    document.getElementById("pending-verification").innerHTML = data.pending_cases;
    document.getElementById("verified-cases").innerHTML = data.verified_cases;
    document.getElementById("matches-found").innerHTML = data.found_cases;

  } catch (err) {
    console.log("Card error:", err);
  }
});