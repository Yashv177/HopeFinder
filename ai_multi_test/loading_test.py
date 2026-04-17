"""
Quick test to verify multi_test.py loads targets correctly
"""
import cv2
import os
import sys
import numpy as np
from deepface import DeepFace
import warnings
warnings.filterwarnings('ignore')

# Suppress DeepFace logging
import logging
logging.getLogger('deepface').setLevel(logging.ERROR)

# Constants from multi_test.py
BASE_DIR = os.path.dirname(os.path.abspath(__file__))
MISSING_DIR = os.path.join(BASE_DIR, "missing")
MODEL_NAME = "Facenet"
DETECTOR_BACKEND = "RetinaFace"

out = open("loading_test.log", "w")

def log(msg):
    print(msg)
    out.write(msg + "\n")
    out.flush()

log("=" * 60)
log("TESTING TARGET LOADING")
log("=" * 60)

# Build model
log("\nBuilding Facenet model...")
try:
    model = DeepFace.build_model(MODEL_NAME)
    log("SUCCESS")
except Exception as e:
    log(f"ERROR: {e}")
    sys.exit(1)

# Load targets
targets = []
target_files = [f for f in os.listdir(MISSING_DIR) if os.path.isfile(os.path.join(MISSING_DIR, f))]

log(f"\nFound {len(target_files)} files\n")

face_cascade = cv2.CascadeClassifier(
    cv2.data.haarcascades + 'haarcascade_frontalface_default.xml'
)

for filename in sorted(target_files):
    path = os.path.join(MISSING_DIR, filename)
    log(f"Processing: {filename}...", end=" ")
    
    # Verify image
    test_img = cv2.imread(path)
    if test_img is None:
        log("(Invalid image)")
        continue
    
    h, w = test_img.shape[:2]
    log(f"{w}x{h}", end=" ")
    
    # Use Haar to find largest face and crop
    gray = cv2.cvtColor(test_img, cv2.COLOR_BGR2GRAY)
    faces = face_cascade.detectMultiScale(gray, 1.1, 5, minSize=(100, 100))
    
    if len(faces) > 0:
        # Use largest face
        largest = max(faces, key=lambda f: f[2] * f[3])
        x, y, fw, fh = largest
        face_roi = test_img[y:y+fh, x:x+fw]
        temp_path = os.path.join(BASE_DIR, "temp_face_test.jpg")
        cv2.imwrite(temp_path, face_roi)
        
        # Get embedding from cropped face
        try:
            result = DeepFace.represent(
                img_path=temp_path,
                model_name=MODEL_NAME,
                detector_backend="opencv",
                enforce_detection=False,
                align=True,
                normalize=True
            )
            if result:
                emb = np.array(result[0]["embedding"])
                emb_norm = emb / np.linalg.norm(emb)
                
                person_name = os.path.splitext(filename)[0]
                # Extract ID
                import re
                match = re.search(r'(\d+)$', person_name)
                person_id = match.group(1) if match else "unknown"
                
                targets.append({
                    "name": person_name,
                    "id": person_id,
                    "embedding": emb,
                    "embedding_norm": emb_norm
                })
                
                log(f"OK - {person_name} (ID: {person_id})")
        except Exception as e:
            log(f"Error: {e}")
        
        # Cleanup
        if os.path.exists(temp_path):
            os.remove(temp_path)
    else:
        log("(No face detected)")

log(f"\n{'=' * 60}")
log(f"RESULT: Loaded {len(targets)} / {len(target_files)} targets")
log("=" * 60)

if len(targets) > 0:
    log("\nSUCCESS: System can load target images!")
    log("\nRun 'python multi_test.py' to start the webcam system.")
else:
    log("\nFAILED: Could not load any targets.")

out.close()

