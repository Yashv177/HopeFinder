<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>AI Match System</title>

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: #f4f6f9;
        }

        video {
            height: 400px;
            object-fit: cover;
            border-radius: 10px;
        }

        .card {
            border-radius: 12px;
        }
    </style>
</head>

<body>

    <div class="container mt-4">

        <h2 class="mb-3">🤖 AI Face Matching System</h2>

        <div class="row">

            <!-- CAMERA -->
            <div class="col-md-8">
                <div class="card p-3">
                    <h5>📹 Camera Feed</h5>
                    <video id="cameraFeed" class="w-100" autoplay muted playsinline></video>
                </div>
            </div>

            <!-- CONTROL -->
            <div class="col-md-4">
                <div class="card p-3">

                    <button class="btn btn-success w-100 mb-2" onclick="startAI()">▶ Start AI</button>
                    <button class="btn btn-danger w-100 mb-2" onclick="stopAI()">⏹ Stop AI</button>

                    <p>Status:
                        <span id="status" class="badge bg-secondary">Stopped</span>
                    </p>

                    <h6>👤 Select Missing Persons</h6>
                    <div id="personList">Loading...</div>

                </div>
            </div>

        </div>

        <!-- RESULTS -->
        <div class="card mt-4 p-3">
            <h5>🔍 Live Matches</h5>

            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Confidence</th>
                        <th>Location</th>
                        <th>Time</th>
                    </tr>
                </thead>

                <tbody id="matchTable"></tbody>
            </table>
        </div>

    </div>

    <script>

        // =========================
        // 📍 LOCATION
        // =========================
        let userLocation = { lat: null, lng: null };

        function getLocation() {
            return new Promise((resolve) => {
                navigator.geolocation.getCurrentPosition(
                    pos => {
                        userLocation.lat = pos.coords.latitude;
                        userLocation.lng = pos.coords.longitude;
                        console.log("📍 Location:", userLocation);
                        resolve();
                    },
                    err => {
                        console.log("❌ Location error:", err);
                        resolve();
                    }
                );
            });
        }

        // =========================
        // 🎥 CAMERA
        // =========================
        async function startCamera() {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ video: true });
                const video = document.getElementById("cameraFeed");
                video.srcObject = stream;
                await video.play();
            } catch (err) {
                alert("Camera access denied!");
            }
        }

        startCamera();
        getLocation();

        // =========================
        // 👤 LOAD PERSONS
        // =========================
        async function loadPersons() {
            try {
                const res = await fetch('/HopeFinder/admin/api/get_missing_persons.php');
                const data = await res.json();

                let html = "";

                if (!data || data.length === 0) {
                    html = "No missing persons found";
                } else {
                    data.forEach(p => {
                        html += `
<div class="d-flex align-items-center mb-2">
    <input type="checkbox" value="${p.report_id}" class="me-2">

    <img src="/HopeFinder/uploads/missing_persons/${p.photo}" 
         width="40" height="40" class="rounded me-2"
         onerror="this.src='https://via.placeholder.com/40'">

    <span>${p.missing_name}</span>
</div>`;
                    });
                }

                document.getElementById("personList").innerHTML = html;

            } catch (err) {
                console.log("❌ Error loading persons:", err);
            }
        }

        loadPersons();

        // =========================
        // ▶ START AI
        // =========================
        async function startAI() {

            let ids = [];

            document.querySelectorAll("#personList input:checked")
                .forEach(el => ids.push(el.value));

            if (ids.length === 0) {
                alert("⚠ Select at least 1 person");
                return;
            }

            try {
                await fetch("http://127.0.0.1:5001/update_targets", {
                    method: "POST",
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ report_ids: ids })
                });

                await fetch("http://127.0.0.1:5001/start_ai", {
                    method: "POST",
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ location: userLocation })
                });

                document.getElementById("status").innerText = "Running";
                document.getElementById("status").className = "badge bg-success";

            } catch (err) {
                alert("AI Server not running!");
            }
        }

        // =========================
        // ⏹ STOP AI
        // =========================
        async function stopAI() {
            await fetch("http://127.0.0.1:5001/stop_ai", { method: "POST" });

            document.getElementById("status").innerText = "Stopped";
            document.getElementById("status").className = "badge bg-secondary";
        }

        // =========================
        // 🔁 LOAD MATCHES
        // =========================
        async function loadMatches() {
            try {
                const res = await fetch('/HopeFinder/admin/api/get_matches.php');
                const data = await res.json();

                let html = "";

                if (!data || data.length === 0) {
                    html = "<tr><td colspan='5'>No matches yet</td></tr>";
                } else {
                    data.forEach(m => {
                        html += `
<tr>
<td>
<img src="/HopeFinder/uploads/ai_found/${m.image}" width="60"
     onerror="this.src='https://via.placeholder.com/60'">
</td>
<td>${m.name}</td>
<td>${m.confidence}%</td>
<td>
<a href="https://maps.google.com/?q=${m.lat},${m.lng}" target="_blank">📍 Map</a>
</td>
<td>${m.timestamp}</td>
</tr>`;
                    });
                }

                document.getElementById("matchTable").innerHTML = html;

            } catch (err) {
                console.log("Match error:", err);
            }
        }

        setInterval(loadMatches, 3000);
        loadMatches();

    </script>

</body>
</html>