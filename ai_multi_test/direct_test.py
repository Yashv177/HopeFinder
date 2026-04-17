import cv2
import os
import sys
from deepface import DeepFace
import warnings
warnings.filterwarnings('ignore')

out = open("deepface_test.log", "w")

def log(msg):
    print(msg)
    out.write(msg + "\n")
    out.flush()

log("=" * 50)
log("DEEPFACE TEST")
log("=" * 50)

# Build model
log("Building Facenet model...")
try:
    model = DeepFace.build_model("Facenet")
    log("SUCCESS")
except Exception as e:
    log(f"ERROR: {e}")
    sys.exit(1)

# Test images
log("\nTesting images...")
missing_dir = "missing"

face_cascade = cv2.CascadeClassifier(
    cv2.data.haarcascades + 'haarcascade_frontalface_default.xml'
)

for filename in sorted(os.listdir(missing_dir)):
    if not filename.lower().endswith(('.jpg', '.jpeg', '.png')):
        continue
    
    path = os.path.join(missing_dir, filename)
    log(f"\n{filename}")
    
    img = cv2.imread(path)
    if img is None:
        log("  Cannot read image")
        continue
    
    h, w = img.shape[:2]
    log(f"  Size: {w}x{h}")
    
    # Haar
    gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
    faces = face_cascade.detectMultiScale(gray, 1.1, 5, minSize=(100, 100))
    log(f"  Haar: {len(faces)} faces")
    
    # DeepFace
    for backend in ["opencv", "mtcnn", "ssd"]:
        try:
            result = DeepFace.represent(
                img_path=path,
                model_name="Facenet",
                detector_backend=backend,
                enforce_detection=False,
                align=True
            )
            if result:
                log(f"  {backend}: OK ({len(result[0]['embedding'])} dims)")
                break
        except Exception as e:
            log(f"  {backend}: {str(e)[:40]}")

log("\n" + "=" * 50)
out.close()

