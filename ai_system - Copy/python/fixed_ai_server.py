#!/usr/bin/env python3
"""HopeFinder AI Server - FINAL VERSION (Detection + Match + Save)"""

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

# Paths
ROOT_DIR = Path(__file__).resolve().parent.parent.parent
UPLOADS_DIR = ROOT_DIR / 'uploads'
AI_FOUND_DIR = UPLOADS_DIR / 'ai_found'
AI_FOUND_DIR.mkdir(parents=True, exist_ok=True)

# API
GET_MISSING_URL = 'http://127.0.0.1/HopeFinder/ai_system/backend/api/get_missing.php'

# Config
FRAME_W, FRAME_H = 640, 480
SKIP_FRAMES = 2
TOLERANCE = 0.6

# Logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

app = Flask(__name__)

# Globals
stop_event = threading.Event()
target_encodings = []
frame_queue = queue.Queue(maxsize=3)
latest_frame = None
latest_annotated = None

# ------------------ LOAD TARGETS ------------------

def load_selected_targets(selected_ids):
    if not selected_ids:
        return 0

    try:
        resp = requests.get(GET_MISSING_URL, params={'ids': selected_ids})
        data = resp.json()

        encodings = []

        for person in data:
            rid = person.get('id')
            photo_rel = person.get('photo')

            if not photo_rel:
                continue

            # FIXED PATH
            full_path = ROOT_DIR / photo_rel

            if not full_path.exists():
                fallback = UPLOADS_DIR / 'missing_persons' / Path(photo_rel).name
                if fallback.exists():
                    full_path = fallback
                else:
                    logger.error(f"❌ Image not found: {photo_rel}")
                    continue

            logger.info(f"✅ Loading: {full_path}")

            try:
                img = face_recognition.load_image_file(str(full_path))
                enc = face_recognition.face_encodings(img)

                if enc:
                    encodings.append(enc[0])
                    logger.info(f"🔥 Loaded ID: {rid}")
                else:
                    logger.warning(f"No face in image {rid}")

            except Exception as e:
                logger.error(f"Encoding error {rid}: {e}")

        global target_encodings
        target_encodings = encodings

        logger.info(f"TOTAL TARGETS: {len(encodings)}")
        return len(encodings)

    except Exception as e:
        logger.error(f"Load error: {e}")
        return 0

# ------------------ CAMERA ------------------

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

        time.sleep(0.01)

    cap.release()

# ------------------ DETECTION ------------------

def detection_thread():
    frame_count = 0

    while not stop_event.is_set():
        try:
            frame = frame_queue.get(timeout=0.1)
        except:
            continue

        frame_count += 1

        if frame_count % (SKIP_FRAMES + 1) != 0:
            continue

        annotated = frame.copy()

        if len(target_encodings) == 0:
            cv2.putText(annotated, "AI Ready - No Targets", (50, 50),
                        cv2.FONT_HERSHEY_SIMPLEX, 1, (0, 0, 255), 2)
        else:
            rgb = cv2.cvtColor(frame, cv2.COLOR_BGR2RGB)

            face_locations = face_recognition.face_locations(rgb)
            face_encodings = face_recognition.face_encodings(rgb, face_locations)

            for (top, right, bottom, left), face_enc in zip(face_locations, face_encodings):

                matches = face_recognition.compare_faces(target_encodings, face_enc, TOLERANCE)

                if True in matches:
                    # DRAW BOX
                    cv2.rectangle(annotated, (left, top), (right, bottom), (0, 255, 0), 2)
                    cv2.putText(annotated, "MATCH FOUND", (left, top - 10),
                                cv2.FONT_HERSHEY_SIMPLEX, 0.7, (0, 255, 0), 2)

                    # SAVE IMAGE
                    timestamp = int(time.time())
                    filename = f"match_{timestamp}.jpg"
                    save_path = str(AI_FOUND_DIR / filename)

                    cv2.imwrite(save_path, frame)
                    logger.info(f"🔥 SAVED: {save_path}")

                else:
                    cv2.rectangle(annotated, (left, top), (right, bottom), (0, 0, 255), 2)

            cv2.putText(annotated, f"Scanning {len(target_encodings)} targets", (50, 50),
                        cv2.FONT_HERSHEY_SIMPLEX, 1, (0, 255, 0), 2)

        global latest_annotated
        latest_annotated = annotated

# ------------------ WORKERS ------------------

def start_workers():
    threading.Thread(target=camera_thread, daemon=True).start()
    threading.Thread(target=detection_thread, daemon=True).start()

# ------------------ ROUTES ------------------

@app.route('/start_detection', methods=['POST'])
def start_detection():
    stop_event.clear()

    data = request.json
    selected_ids = data.get('selected_ids', [])

    count = load_selected_targets(selected_ids)
    start_workers()

    return jsonify({'status': 'success', 'targets': count})

@app.route('/stop_detection', methods=['POST'])
def stop_detection():
    stop_event.set()
    return jsonify({'status': 'stopped'})

@app.route('/video_feed')
def video_feed():
    def gen():
        while not stop_event.is_set():
            frame = latest_annotated if latest_annotated is not None else latest_frame

            if frame is None:
                frame = np.zeros((FRAME_H, FRAME_W, 3), dtype=np.uint8)

            _, buffer = cv2.imencode('.jpg', frame)
            yield (b'--frame\r\nContent-Type: image/jpeg\r\n\r\n' +
                   buffer.tobytes() + b'\r\n')

            time.sleep(1/30)

    return Response(gen(), mimetype='multipart/x-mixed-replace; boundary=frame')

# ------------------ MAIN ------------------

if __name__ == '__main__':
    print("🚀 AI SERVER RUNNING (FINAL)...")
    app.run(host='0.0.0.0', port=5001, debug=False)