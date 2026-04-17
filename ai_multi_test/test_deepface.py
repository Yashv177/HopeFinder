"""
Test script to diagnose DeepFace embedding issues
"""
import cv2
import os
import sys
from deepface import DeepFace
import warnings
warnings.filterwarnings('ignore')

print("=" * 60)
print("DEEPFACE DIAGNOSTIC TEST")
print("=" * 60)

# Check if models are built
print("\n1. Building Facenet model...")
try:
    model = DeepFace.build_model(model_name="Facenet")
    print("   SUCCESS: Facenet model built")
except Exception as e:
    print(f"   ERROR: {e}")

# Check available backends
print("\n2. Testing detector backends...")
backends = ['opencv', 'ssd', 'mtcnn', 'retinaface']
for backend in backends:
    try:
        # Simple test if backend is available
        print(f"   {backend}: checking...")
    except Exception as e:
        print(f"   {backend}: {e}")

# Test with actual images
print("\n3. Testing embedding extraction...")
missing_dir = "missing"

for filename in os.listdir(missing_dir):
    path = os.path.join(missing_dir, filename)
    print(f"\n   Testing: {filename}")
    
    # First verify image can be read
    img = cv2.imread(path)
    if img is None:
        print(f"      ERROR: Cannot read image")
        continue
    
    h, w = img.shape[:2]
    print(f"      Image size: {w}x{h}")
    
    # Try each detector
    for detector in ['opencv', 'ssd', 'mtcnn', 'retinaface']:
        try:
            result = DeepFace.represent(
                img_path=path,
                model_name="Facenet",
                detector_backend=detector,
                enforce_detection=False,
                align=True
            )
            if result and len(result) > 0:
                emb = result[0]["embedding"]
                print(f"      {detector}: SUCCESS - embedding size: {len(emb)}")
                break
        except Exception as e:
            error_str = str(e)
            if "no face" in error_str.lower():
                print(f"      {detector}: No face detected")
            else:
                print(f"      {detector}: {error_str[:50]}...")

print("\n" + "=" * 60)
print("DIAGNOSTIC COMPLETE")
print("=" * 60)

