#!/usr/bin/env python3
# HopeFinder CCTV - DeepFace Edition (No face_recognition needed)

import os
import cv2
import numpy as np
from flask import Flask, Response, request, jsonify
from deepface import DeepFace
import mysql.connector
from datetime import datetime
import requests
import threading
import time

app = Flask(__name__)

# Config
DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'hopefinder'
}

UPLOADS_DIR = os.path.join(os.path.dirname(__file__), '..', 'uploads')
MISSING_DIR = os.path.join(UPLOADS_DIR, 'missing')
FOUND_DIR = os.path.join(UPLOADS_DIR, 'found')

os.makedirs(MISSING_DIR, exist_ok=True)
os.makedirs(FOUND_DIR, exist_ok=True)

# State
reference_images = {}  # report_id: image_path
running = False
cap = None
session_id = None

def load_references(report_ids):
    global reference_images
    reference_images = {}
    
    conn = mysql.connector.connect(**DB_CONFIG)
    cursor = conn.cursor(dictionary=True)
    placeholders = ','.join(['%s'] * len(report_ids))
    cursor.execute(f"""
        SELECT report_id, photo 
        FROM missing_reports 
        WHERE report_id IN ({placeholders})
    """, report_ids)
    
    for row in cursor:
        path = os.path.join(os.path.dirname(__file__), row['photo'])
        if os.path.exists(path):
            reference_images[row['report_id']] = path
            print(f"Loaded ref: {row['report_id']}")
    
    conn.close()
    print(f"DeepFace refs: {len(reference_images)}")

@app.route('/video_feed')
def video_feed():
    global cap
    if cap is None:
        cap = cv2.VideoCapture(0)
    
    def generate():
        global running
        running = True
        while running:
            success, frame = cap.read()
            if not success:
                continue
                
            # DeepFace verification (first ref image)
            for report_id, ref_path in reference_images.items():
                try:
                    result = DeepFace.verify(ref_path, frame, model_name='VGG-Face', 
                                           enforce_detection=False, detector_backend='opencv')
                    if result['verified'] and result['distance'] < 0.4:  # High confidence
                        confidence = (1 - result['distance']) * 100
                        
                        # Save & report
                        ts = datetime.now().strftime('%Y%m%d_%H%M%S')
                        filename = f"{report_id}_deepface_{ts}.jpg"
                        out_path = os.path.join(FOUND_DIR, filename)
                        cv2.imwrite(out_path, frame)
                        
                        data = {
                            'session_id': session_id,
                            'report_id': report_id,
                            'image_path': f'uploads/found/{filename}',
                            'confidence': confidence,
                            'lat': 25.5941, 'lng': 85.1376
                        }
                        requests.post('http://localhost/HopeFinder/admin/api/report_detection.php', 
                                    json=data, timeout=3)
                        
                        # Draw
                        h, w = frame.shape[:2]
                        cv2.rectangle(frame, (50, 50), (w-50, h-50), (0, 255, 0), 3)
                        cv2.putText(frame, f"MATCH #{report_id} {confidence:.0f}%", (50, 100),
                                   cv2.FONT_HERSHEY_SIMPLEX, 1, (0, 255, 0), 3)
                        break
                except:
                    continue
            
            ret, buffer = cv2.imencode('.jpg', frame)
            yield (b'--frame\r\nContent-Type: image/jpeg\r\n\r\n' + buffer.tobytes() + b'\r\n')
            time.sleep(0.05)
    
    return Response(generate(), mimetype='multipart/x-mixed-replace; boundary=frame')

@app.route('/start', methods=['POST'])
def start():
    global session_id
    data = request.json
    report_ids = data.get('report_ids', [])
    session_id = data.get('session_id', 'deep_session')
    
    load_references(report_ids)
    return jsonify({
        'status': 'started',
        'session': session_id,
        'refs': len(reference_images)
    })

@app.route('/status')
def status():
    return jsonify({
        'session': session_id,
        'refs': len(reference_images),
        'running': running
    })

if __name__ == '__main__':
    print("🚀 DeepFace CCTV: http://localhost:5001/video_feed")
app.run(host='0.0.0.0', port=5001, debug=True, cors=True)

