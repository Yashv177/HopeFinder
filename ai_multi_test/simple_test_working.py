import cv2
from flask import Flask, Response
import time

app = Flask(__name__)

cap = None
running = False

@app.route('/start')
def start_camera():
    global cap, running
    running = True
    cap = cv2.VideoCapture(0)
    cap.set(cv2.CAP_PROP_FRAME_WIDTH, 640)
    cap.set(cv2.CAP_PROP_FRAME_HEIGHT, 480)
    return 'Camera started!'

@app.route('/video_feed')
def video_feed():
    def generate_frames():
        while True:
            if cap and cap.isOpened():
                ret, frame = cap.read()
                if ret:
                    ret, buffer = cv2.imencode('.jpg', frame)
                    frame_bytes = buffer.tobytes()
                    yield (b'--frame\r\n'
                           b'Content-Type: image/jpeg\r\n\r\n' + frame_bytes + b'\r\n')
            time.sleep(0.03)
    return Response(generate_frames(), mimetype='multipart/x-mixed-replace; boundary=frame')

@app.route('/test')
def test():
    if cap and cap.isOpened():
        ret, frame = cap.read()
        return f'Camera OK. Shape: {frame.shape if ret else "NO FRAME"}'
    return 'Camera not open'

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5002, debug=True)

