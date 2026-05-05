import cv2
import numpy as np
import face_recognition
import requests
import threading
import time
import queue
from flask import Flask, Response, request, jsonify
from pathlib import Path
import logging

ROOT_DIR = Path(__file__).resolve().parent.parent.parent
UPLOADS_DIR = ROOT_DIR / 'uploads'
AI_FOUND_DIR = UPLOADS_DIR / 'ai_found'
AI_FOUND_DIR.mkdir(parents=True, exist_ok=True)

GET_MISSING_URL = 'http://127.0.0.1/HopeFinder/ai_system/backend/api/get_missing.php'
SAVE_API = 'http://127.0.0.1/HopeFinder/ai_system/backend/api/save_detection.php'

FRAME_W, FRAME_H = 640, 480
SKIP_FRAMES = 2
TOLERANCE = 0.6

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

app = Flask(__name__)

stop_event = threading.Event()
target_encodings = []
frame_queue = queue.Queue(maxsize=3)
latest_frame = None
latest_annotated = None

# ✅ GLOBAL GPS STORAGE
current_location = {
    "latitude": None,
    "longitude": None
}

# ---------------- LOAD TARGETS ----------------
def load_selected_targets(selected_ids):
    global target_encodings
    target_encodings = []

    resp = requests.get(GET_MISSING_URL, params={'ids': selected_ids})
    data = resp.json()

    for person in data:
        rid = person['id']
        path = ROOT_DIR / person['photo']

        if not path.exists():
            continue

        img = face_recognition.load_image_file(str(path))
        enc = face_recognition.face_encodings(img)

        if enc:
            target_encodings.append({
                "id": rid,
                "encoding": enc[0]
            })

    return len(target_encodings)

# ---------------- CAMERA ----------------
def camera_thread():
    cap = cv2.VideoCapture(0)

    while not stop_event.is_set():
        ret, frame = cap.read()
        if not ret:
            continue

        try:
            frame_queue.put_nowait(frame)
        except:
            pass

        global latest_frame
        latest_frame = frame

    cap.release()

# ---------------- DETECTION ----------------
def detection_thread():
    while not stop_event.is_set():

        try:
            frame = frame_queue.get(timeout=0.1)
        except:
            continue

        annotated = frame.copy()

        enc_list = [t["encoding"] for t in target_encodings]
        ids_list = [t["id"] for t in target_encodings]

        rgb = cv2.cvtColor(frame, cv2.COLOR_BGR2RGB)

        faces = face_recognition.face_locations(rgb)
        encodings = face_recognition.face_encodings(rgb, faces)

        for (top, right, bottom, left), face_enc in zip(faces, encodings):

            matches = face_recognition.compare_faces(enc_list, face_enc, TOLERANCE)

            if True in matches:
                idx = matches.index(True)
                report_id = ids_list[idx]

                cv2.rectangle(annotated, (left, top), (right, bottom), (0,255,0),2)
                cv2.putText(annotated, f"ID {report_id}", (left, top-10),
                            cv2.FONT_HERSHEY_SIMPLEX, 0.7, (0,255,0),2)

                filename = f"match_{int(time.time())}.jpg"
                save_path = AI_FOUND_DIR / filename
                cv2.imwrite(str(save_path), frame)

                # ✅ SEND WITH GPS
                try:
                    requests.post(SAVE_API, json={
                        "report_id": report_id,
                        "confidence": 90,
                        "image_path": f"uploads/ai_found/{filename}",
                        "timestamp": time.strftime("%Y-%m-%d %H:%M:%S"),
                        "latitude": current_location["latitude"],
                        "longitude": current_location["longitude"]
                    })
                    logger.info(f"Saved detection with GPS for ID {report_id}")
                except Exception as e:
                    logger.error(e)

        global latest_annotated
        latest_annotated = annotated

# ---------------- ROUTES ----------------
@app.route('/start_detection', methods=['POST'])
def start_detection():
    stop_event.clear()

    data = request.json

    ids = data.get('report_ids', [])

    # ✅ STORE GPS
    current_location["latitude"] = data.get("latitude")
    current_location["longitude"] = data.get("longitude")

    print("📍 GPS RECEIVED:", current_location)

    load_selected_targets(ids)

    threading.Thread(target=camera_thread, daemon=True).start()
    threading.Thread(target=detection_thread, daemon=True).start()

    return jsonify({"status":"started"})

@app.route('/stop_detection', methods=['POST'])
def stop_detection():
    stop_event.set()
    return jsonify({"status":"stopped"})

@app.route('/video_feed')
def video_feed():
    def gen():
        while not stop_event.is_set():
            frame = latest_annotated if latest_annotated is not None else latest_frame

            if frame is None:
                frame = np.zeros((480,640,3), dtype=np.uint8)

            _, buffer = cv2.imencode('.jpg', frame)
            yield (b'--frame\r\nContent-Type: image/jpeg\r\n\r\n' +
                   buffer.tobytes() + b'\r\n')

    return Response(gen(), mimetype='multipart/x-mixed-replace; boundary=frame')

# ✅ HEALTH CHECK (for frontend)
@app.route('/health')
def health():
    return jsonify({"status": "ok", "running": not stop_event.is_set()})

if __name__ == '__main__':
    app.run(port=5001)