@echo off
echo Starting HopeFinder AI CCTV Server...
cd /d "e:\xmapp\htdocs\HopeFinder\python-ai"
call venv\Scripts\activate.bat
python cctv_server_deepface.py
pause

