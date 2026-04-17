# 🚨 AI CCTV Surveillance System - Production Ready
## Optimized Python + OpenCV + DeepFace + Flask + MySQL

### Features
- **Real-time face detection** (Haar Cascade)
- **AI matching** (DeepFace VGG-Face, every 10th frame)
- **Face cropping** (RGB 224x224 optimized)
- **Duplicate prevention** (DB check last 1min)
- **Browser geolocation** support
- **Error resilient** (try/catch everywhere)
- **API control** from PHP website
- **Clean DB inserts** to `ai_matches` + `found_persons`

### API Endpoints (port 5001)
```
POST /update_targets  # {\"report_ids\": [1,2], \"lat\": 40.71, \"lng\": -74.00}
POST /start_ai
POST /stop_ai
GET /status
```

### Quick Start (XAMPP)
1. `cd ai_multi_test`
2. `pip install -r requirements.txt`
3. `python cctv_surveillance.py`
4. Open php/ai_match.php → Select targets → Start AI

### DB Tables Used
- `missing_reports` (load targets)
- `found_persons` (new records)
- `ai_matches` (matches w/ confidence, cropped img, GPS, timestamp)

### Optimizations Applied
- Process every 10th frame (smooth 30fps camera)
- Cropped faces only for DeepFace (faster/accurate)
- No duplicates within 1min per report
- Full error handling prevents crashes

### Files Generated
- `detections/detection_*.jpg` (full frames)
- `detections/face_crop_*.jpg` (cropped faces for DB)
- `cctv_log.txt` (events log)

**Production ready for HopeFinder integration!** 🎥🤖
