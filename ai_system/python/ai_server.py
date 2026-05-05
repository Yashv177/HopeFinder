#!/usr/bin/env python3
"""HopeFinder AI Server - Real-time Missing Person Detection
Flask + face_recognition + OpenCV. Compatible numpy 1.26.4.
Port 5001. POST detections to PHP save_detection.php.
"""
import os
import cv2
import numpy as np
import face_recognition
import requests
import threading
import time
import queue
from datetime import datetime
from flask import Flask, Response, request, jsonify
from pathlib import Path
import logging

# Paths (relative to HopeFinder root)
ROOT_DIR = Path(__file__).resolve().parent.parent.parent  # e:/xmapp/htdocs/HopeFinder
UPLOADS_DIR = ROOT_DIR / 'uploads'
AI_FOUND_DIR = UPLOADS_DIR / 'ai_found'
AI_FOUND_DIR.mkdir(parents=True, exist_ok=True)

# PHP API
PHP_BASE = 'http://127.0.0.1/HopeFinder/ai_system/backend/api'
SAVE_DETECTION_URL = f'{PHP_BASE}/save_detection.php'
GET_MISSING_URL = f'{PHP_BASE}/get_missing.php'

# Config
TOLERANCE = 0.6  # Lower = stricter match (~0.6 = 70%+ conf)
FRAME_W, FRAME_H = 640, 480
COOLDOWN_SEC = 10
SKIP_FRAMES = 2  # Process every 3rd frame

# Logging
AI_DIR = Path(__file__).resolve().parent.parent
(AI_DIR / 'logs').mkdir(exist_ok=True)
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s',
    handlers=[logging.FileHandler(AI_DIR / 'logs' / 'ai.log'), logging.StreamHandler()]
)
logger = logging.getLogger(__name__)

app = Flask(__name__)

# Thread-safe globals
stop_event = threading.Event()
current_encodings_lock = threading.Lock()
target_encodings = []  # list(np.array)
target_ids = []        # list(str)
target_names = {}      # dict str:str
recent_detections_lock = threading.Lock()
recent_detections = {} # dict str:float (time)
frame_queue = queue.Queue(maxsize=3)
latest_frame_lock = threading.Lock()
latest_frame = None
latest_annotated_lock = threading.Lock()
latest_annotated = None
camera_worker = None
detection_worker = None
workers_lock = threading.Lock()
session_latitude = 0.0
session_longitude = 0.0

@app.after_request
def cors(response):
    response.headers.update({
        'Access-Control-Allow-Origin': '*',
        'Access-Control-Allow-Headers': 'Content-Type',
        'Access-Control-Allow-Methods': 'GET,POST,OPTIONS'
    })
    return response

def load_selected_targets(selected_ids):
    """Load face encodings for selected missing persons from PHP API"""
    if not selected_ids:
        return 0
    
    params = {'ids': selected_ids}
    try:
        resp = requests.get(GET_MISSING_URL, params=params, timeout=10)
        resp.raise_for_status()
        missing_data = resp.json()
        
        new_encodings = []
        new_ids = []
        new_names = {}
        
        for person in missing_data:
            rep_id = str(person.get('id'))
            photo_rel = person.get('photo')
            name = person.get('name', f'Report {rep_id}')
            
            # Resolve photo path
            photo_path = None
            candidates = [
                ROOT_DIR / photo_rel,
                UPLOADS_DIR / photo_rel,
                UPLOADS_DIR / 'missing_persons' / Path(photo_rel).name
            ]
            for cand in candidates:
                if cand.exists():
                    photo_path = str(cand)
                    break
            
            if not photo_path:
                logger.warning(f'Missing photo for {rep_id}: {photo_rel}')
                continue
            
            try:
                image = face_recognition.load_image_file(photo_path)
                encodings = face_recognition.face_encodings(image)
                if encodings:
                    new_encodings.append(encodings[0])
                    new_ids.append(rep_id)
                    new_names[rep_id] = name
                    logger.info(f'Loaded target: {rep_id} - {name}')
            except Exception as e:
                logger.error(f'Encoding failed for {rep_id}: {e}')
        
        with current_encodings_lock:
            target_encodings[:] = new_encodings
            target_ids[:] = new_ids
            target_names.update(new_names)
        
        count = len(new_encodings)
        logger.info(f'🎯 Loaded {count} targets')
        return count
    except Exception as e:
        logger.error(f'Targets load error: {e}')
        return 0

def save_and_report_detection(report_id, confidence, face_crop):
    """Save crop to ai_found/ and POST to PHP"""
    try:
        conf_pct = min(100.0, max(0.0, confidence * 100))
        ts = datetime.now().strftime('%Y%m%d_%H%M%S_%f')[:-3]
        fname = f'match_{report_id}_{ts}.jpg'
        full_path = AI_FOUND_DIR / fname
        rel_path = f'uploads/ai_found/{fname}'
        
        cv2.imwrite(str(full_path), face_crop, [cv2.IMWRITE_JPEG_QUALITY, 95])
        
        data = {
            'report_id': int(report_id),
            'image_path': rel_path,
            'confidence': conf_pct,
            'latitude': session_latitude,
            'longitude': session_longitude,
            'timestamp': datetime.now().strftime('%Y-%m-%d %H:%M:%S')
        }
        
        resp = requests.post(SAVE_DETECTION_URL, json=data, timeout=5)
        resp.raise_for_status()
        result = resp.json()
        
        if result.get('status') == 'success':
            logger.info(f'✅ Detection saved: {report_id} {conf_pct:.1f}%')
            return True
        else:
            logger.warning(f'PHP save error: {result}')
    except Exception as e:
        logger.error(f'Detection report failed: {e}')
    return False

def camera_capture_thread():
    """Optimized webcam/RTSP capture"""
    global camera_worker
    cam_src = getattr(request, 'camera_source', 0)  # Default webcam
    cap = cv2.VideoCapture(cam_src)
    
    if not cap.isOpened():
        logger.error(f'Camera/RTSP failed: {cam_src}')
        return
    
    cap.set(cv2.CAP_PROP_FRAME_WIDTH, FRAME_W)
    cap.set(cv2.CAP_PROP_FRAME_HEIGHT, FRAME_H)
    cap.set(cv2.CAP_PROP_FPS, 30)
    cap.set(cv2.CAP_PROP_BUFFERSIZE, 1)
    
    logger.info(f'📹 Camera started: {cam_src}')
    consecutive_fails = 0
    
    while not stop_event.is_set():
        ret, frame = cap.read()
        if not ret:
            consecutive_fails += 1
            if consecutive_fails > 50:
                logger.error('Camera permanently failed')
                break
            time.sleep(0.1)
            continue
        
        consecutive_fails = 0
        
        # Queue raw frame
        try:
            frame_queue.put_nowait(frame.copy())
        except queue.Full:
            pass
        
        # Update latest raw
        with latest_frame_lock:
            latest_frame = frame.copy()
        
        time.sleep(0.01)  # ~100fps cap
    
    cap.release()
    logger.info('📹 Camera stopped')

def detection_thread():
    """Real-time face matching"""
    frame_count = 0
    logger.info('🧠 Detection started')
    
    while not stop_event.is_set():
        try:
            frame = frame_queue.get(timeout=0.1)
        except queue.Empty:
            continue
        
        frame_count += 1
        if frame_count % (SKIP_FRAMES + 1) != 0:
            with latest_frame_lock:
                latest_annotated = frame.copy()
            continue
        
        annotated = frame.copy()
        
        # Status text
        with current_encodings_lock:
            num_targets = len(target_encodings)
        if num_targets == 0:
            cv2.putText(annotated, 'AI Ready - Select targets via /start_detection', (10, 30),
                       cv2.FONT_HERSHEY_SIMPLEX, 0.7, (0, 255, 255), 2)
        else:
            cv2.putText(annotated, f'Scanning {num_targets} targets | FPS ~30', (10, 30),
                       cv2.FONT_HERSHEY_SIMPLEX, 0.7, (0, 255, 0), 2)
        
        with current_encodings_lock:
            if len(target_encodings) == 0:
                with latest_annotated_lock:
                    latest_annotated = annotated
                continue
            
            rgb_frame = cv2.cvtColor(frame, cv2.COLOR_BGR2RGB)
            face_locations = face_recognition.face_locations(rgb_frame)
            face_encodings = face_recognition.face_encodings(rgb_frame, face_locations)
        
        for (top, right, bottom, left), face_enc in zip(face_locations, face_encodings):
            matches = face_recognition.compare_faces(target_encodings, face_enc, TOLERANCE)
            face_distances = face_recognition.face_distance(target_encodings, face_enc)
            
            best_match_idx = np.argmin(face_distances)
            if matches[best_match_idx]:
                min_dist = face_distances[best_match_idx]
                conf = (1 - min_dist) * 100  # 0-100%
                rep_id = target_ids[best_match_idx]
                name = target_names.get(rep_id, 'Unknown')
                
                # Cooldown check
                now = time.time()
                with recent_detections_lock:
                    if rep_id in recent_detections and now - recent_detections[rep_id] < COOLDOWN_SEC:
                        continue
                    recent_detections[rep_id] = now
                
                # Draw
                cv2.rectangle(annotated, (left, top), (right, bottom), (0, 255, 0), 3)
                label = f'{name} #{rep_id} {conf:.0f}%'
                cv2.putText(annotated, label, (left, top-10),
                           cv2.FONT_HERSHEY_SIMPLEX, 0.6, (0, 255, 0), 2)
                
                # Crop & async save
                crop = frame[top:bottom, left:right]
                crop_resized = cv2.resize(crop, (320, 320))
                threading.Thread(target=save_and_report_detection, args=(rep_id, conf/100, crop_resized), daemon=True).start()
        
        with latest_annotated_lock:
            latest_annotated = annotated
    
    logger.info('🧠 Detection stopped')

def ensure_workers():
    """Start/restart threads if needed"""
    global camera_worker, detection_worker
    with workers_lock:
        if camera_worker is None or not camera_worker.is_alive():
            camera_worker = threading.Thread(target=camera_capture_thread, daemon=True)
            camera_worker.start()
        if detection_worker is None or not detection_worker.is_alive():
            detection_worker = threading.Thread(target=detection_thread, daemon=True)
            detection_worker.start()

def gen_video_feed():
    """MJPEG generator"""
    while not stop_event.is_set():
        frame = None
        with latest_annotated_lock:
            if latest_annotated is not None:
                frame = latest_annotated.copy()
        if frame is None:
            with latest_frame_lock:
                if latest_frame is not None:
                    frame = latest_frame.copy()
        
        if frame is None:
            # Placeholder
            frame = np.zeros((FRAME_H, FRAME_W, 3), dtype=np.uint8)
            cv2.putText(frame, 'No Camera / No Targets', (100, FRAME_H//2),
                       cv2.FONT_HERSHEY_SIMPLEX, 1, (0, 0, 255), 3)
        
        ret, buffer = cv2.imencode('.jpg', frame, [cv2.IMWRITE_JPEG_QUALITY, 85])
        if ret:
            yield (b'--frame\\r\\n'
                   b'Content-Type: image/jpeg\\r\\n\\r\\n' + buffer.tobytes() + b'\\r\\n')
        time.sleep(1/30)

@app.route('/video_feed')
def video_feed():
    return Response(gen_video_feed(), mimetype='multipart/x-mixed-replace; boundary=frame')

@app.route('/start_detection', methods=['POST'])
def start_detection():
    data = request.get_json() or {}
    print(f"🔍 DEBUG /start_detection - Received: {data}")
    selected_ids = data.get('selected_ids') or data.get('report_ids', [])
    camera_src = data.get('camera', 0)
    
    # Store session GPS location
    global session_latitude, session_longitude
    session_latitude = float(data.get('latitude', 0.0))
    session_longitude = float(data.get('longitude', 0.0))
    print(f"📍 GPS Session set: lat={session_latitude}, lng={session_longitude}")

    if not isinstance(selected_ids, list):
        print(f"🔍 DEBUG - Invalid selected_ids type: {type(selected_ids)}")
        return jsonify({'error': 'selected_ids/report_ids list required'}), 400
    
    with recent_detections_lock:
        recent_detections.clear()
    
    request.camera_source = camera_src
    
    try:
        count = load_selected_targets(selected_ids)
    except Exception as e:
        print(f"🔍 DEBUG - load_targets ERROR: {e}")
        logger.error(f"Target load failed: {e}")
        count = 0

    ensure_workers()
    logger.info(f'▶️ Started detection: {count} targets, cam={camera_src}, GPS=({session_latitude}, {session_longitude})')
    return jsonify({
        'status': 'success', 
        'targets_loaded': count, 
        'camera': camera_src,
        'gps': {'lat': session_latitude, 'lng': session_longitude}
    })

@app.route('/stop_detection', methods=['POST'])
def stop_detection():
    stop_event.set()
    with current_encodings_lock:
        target_encodings.clear()
        target_ids.clear()
        target_names.clear()
    with recent_detections_lock:
        recent_detections.clear()
    with latest_frame_lock:
        latest_frame = None
    with latest_annotated_lock:
        latest_annotated = None
    
    while not frame_queue.empty():
        try:
            frame_queue.get_nowait()
        except queue.Empty:
            break
    
    logger.info('⏹️ Detection stopped')
    return jsonify({'status': 'stopped'})

@app.route('/health')
def health():
    with current_encodings_lock:
        tgt_count = len(target_encodings)
    cam_alive = camera_worker is not None and camera_worker.is_alive()
    det_alive = detection_worker is not None and detection_worker.is_alive()
    return jsonify({
        'status': 'ok',
        'targets': tgt_count,
        'running': not stop_event.is_set(),
        'camera': cam_alive,
        'detection': det_alive,
        'tolerance': TOLERANCE
    })

if __name__ == '__main__':
    logger.info('🚀 HopeFinder AI Server starting (numpy 1.26.4 + face_recognition)...')
app.run(host='0.0.0.0', port=5001, debug=False, threaded=True, use_reloader=False)