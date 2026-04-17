from flask import Flask, request, jsonify, Response
import threading
import cv2
import face_recognition
import os
import requests
import time
import logging
import numpy as np
from datetime import datetime
import threading
from collections import defaultdict
from flask import Flask, request, jsonify, Response

app = Flask(__name__)

# Config
class Config:
    BASE_DIR = os.path.abspath(os.path.join(os.path.dirname(__file__), '..'))
    MISSING_DIR = os.path.join(BASE_DIR, 'uploads', 'missing_persons')
    FOUND_DIR = os.path.join(BASE_DIR, 'uploads', 'ai_found')
    PHP_API_URL = "http://localhost/HopeFinder/admin/api/report_detection.php"
    CAMERA_INDEX = 0
    TOLERANCE = 0.6
    COOLDOWN_SEC = 10

config = Config()
os.makedirs(config.FOUND_DIR, exist_ok=True)
os.makedirs(config.MISSING_DIR, exist_ok=True)

logging.basicConfig(level=logging.INFO, format='%(asctime)s - %(levelname)s - %(message)s')
logger = logging.getLogger(__name__)

# =====================
# CONFIG
# =====================
PROJECT_ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), '..'))

MISSING_DIR = os.path.join(PROJECT_ROOT, './uploads/missing_persons')
FOUND_DIR = os.path.join(PROJECT_ROOT, './uploads/ai_found')

os.makedirs(FOUND_DIR, exist_ok=True)

PHP_API_URL = "http://localhost/HopeFinder/admin/api/report_detection.php"

# =====================
# GLOBAL STATE
# =====================
sessions = defaultdict(set)  # session_id -> set(report_ids)
active_sessions = set()
frame_lock = threading.Lock()
stop_event = threading.Event()

running = False
known_encodings = []
known_names = []
cooldowns = {}  # person_id -> timestamp

latest_frame = None
process_frame = None

# =====================
# LOAD FACES
# =====================
def load_faces(ids):
    global known_encodings, known_names

    known_encodings = []
    known_names = []

    for file in os.listdir(config.MISSING_DIR):
        if not file.endswith((".jpg", ".jpeg", ".png")):
            continue

        person_id = file.split("_")[0]

        if person_id in ids:
            path = os.path.join(config.MISSING_DIR, file)

            try:
                img = face_recognition.load_image_file(path)
                enc = face_recognition.face_encodings(img)
                if len(enc) > 0:
                    known_encodings.append(enc[0])
                    known_names.append(file)
            except Exception as e:
                logger.error(f"Failed to load face {file}: {e}")

    logger.info(f"✅ Loaded {len(known_encodings)} faces for IDs: {ids}")


# =====================
# CAMERA THREAD (SMOOTH)
# =====================
def camera_loop():
    global latest_frame, process_frame, running
    cap = None
    retry_count = 0
    max_retries = 5

    while running and retry_count < max_retries:
        try:
            cap = cv2.VideoCapture(config.CAMERA_INDEX, cv2.CAP_DSHOW)
            cap.set(cv2.CAP_PROP_FRAME_WIDTH, 640)
            cap.set(cv2.CAP_PROP_FRAME_HEIGHT, 480)
            if cap.isOpened():
                logger.info(f"Camera {config.CAMERA_INDEX} opened successfully")
                break
        except Exception as e:
            logger.error(f"Camera open failed: {e}")
        retry_count += 1
        time.sleep(1)

    if cap is None:
        logger.error("Failed to open camera after retries")
        return

    try:
        while running and not stop_event.wait(0.01):
            ret, frame = cap.read()
            if not ret:
                logger.warning("Failed to grab frame")
                continue

            with frame_lock:
                latest_frame = frame.copy()
                if int(time.time() * 10) % 5 == 0:
                    process_frame = frame.copy()
    finally:
        if cap:
            cap.release()
            logger.info("Camera released")

# =====================
# AI DETECTION THREAD
# =====================
def detection_loop():
    global process_frame

    while running and not stop_event.wait(0.05):
        with frame_lock:
            if process_frame is None:
                continue
            frame = process_frame.copy()

        small = cv2.resize(frame, (0, 0), fx=0.3, fy=0.3)
        rgb = cv2.cvtColor(small, cv2.COLOR_BGR2RGB)

        faces = face_recognition.face_locations(rgb, model="hog")
        encodings = face_recognition.face_encodings(rgb, faces)

        for (top, right, bottom, left), enc in zip(faces, encodings):
            if len(known_encodings) == 0:
                continue

            distances = face_recognition.face_distance(known_encodings, enc)
            min_dist = min(distances)
            if min_dist > config.TOLERANCE:
                continue

            idx = np.argmin(distances)
            person_id = known_names[idx].split("_")[0]
            now = time.time()

            if cooldowns.get(person_id, 0) + config.COOLDOWN_SEC > now:
                continue
            cooldowns[person_id] = now

            confidence = int((1 - min_dist) * 100)
            logger.info(f"🔥 DETECTED {person_id} ({confidence}%)")

            # Find matching session (simple: any active)
            session_id = list(active_sessions)[0] if active_sessions else 'default'

            filename = f"{person_id}_{int(now)}_{session_id}.jpg"
            save_path = os.path.join(config.FOUND_DIR, filename)
            relative_path = f"uploads/ai_found/{filename}"

            cv2.imwrite(save_path, frame)

            # API CALL
            api_data = {
                "report_id": person_id,
                "image_path": relative_path,
                "confidence": confidence,
                "session_id": session_id
            }
            data = request.json or {} if 'request' in globals() else {}
            api_data["location"] = data.get("location")

            try:
                requests.post(config.PHP_API_URL, json=api_data, timeout=3)
                logger.info(f"API reported {person_id}")
            except Exception as e:
                logger.error(f"API call failed: {e}")

# =====================
# VIDEO STREAM
# =====================
def generate_frames():
    global latest_frame

    while True:
        if latest_frame is None:
            time.sleep(0.05)
            continue

        ret, buffer = cv2.imencode('.jpg', latest_frame)
        frame = buffer.tobytes()

        yield (b'--frame\r\n'
               b'Content-Type: image/jpeg\r\n\r\n' + frame + b'\r\n')

@app.route('/video_feed')
def video_feed():
    session_id = request.args.get('session', 'default')
    logger.info(f"Stream requested for session: {session_id}")
    return Response(generate_frames(),
        mimetype='multipart/x-mixed-replace; boundary=frame')

# =====================
# NEW ENDPOINTS
# =====================
@app.route("/status", methods=["GET"])
def status():
    return jsonify({
        "running": running,
        "active_sessions": list(active_sessions),
        "known_faces": len(known_encodings)
    })

@app.route("/update_targets", methods=["POST"])
def update_targets():
    data = request.json or {}
    session_id = data.get("session_id", "default")
    report_ids = data.get("report_ids", [])
    sessions[session_id] = set(report_ids)
    active_sessions.add(session_id)
    load_faces(list(set.union(*sessions.values())))  # load all unique
    logger.info(f"Updated targets for {session_id}: {report_ids}")
    return jsonify({"status": "updated", "session_id": session_id})

@app.route("/logs", methods=["GET"])
def get_logs():
    session_id = request.args.get('session', None)
    # Simple recent log tail (in prod use log file)
    return jsonify({"logs": "Log endpoint ready - implement file tail"})

# =====================
# START AI
# =====================
@app.route("/start_ai", methods=["POST"])
def start_ai():
    global running, config.CAMERA_INDEX

    data = request.json or {}
    session_id = data.get("session_id", f"session_{int(time.time())}")
    ids = data.get("report_ids", [])
    camera_idx = data.get("camera_index", config.CAMERA_INDEX)
    config.CAMERA_INDEX = camera_idx  # override

    if not ids:
        return jsonify({"error": "No report_ids provided"}), 400

    sessions[session_id] = set(ids)
    active_sessions.add(session_id)
    all_ids = list(set.union(*sessions.values()))
    load_faces(all_ids)

    if not running:
        running = True
        threading.Thread(target=camera_loop, daemon=True).start()
        threading.Thread(target=detection_loop, daemon=True).start()
        logger.info(f"Started AI for session {session_id}")
    else:
        logger.info(f"Session {session_id} added to running AI")

    return jsonify({"status": "started", "session_id": session_id})

# =====================
# STOP AI
# =====================
@app.route("/stop_ai", methods=["POST"])
def stop_ai():
    global running
    data = request.json or {}
    session_id = data.get("session_id", None)

    if session_id:
        active_sessions.discard(session_id)
        sessions.pop(session_id, None)
        logger.info(f"Stopped session {session_id}")

    if not active_sessions:
        stop_event.set()
        running = False
        stop_event.clear()
        logger.info("All sessions stopped, AI shutdown")
        # Give threads time to exit
        time.sleep(1)

# =====================
# RUN
# =====================
if __name__ == "__main__":
    print("🚀 AI CCTV Server Running on http://localhost:5001")
    app.run(host='0.0.0.0', port=5001, debug=False)