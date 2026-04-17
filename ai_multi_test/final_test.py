"""
Direct test of DeepFace embedding extraction
"""
import cv2
import os
import sys
import numpy as np

# Add current directory to path
sys.path.insert(0, os.getcwd())

# Import DeepFace
from deepface import DeepFace
import warnings
warnings.filterwarnings('ignore')

print("=" * 60)
print("TESTING DEEPFACE EMBEDDING EXTRACTION")
print("=" * 60)

# Force model building
print("\n1. Building Facenet model...")
try:
    model = DeepFace.build_model("Facenet")
    print("   SUCCESS")
except Exception as e:
    print(f"   ERROR: {e}")

# Test each image
print("\n2. Testing embedding extraction from missing/ folder...")
# Fixed path - use absolute path to ai_multi_test/missing/
script_dir = os.path.dirname(os.path.abspath(__file__))
missing_dir = os.path.join(script_dir, "missing")
print(f"   Looking in: {missing_dir}")

face_cascade = cv2.CascadeClassifier(
    cv2.data.haarcascades + 'haarcascade_frontalface_default.xml'
)

# Check if directory exists
if not os.path.exists(missing_dir):
    print(f"   ERROR: Directory not found: {missing_dir}")
    print(f"   Creating directory...")
    os.makedirs(missing_dir, exist_ok=True)
    print(f"   Directory created. Please add images to: {missing_dir}")
    sys.exit(1)

for filename in sorted(os.listdir(missing_dir)):
    if not filename.lower().endswith(('.jpg', '.jpeg', '.png')):
        continue
    
    path = os.path.join(missing_dir, filename)
    print(f"\n   Testing: {filename}")
    
    # Read image
    img = cv2.imread(path)
    if img is None:
        print(f"      ERROR: Cannot read image")
        continue
    
    h, w = img.shape[:2]
    print(f"      Image: {w}x{h}")
    
    # Try Haar cascade first
    gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
    faces = face_cascade.detectMultiScale(gray, 1.1, 5, minSize=(100, 100))
    print(f"      Haar Cascade found: {len(faces)} face(s)")
    
    # Try DeepFace with different backends
    for backend in ["opencv", "mtcnn", "ssd", "retinaface"]:
        try:
            result = DeepFace.represent(
                img_path=path,
                model_name="Facenet",
                detector_backend=backend,
                enforce_detection=False,
                align=True
            )
            if result and len(result) > 0:
                emb = result[0]["embedding"]
                print(f"      {backend}: SUCCESS - embedding size: {len(emb)}")
                break
        except Exception as e:
            err = str(e)[:60]
            if "no face" in err.lower():
                print(f"      {backend}: No face detected")
            else:
                print(f"      {backend}: {err}")

print("\n" + "=" * 60)
print("TEST COMPLETE")
print("=" * 60)

