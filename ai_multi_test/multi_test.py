import cv2
import os
import numpy as np
import time
from datetime import datetime
from deepface import DeepFace
import threading
import queue

# ==========================
# PATHS
# ==========================

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
MISSING_DIR = os.path.join(BASE_DIR,"missing")
FOUND_DIR = os.path.join(BASE_DIR,"found")

print(f"Missing dir: {MISSING_DIR}")
print(f"Found dir: {FOUND_DIR}")

os.makedirs(MISSING_DIR, exist_ok=True)
os.makedirs(FOUND_DIR, exist_ok=True)

# ==========================
# SETTINGS
# ==========================

MODEL_NAME = "Facenet"
MATCH_THRESHOLD = 0.75
FRAME_SIZE = (640,480)

# ==========================
# GLOBALS
# ==========================

frame_queue = queue.Queue(maxsize=2)
stop_event = threading.Event()
targets = []
target_names = []

# ==========================
# LOAD MODEL
# ==========================

print("Loading AI model...")
DeepFace.build_model(MODEL_NAME)
print("Model ready\n")

# ==========================
# EMBEDDING FUNCTION
# ==========================

def get_embedding(img):

    try:
        # Convert BGR to RGB if numpy array
        if isinstance(img, np.ndarray):
            img_rgb = cv2.cvtColor(img, cv2.COLOR_BGR2RGB)
        else:
            img_rgb = img
        
        result = DeepFace.represent(
            img_path=img_rgb,
            model_name=MODEL_NAME,
            detector_backend="opencv",
            enforce_detection=False
        )

        if result and len(result)>0:
            emb = np.array(result[0]["embedding"])
            emb = emb / np.linalg.norm(emb)
            return emb

    except Exception as e:
        print(f"Embedding error: {e}")
        pass

    return None


# ==========================
# LOAD TARGETS
# ==========================

print("Loading missing persons...\n")

files = [f for f in os.listdir(MISSING_DIR)
         if f.lower().endswith((".jpg",".png",".jpeg"))]

for f in files:

    img = cv2.imread(os.path.join(MISSING_DIR,f))

    if img is None:
        continue

    emb = get_embedding(img)

    if emb is not None:

        targets.append(emb)
        target_names.append(os.path.splitext(f)[0])

        print("Loaded:",f)

targets = np.array(targets)

print("\nTotal targets:",len(target_names))


# ==========================
# FACE DETECTOR
# ==========================

face_detector = cv2.CascadeClassifier(
    cv2.data.haarcascades+"haarcascade_frontalface_default.xml"
)

# ==========================
# CAMERA THREAD
# ==========================

def camera_thread():
    global stop_event

    cap = cv2.VideoCapture(0)
    if not cap.isOpened():
        print("Error: Cannot open camera")
        return

    cap.set(cv2.CAP_PROP_FRAME_WIDTH, FRAME_SIZE[0])
    cap.set(cv2.CAP_PROP_FRAME_HEIGHT, FRAME_SIZE[1])
    cap.set(cv2.CAP_PROP_BUFFERSIZE, 1)

    print("Camera started. Press 'q' to quit.")

    while not stop_event.is_set():
        ret, frame = cap.read()
        if not ret:
            print("Failed to grab frame")
            continue

        if frame_queue.full():
            try:
                frame_queue.get_nowait()
            except queue.Empty:
                pass
        frame_queue.put(frame)

        cv2.imshow("CCTV Camera", frame)
        key = cv2.waitKey(1) & 0xFF
        if key == ord("q"):
            break

    cap.release()
    cv2.destroyAllWindows()
    print("Camera thread stopped")


# ==========================
# AI THREAD
# ==========================

def ai_thread():

    while True:

        if frame_queue.empty():
            time.sleep(0.05)
            continue

        frame = frame_queue.get()

        gray = cv2.cvtColor(frame,cv2.COLOR_BGR2GRAY)

        faces = face_detector.detectMultiScale(gray,1.3,5)

        for (x,y,w,h) in faces:

            face = frame[y:y+h,x:x+w]
            face = cv2.resize(face,(160,160))

            emb = get_embedding(face)

            if emb is None:
                continue

            distances = np.linalg.norm(targets-emb,axis=1)

            best_index = np.argmin(distances)
            best_distance = distances[best_index]

            if best_distance < MATCH_THRESHOLD:

                name = target_names[best_index]

                ts = datetime.now().strftime("%Y%m%d_%H%M%S")

                save_name = f"{name}_{ts}.jpg"

                cv2.imwrite(
                    os.path.join(FOUND_DIR,save_name),
                    frame
                )

                print("\nPERSON FOUND")
                print("Name:",name)
                print("Distance:",best_distance)
                print("Saved:",save_name)


# ==========================
# START THREADS
# ==========================

t1 = threading.Thread(target=camera_thread)
t2 = threading.Thread(target=ai_thread)

t1.start()
t2.start()

t1.join()
t2.join()