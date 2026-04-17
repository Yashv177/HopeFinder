"""
Simple face detection test
"""
import cv2
import os
import sys

# Write output to file
output_file = open("test_output.txt", "w")

def log(msg):
    print(msg)
    output_file.write(msg + "\n")
    output_file.flush()

log("=" * 60)
log("FACE DETECTION TEST")
log("=" * 60)

# Load Haar Cascade
face_cascade = cv2.CascadeClassifier(cv2.data.haarcascades + 'haarcascade_frontalface_default.xml')

if face_cascade.empty():
    log("ERROR: Cannot load Haar Cascade")
    sys.exit(1)

log("Haar Cascade loaded successfully")

missing_dir = "missing"

for filename in os.listdir(missing_dir):
    path = os.path.join(missing_dir, filename)
    log(f"\nTesting: {filename}")
    
    img = cv2.imread(path)
    if img is None:
        log(f"  ERROR: Cannot read image")
        continue
    
    h, w = img.shape[:2]
    log(f"  Size: {w}x{h}")
    
    gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
    faces = face_cascade.detectMultiScale(gray, 1.1, 5, minSize=(100, 100))
    
    log(f"  Faces detected: {len(faces)}")
    for (x, y, fw, fh) in faces:
        log(f"  Face: x={x}, y={y}, size={fw}x{fh}")

log("\n" + "=" * 60)
log("DONE")
output_file.close()

