# 🚀 HopeFinder - LIVE CCTV Frontend Integration

## Your CCTV Code → Frontend Ready!

**Your code integrated:** Flask + face_recognition + dashboard/realtime.html

## 1. Install Deps (1-time)
```
cd "e:\xmapp\htdocs\HopeFinder\ai_multi_test"
venv\Scripts\activate
pip install flask face_recognition opencv-python requests numpy
```

## 2. Add Missing Faces
```
mkdir missing
copy ..\uploads\*.jpg missing\1_*.jpg  # Report ID 1
```

## 3. RUN SERVER
```
python cctv_flask_hopefinder.py
```
**See:** `Running on http://127.0.0.1:5001`

## 4. TEST
**Stream:** http://localhost:5001/video_feed
**Status:** http://localhost:5001/status

## 5. Start Detection (curl test)
```
curl -X POST http://localhost:5001/start -H "Content-Type: application/json" -d "{\"report_ids\": [1]}"
```

**Dashboard:** http://localhost/HopeFinder/dashboard/realtime.html

**Match → AUTO saves + POSTs report_detection.php!**

✅ **Production Ready!** 👮‍♂️

