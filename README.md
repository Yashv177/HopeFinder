# HopeFinder - AI Based Lost People Finder Web Application 

HopeFinder is a full-stack web application for managing missing persons reports, with admin/police/user dashboards, real-time notifications, and AI-powered CCTV face recognition integration using DeepFace and OpenCV.

## Features
- User registration/login and missing persons report submission
- Admin dashboard for managing users, reports, police assignments, AI matches
- Police dashboard for assigned cases and statistics
- Real-time AI face matching from CCTV streams against missing persons database
- Live detections polling, PDF report generation, notifications
- Responsive frontend with charts, maps, and realtime updates

## Prerequisites
- Windows with XAMPP (Apache + MySQL/phpMyAdmin on ports 80/3306)
- PHP 8.0+
- Python 3.10+
- Git (optional for updates)
- Webcam/CCTV for AI testing

## Quick Start
1. Start XAMPP Apache and MySQL
2. Import Database/*.sql files via phpMyAdmin (database: `hopefinder`)
3. Access web app: http://localhost/HopeFinder/php/login.php
4. Run AI: 
   - `ai_system/run_ai.bat` (port 5001)
   - Or `ai_multi_test/RUN_ALL.bat` (port 5002 + auto-opens dashboard)
5. Login as admin (check Database/insert_admin.sql) and test reports/AI

## Detailed Setup

### 1. Database Setup
- Open phpMyAdmin: http://localhost/phpmyadmin
- Create database `hopefinder` (utf8mb4 charset)
- Import all files from `Database/` folder in order:
  1. `hopefinder_realtime.sql` (core tables: missing_reports, live_detections)
  2. `admin_dashboard_schema.sql` / `create_missing_tables.sql` etc.
  3. `insert_admin.sql` (creates default admin user)
  4. Any schema_updates.sql
- Verify: Run `SELECT * FROM users WHERE role='admin';`
- DB Config (Database/Conn_db.php): `localhost:3307`, user `root`, pass ``, db `hopefinder`

### 2. Web Application Setup
- Ensure XAMPP Apache/MySQL running
- Project accessible at http://localhost/HopeFinder/
- Key pages:
  | Role | Login URL | Dashboard |
  |------|-----------|-----------|
  | User | php/login.php | php/Dashboard.php |
  | Admin | php/login.php (admin creds) | admin/ |
  | Police | php/login.php (police creds) | police/ |
- Create folders if missing: `uploads/`, `image/`, `ai_multi_test/detections/`, `ai_system/uploads/missing_persons/`

### 3. AI System Setup
Two AI implementations (both Flask + DeepFace):

#### Option A: Full AI System (ai_system/)
```
cd ai_system
double-click run_ai.bat
```
- Auto-creates venv, installs deps (deepface, opencv-python, flask, etc.)
- Starts server: http://127.0.0.1:5001
- Frontend: ai_system/frontend/index.html
- Backend logs: ai_system/logs/ai.log

#### Option B: Simple Production Test (ai_multi_test/)
```
cd ai_multi_test
double-click RUN_ALL.bat
```
- Starts AI on port 5002
- Auto-opens browser with realtime.html dashboard
- Main script: simple_test_working.py

**Python Dependencies** (auto-installed by .bat):
```
deepface==0.0.79-0.0.93
opencv-python==4.9.0-4.10.0
flask==3.0+
numpy, Pillow, requests
```

**DB Table for AI** (add via phpMyAdmin):
```sql
CREATE TABLE IF NOT EXISTS ai_detections (
  id INT AUTO_INCREMENT PRIMARY KEY,
  report_id INT,
  image_path VARCHAR(500),
  confidence DECIMAL(5,3),
  timestamp DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

### 4. Folders Structure
```
HopeFinder/
├── php/          # Core PHP (login, dashboard, reports)
├── admin/        # Admin APIs/panels
├── police/       # Police dashboard
├── api/          # Notification/REST APIs
