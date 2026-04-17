import cv2
import time

print('Testing stable camera... Press Q to quit')

cap = cv2.VideoCapture(0, cv2.CAP_DSHOW)
if not cap.isOpened():
    print('Fallback to default...')
    cap = cv2.VideoCapture(0)
if not cap.isOpened():
    print('Camera not available!')
    exit()

cap.set(cv2.CAP_PROP_FRAME_WIDTH, 640)
cap.set(cv2.CAP_PROP_FRAME_HEIGHT, 480)
cap.set(cv2.CAP_PROP_FPS, 30)
cap.set(cv2.CAP_PROP_BUFFERSIZE, 1)

fps_counter = 0
fps_time = time.time()

while True:
    ret, frame = cap.read()
    if not ret:
        print('Frame read failed - retrying...')
        continue

    # FPS overlay
    fps_counter += 1
    if time.time() - fps_time > 1.0:
        current_fps = fps_counter
        fps_counter = 0
        fps_time = time.time()
        cv2.putText(frame, f'Stable FPS: {current_fps}', (10, 30), cv2.FONT_HERSHEY_SIMPLEX, 1, (0,255,0), 2)

    cv2.imshow('Stable Camera Test', frame)

    if cv2.waitKey(1) & 0xFF == ord('q'):
        break

cap.release()
cv2.destroyAllWindows()
print('Test complete - camera stable?')
