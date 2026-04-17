"""
High-Performance Optimized AI Face Recognition System
======================================================
- Optimized for CPU: Fast processing, quick response
- Uses lightweight model and efficient matching
"""

import cv2
import os
import numpy as np
import time
import re
import requests
from datetime import datetime
from deepface import DeepFace
import warnings
import threading
warnings.filterwarnings('ignore')

# ============================================================================
# CPU OPTIMIZED CONFIGURATION
# ============================================================================
# ============================================================================
BASE_DIR = os.path.dirname(os.path.abspath(__file__))
MISSING_DIR = os.path.join(BASE_DIR, "missing")
FOUND_DIR = os.path.join(BASE_DIR, "found")

# Lightweight, fast model
MODEL_NAME = "Facenet"          
DETECTOR_BACKEND = "opencv"     
MATCH_THRESHOLD = 0.80          
CHECK_INTERVAL = 0.3            # Fast response (every 0.3s)
FRAME_RESIZE = (320, 240)       # SMALLER = Much faster processing
COOLDOWN_SECONDS = 2            # Quick re-detection

# Auto-create directories
os.makedirs(MISSING_DIR, exist_ok=True)
os.makedirs(FOUND_DIR, exist_ok=True)

# Suppress DeepFace logging
import logging
logging.getLogger('deepface').setLevel(logging.ERROR)



# ============================================================================
# CACHED LOCATION (updated asynchronously)
# ============================================================================
_cached_location = None
_location_lock = threading.Lock()

def update_location_async():
    """Update location in background thread."""
    global _cached_location
    try:
        response = requests.get(
            "http://ip-api.com/json/?fields=lat,lon,city,country", 
            timeout=3
        )
        if response.status_code == 200:
            data = response.json()
            with _location_lock:
                _cached_location = {
                    "latitude": data.get("lat", 0.0),
                    "longitude": data.get("lon", 0.0),
                    "city": data.get("city", "Unknown"),
                    "country": data.get("country", "Unknown"),
                    "timestamp": datetime.now().isoformat()
                }
    except:
        pass

def get_location():
    """Return cached location (non-blocking)."""
    with _location_lock:
        if _cached_location:
            return _cached_location
        return {
            "latitude": 0.0,
            "longitude": 0.0,
            "city": "Unknown",
            "country": "Unknown",
            "timestamp": datetime.now().isoformat()
        }

# Start location update thread
threading.Thread(target=update_location_async, daemon=True).start()

# ============================================================================
# HELPER FUNCTIONS
# ============================================================================
def extract_person_id(filename):
    name_without_ext = os.path.splitext(filename)[0]
    match = re.search(r'(\d+)$', name_without_ext)
    return match.group(1) if match else "unknown"

def preprocess_image(img, target_size=(160, 160)):
    """Fast image preprocessing."""
    # Resize quickly
    img = cv2.resize(img, (target_size[1], target_size[0]))
    # Convert to grayscale for faster detection
    gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
    return img, gray

# ============================================================================
# FAST EMBEDDING EXTRACTION
# ============================================================================
def get_embedding_fast(img_path):
    """Fast embedding extraction with error handling."""
    try:
        result = DeepFace.represent(
            img_path=img_path,
            model_name=MODEL_NAME,
            detector_backend=DETECTOR_BACKEND,
            enforce_detection=False,
            align=True
        )
        if result and len(result) > 0:
            return np.array(result[0]["embedding"])
    except:
        pass
    return None

# ============================================================================
# LOAD MISSING PERSONS (CACHED IN RAM)
# ============================================================================
print("=" * 60)
print("LOADING TARGETS (CPU OPTIMIZED)...")
print("=" * 60)

# Build model once
print("Loading model...")
try:
    DeepFace.build_model(MODEL_NAME)
    print("Ready!\n")
except Exception as e:
    print(f"Error: {e}")
    exit(1)

# Load targets
target_files = [f for f in os.listdir(MISSING_DIR) 
                if os.path.isfile(os.path.join(MISSING_DIR, f)) 
                and f.lower().endswith(('.jpg', '.jpeg', '.png'))]

if not target_files:
    print("No target images in missing/ folder!")
    exit()

print(f"Loading {len(target_files)} targets...")

# Load ALL targets and pre-compute distances matrix
all_embeddings = []
target_names = []
target_ids = []

for filename in sorted(target_files):
    path = os.path.join(MISSING_DIR, filename)
    emb = get_embedding_fast(path)
    
    if emb is not None:
        # Normalize once
        emb_norm = emb / np.linalg.norm(emb)
        all_embeddings.append(emb_norm)
        target_names.append(os.path.splitext(filename)[0])
        target_ids.append(extract_person_id(filename))
        print(f"  Loaded: {filename}")
    else:
        print(f"  Skipped: {filename}")

# Convert to numpy array for vectorized operations
all_embeddings = np.array(all_embeddings)

loaded_count = len(target_names)
print(f"\nLoaded {loaded_count} / {len(target_files)} targets")
print("=" * 60)

if loaded_count == 0:
    print("No valid targets!")
    exit()

# ============================================================================
# CAMERA SETUP
# ============================================================================
print("\nStarting webcam...")
cap = cv2.VideoCapture(0, cv2.CAP_DSHOW)  # DirectShow for faster startup

if not cap.isOpened():
    print("Cannot open webcam!")
    exit()

# Fast camera settings
cap.set(cv2.CAP_PROP_FRAME_WIDTH, FRAME_RESIZE[0])
cap.set(cv2.CAP_PROP_FRAME_HEIGHT, FRAME_RESIZE[1])
cap.set(cv2.CAP_PROP_FPS, 30)
cap.set(cv2.CAP_PROP_BUFFERSIZE, 1)

print(f"Resolution: {FRAME_RESIZE[0]}x{FRAME_RESIZE[1]}")
print(f"Check every: {CHECK_INTERVAL}s")
print("\n" + "=" * 60)
print("RUNNING - Press Q to quit")
print("=" * 60)

# ============================================================================
# OPTIMIZED MAIN LOOP
# ============================================================================
last_check_time = 0
last_found_person = None
last_found_time = 0
TEMP_PATH = os.path.join(BASE_DIR, "_temp.jpg")

# Pre-allocate variables
best_match_idx = 0
best_distance = 1.0

while True:
    ret, frame = cap.read()
    if not ret:
        break
    
    # Show frame immediately (no processing lag)
    cv2.putText(frame, f"Targets: {len(target_names)} | Q=Quit", 
                (5, 20), cv2.FONT_HERSHEY_SIMPLEX, 0.5, (0, 255, 0), 1)
    cv2.imshow("Face Search", frame)
    
    # Process at fixed interval
    current_time = time.time()
    if current_time - last_check_time >= CHECK_INTERVAL:
        last_check_time = current_time
        
        # Save small image for fast processing
        small_frame = cv2.resize(frame, (160, 120))
        cv2.imwrite(TEMP_PATH, small_frame, [cv2.IMWRITE_JPEG_QUALITY, 60])
        
        # Get embedding
        source_emb = get_embedding_fast(TEMP_PATH)
        if source_emb is None:
            continue
        
        # Normalize
        source_norm = source_emb / np.linalg.norm(source_emb)
        
        # VECTORIZED distance computation (much faster than loop)
        distances = np.linalg.norm(all_embeddings - source_norm, axis=1)
        
        # Find best match
        best_match_idx = np.argmin(distances)
        best_distance = distances[best_match_idx]
        
        # Check threshold
        if best_distance <= MATCH_THRESHOLD:
            current_ts = time.time()
            matched_name = target_names[best_match_idx]
            
            # Cooldown check
            if matched_name != last_found_person or (current_ts - last_found_time) >= COOLDOWN_SECONDS:
                last_found_person = matched_name
                last_found_time = current_ts
                
                # Quick save
                ts = datetime.now().strftime("%Y%m%d_%H%M%S")
                save_name = f"{matched_name}_ID{target_ids[best_match_idx]}_{ts}.jpg"
                cv2.imwrite(os.path.join(FOUND_DIR, save_name), frame, [cv2.IMWRITE_JPEG_QUALITY, 90])
                
                # Fast output
                conf = (1 - best_distance / MATCH_THRESHOLD) * 100
                loc = get_location()
                print(f"\n[FOUND] {matched_name} | Distance: {best_distance:.3f} | Confidence: {conf:.0f}%")
                print(f"  Location: {loc['city']}, {loc['country']} | Saved: {save_name}")
    
    # Quick keyboard check
    key = cv2.waitKey(1) & 0xFF
    if key == ord('q'):
        break

# Cleanup
cap.release()
cv2.destroyAllWindows()
if os.path.exists(TEMP_PATH):
    os.remove(TEMP_PATH)
print("\nDone.")

