#!/usr/bin/env python3
\"\"\"Real-Time CCTV Face Detection Server for HopeFinder\"\"\"

import os
import cv2
import face_recognition
import mysql.connector
from flask import Flask, Response, jsonify
from datetime import datetime
import numpy as np
import threading
import time
import requests
from dotenv import load_dotenv

load_dotenv()

app = Flask(__name__)

# Config
DB_HOST = os.getenv('DB_HOST', 'localhost')
DB_USER = os.getenv('DB_USER', 'root')
DB_PASS = os.getenv('DB_PASS', '')
DB_NAME = os.getenv('DB_NAME', 'hopefinder')

UPLOADS_DIR = os.path.abspath('../../uploads')
MISSING_DIR = os.path.join(UPLOADS_DIR, 'missing')
FOUND_DIR = os.path.join(UPLOADS_DIR, 'found')

os.makedirs(MISSING_DIR, exist_ok=True)
os.makedirs(FOUND_DIR, exist_ok=True)

# Global state
known_encodings = []
known_report_ids = []
running = False
cap = None
session_id = None

def db_connect():
    return mysql.connector.connect(
        host=DB_HOST, user=DB_USER, password=DB_PASS, database=DB_NAME
    )

def load_missing_faces(report_ids):
    global known_encodings, known_report_ids
    known_encodings = []
    known_report_ids = []
    
    conn = db_connect()
    cursor = conn.cursor(dictionary=True)
    placeholders = ','.join(['%s'] * len(report_ids))
    query = f"""
        SELECT report_id, photo as image_path 
        FROM missing_reports 
        WHERE report_id IN ({placeholders}) AND status IN ('searching', 'assigned')
    """
    cursor.execute(query, report_ids)
    rows = cursor.fetchall()
    
    for row in rows:
        image_path = os.path.join(os.path.dirname(os.path.dirname(__file__)), row['image_path'])
        if os.path.exists(image_path):
            image = face_recognition.load_image_file(image_path)
            encodings = face_recognition.face_encodings(image)
            if encodings:
                known_encodings.append(encodings[0])
                known_report_ids.append(row['report_id'])
    
    conn.close()
    print(f"Loaded {len(known_encodings)} missing persons")

def detection_loop():
    global running
    while running:
        ret, frame = cap.read()
        if not ret:
            continue
            
        rgb_frame = cv2.cvtColor(frame, cv2.COLOR_BGR2RGB)
        face_locations = face_recognition.face_locations(rgb_frame)
        face_encodings = face_recognition.face_encodings(rgb_frame, face_locations)
        
        for (top, right, bottom, left), face_encoding in zip(face_locations, face_encodings):
            matches = face_recognition.compare_faces(known_encodings, face_encoding, tolerance=0.6)
            face_distances = face_recognition.face_distance(known_encodings, face_encoding)
            
            if matches.count(True) > 0:
                best_match_idx = np.argmin(face_distances)
                confidence = (1 - face_distances[best_match_idx]) * 100
                
                report_id = known_report_ids[best_match_idx]
                
                # Save found image
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
                    'lat': 25.5941,  # Mock GPS
                    'lng': 85.1376
                }
                try:
                    requests.post('http://localhost/HopeFinder/admin/api/report_detection.php', 
                                json=data, timeout=5)
                    print(f"✅ MATCH: Report {report_id} ({confidence:.1f}%)")
                except Exception as e:
                    print(f"Report error: {e}")
            
            # Draw box
            cv2.rectangle(frame, (left, top), (right, bottom), (0, 255, 0), 2)
        
        # Resize for stream
        small_frame = cv2.resize(frame, (640, 480))
        ret, buffer = cv2.imencode('.jpg', small_frame, [cv2.IMWRITE_JPEG_QUALITY, 70])
        frame_bytes = buffer.tobytes()
        
        yield (b'--frame\r\n'
               b'Content-Type: image/jpeg\r\n\r\n' + frame_bytes + b'\r\n')
        time.sleep(0.03)  # ~30 FPS

@app.route('/video_feed')
def video_feed():
    return Response(detection_loop(), mimetype='multipart/x-mixed-replace; boundary=frame')

@app.route('/start_detection', methods=['POST'])
def start_detection():
    global running, session_id, known_encodings, cap
    data = request.json
    report_ids = data.get('report_ids', [])
    
    if not report_ids:
        return jsonify({'error': 'No report IDs'}), 400
    
    session_id = data.get('session_id', 'default')
    load_missing_faces(report_ids)
    
    if cap is None:
        cap = cv2.VideoCapture(0)
    
    running = True
    threading.Thread(target=lambda: None, daemon=True)  # Background loop handled by generator
    
    return jsonify({'status': 'started', 'session_id': session_id, 'targets': len(report_ids)})

@app.route('/status')
def status():
    return jsonify({
        'running': running,
        'targets': len(known_encodings),
        'session_id': session_id
    })

if __name__ == '__main__':
    print("🚀 HopeFinder AI CCTV Server on http://localhost:5001")
    print("Start with: POST /start_detection {report_ids: [1,2], session_id: 'abc'}")
    print("Stream: http://localhost:5001/video_feed")
    app.run(host='0.0.0.0', port=5001, debug=False, threaded=True)

