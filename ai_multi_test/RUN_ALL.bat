@echo off
echo Starting HopeFinder AI CCTV System...
echo ================================
cd /d "e:\xmapp\htdocs\HopeFinder\ai_multi_test"
call venv\Scripts\activate.bat
pip install flask opencv-python --quiet
echo.
echo [1/2] Starting AI Server...
start "AI Server" python simple_test_working.py
echo.
echo [2/2] Opening Dashboard...
timeout /t 3 /nobreak >nul
start http://localhost:5002/video_feed
start http://localhost/HopeFinder/dashboard/realtime.html
echo.
echo LIVE! Check browser tabs above ^ (Ctrl+C to stop server)
pause

