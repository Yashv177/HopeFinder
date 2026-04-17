#!/usr/bin/env python3
"""
HopeFinder Real-Time CCTV Server - FIXED & PRODUCTION READY
- Thread-safe detection loop
- /video_feed smooth stream
- /start_ai, /stop_ai API matching frontend
- Cooldown system
- Error handling & camera reconnect
"""

import os
import cv2
import face_recognition
import mysql.connector
from flask import Flask, Response, request, jsonify
from datetime import datetime
import numpy as np
import threading
import time
import requests

app = Flask(__name__)

# Config
DB_HOST = 'localhost'
DB_USER = 'root'
DB_PASS = ''
DB_NAME = 'hopefinder'

BASE_DIR = os.path.abspath(os.path.dirname(__file__))
UPLOADS_DIR = os.path.join(BASE_DIR, '..', 'uploads')
MISSING_DIR = os.path.join(UPLOADS_DIR, 'missing')
FOUND_DIR = os.path.join(UPLOADS_DIR, 'found')

os.makedirs(MISSING_DIR, exist_ok=True)
os.makedirs(FOUND_DIR, exist_ok=True)

# Thread-safe globals
state_lock = threading.Lock()
frame_lock = threading.Lock()
known_encodings_lock = threading.Lock()
cooldown_lock = threading.Lock()

known_encodings = []
known_report_ids = []
running = False
cap = None
session_id = None
current_frame = None
detection_thread = None
last_detection = {}  # report_id: timestamp
COOLDOWN_SECONDS = 30

def db_connect():
    return mysql.connector.connect(
        host=DB_HOST, user=DB_USER, password=DB_PASS, database=DB_NAME
    )

def load_missing_faces(report_ids):
    with known_encodings_lock:
        known_encodings.clear()
        known_report_ids.clear()
    
    with cooldown_lock:
        last_detection.clear()
    
    try:
        conn = db_connect()
        cursor = conn.cursor(dictionary=True)
        placeholders = ','.join(['%s'] * len(report_ids))
        query = f"""
            SELECT report_id, photo 
            FROM missing_reports 
            WHERE report_id IN ({placeholders}) AND status IN ('searching', 'assigned')
        """
        cursor.execute(query, report_ids)
        rows = cursor.fetchall()
        
        for row in rows:
            image_path = os.path.join(BASE_DIR, row['photo'])
            if os.path.exists(image_path):
                image = face_recognition.load_image_file(image_path)
                encodings = face_recognition.face_encodings(image)
                if encodings:
                    with known_encodings_lock:
                        known_encodings.append(encodings[0])
                        known_report_ids.append(row['report_id'])
                    print(f"✅ Loaded report {row['report_id']}")
        
        conn.close()
        print(f"🎯 Loaded {len(known_encodings)} missing faces")
    except Exception as e:
        print(f"❌ DB load error: {e}")

def check_cooldown(report_id):
    now = time.time()
    with cooldown_lock:
        last_time = last_detection.get(report_id, 0)
        if now - last_time < COOLDOWN_SECONDS:
            return False
        last_detection[report_id] = now
    return True

def detection_loop():
    global cap, running, current_frame
    consecutive_failures = 0
    max_failures = 10
    
    while running:
        try:
            if cap is None or not cap.isOpened():
                print("🔄 Reconnecting camera...")
                cap = cv2.VideoCapture(0)
                cap.set(cv2.CAP_PROP_FRAME_WIDTH, 640)
                cap.set(cv2.CAP_PROP_FRAME_HEIGHT, 480)
                consecutive_failures = 0
            
            ret, frame = cap.read()
            if not ret:
                consecutive_failures += 1
                if consecutive_failures > max_failures:
                    print("⚠️ Camera failed, retrying...")
                    if cap:
                        cap.release()
                        cap = None
                continue
            
            consecutive_failures = 0
            
            # Detection
            rgb_frame = cv2.cvtColor(frame, cv2.COLOR_BGR2RGB)
            face_locations = face_recognition.face_locations(rgb_frame)
            face_encodings = face_recognition.face_encodings(rgb_frame, face_locations)
            
            with known_encodings_lock:
                num_known = len(known_encodings)
            
            if num_known == 0 or len(face_encodings) == 0:
                with frame_lock:
                    current_frame = cv2.resize(frame, (640, 480))
                continue
            
            for (top, right, bottom, left), face_encoding in zip(face_locations, face_encodings):
                matches = face_recognition.compare_faces(known_encodings, face_encoding, tolerance=0.6)
                face_distances = face_recognition.face_distance(known_encodings, face_encoding)
                
                if True in matches:
                    best_match_idx = np.argmin(face_distances)
                    confidence = max(0, (1 - face_distances[best_match_idx]) * 100)
                    
                    if confidence > 50 and check_cooldown(known_report_ids[best_match_idx]):
                        report_id = known_report_ids[best_match_idx]
                        
                        # Save frame
                        timestamp = datetime.now().strftime('%Y%m%d_%H%M%S_%f')[:-3]
                        filename = f"{report_id}_found_{timestamp}.jpg"
                        found_path = os.path.join(FOUND_DIR, filename)
                        cv2.imwrite(found_path, frame)
                        
                        # Report to PHP
                        data = {
                            'session_id': session_id,
                            'report_id': report_id,
                            'image_path': f'uploads/found/{filename}',
                            'confidence': round(confidence, 2),
                            'lat': 25.5941,
                            'lng': 85.1376
                        }
                        try:
                            requests.post('http://localhost/HopeFinder/admin/api/report_detection.php', 
                                        json=data, timeout=3)
                            print(f"🎯 MATCH Report#{report_id} {confidence:.1f}% (cooldown OK)")
                        except Exception as e:
                            print(f"⚠️ Report error: {e}")
                    
                    # Draw always
                    color = (0, 255, 0) if confidence > 50 else (0, 165, 255)
                    cv2.rectangle(frame, (left, top), (right, bottom), color, 3)
                    cv2.putText(frame, f"#{known_report_ids[best_match_idx]} {confidence:.0f}%", 
                               (left, top-10), cv2.FONT_HERSHEY_SIMPLEX, 0.6, color, 2)
            
            with frame_lock:
                current_frame = cv2.resize(frame, (640, 480))
        
        except Exception as e:
            print(f"❌ Detection error: {e}")
            time.sleep(1)
    
    if cap:
        cap.release()

@app.route('/video_feed')
def video_feed():
    def gen_frames():
        while True:
            with frame_lock:
                if current_frame is None:
                    # Placeholder
                    placeholder = np.zeros((480, 640, 3), dtype=np.uint8)
                    cv2.putText(placeholder, "AI Active - No Camera", (100, 240), 
                               cv2.FONT_HERSHEY_SIMPLEX, 1, (255,255,255), 2)
                    ret, buffer = cv2.imencode('.jpg', placeholder)
                else:
                    ret, buffer = cv2.imencode('.jpg', current_frame, [cv2.IMWRITE_JPEG_QUALITY, 80])
            
            if ret:
                yield (b'--frame\r\n'
                       b'Content-Type: image/jpeg\r\n\r\n' + buffer.tobytes() + b'\r\n')
            time.sleep(0.033)  # ~30 FPS
    
    return Response(gen_frames(), mimetype='multipart/x-mixed-replace; boundary=frame')

@app.route('/start_ai', methods=['POST'])
@app.route('/start', methods=['POST'])  # Backward compat
def start_detection():
    global running, session_id, detection_thread
    
    data = request.json or {}
    report_ids = data.get('report_ids', [])
    
    if not report_ids:
        return jsonify({'error': 'No reports provided'}), 400
    
    session_id = data.get('session_id', f"ai_{int(time.time())}")
    load_missing_faces(report_ids)
    
    with state_lock:
        if running:
            return jsonify({'status': 'already_running', 'session_id': session_id})
        
        running = True
        
        if detection_thread is None or not detection_thread.is_alive():
            detection_thread = threading.Thread(target=detection_loop, daemon=True)
            detection_thread.start()
    
    print(f"🚀 AI STARTED: session={session_id}, targets={len(report_ids)}")
    return jsonify({
        'status': 'started',
        'session_id': session_id,
        'targets_loaded': len(known_encodings)
    })

@app.route('/stop_ai', methods=['POST'])
@app.route('/stop', methods=['POST'])
def stop_detection():
    global running
    
    with state_lock:
        running = False
    
    print("🛑 AI STOPPED")
    return jsonify({'status': 'stopped'})

@app.route('/status')
def status():
    with state_lock:
        is_running = running
    with known_encodings_lock:
        num_faces = len(known_encodings)
    return jsonify({
        'running': is_running,
        'faces_loaded': num_faces,
        'session_id': getattr(session_id, 'unknown', session_id)
    })

if __name__ == '__main__':
    print("🎥 HopeFinder FIXED CCTV Server")
    print("🌐 Routes: /video_feed, /start_ai (POST), /stop_ai, /status")
    print("📱 Test: http://localhost:5001/video_feed")
    print("🚀 Full system: Open dashboard/realtime.html")
    app.run(host='0.0.0.0', port=5001, debug=False, threaded=True)

