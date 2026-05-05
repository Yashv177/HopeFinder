@echo off
echo Starting HopeFinder AI Missing Person Detection System...
echo.
echo Flask API: http://127.0.0.1:5001
echo Dashboard selects targets -> sends to here -> real-time matching
echo Press Ctrl+C to stop
echo.

cd /d "e:\xmapp\htdocs\HopeFinder\ai_system"

REM ==============================
REM CREATE / ACTIVATE VENV
REM ==============================

if not exist venv (
echo Creating virtual environment...
"C:\Program Files\Python310\python.exe" -m venv venv

```
call venv\Scripts\activate.bat

echo Installing dependencies...

pip install --upgrade pip
pip install numpy==1.26.4
pip install opencv-python==4.9.0.80
pip install cmake
pip install dlib-bin
pip install face-recognition

echo Virtual environment ready!
```

) else (
echo Activating existing virtual environment...
call venv\Scripts\activate.bat
)

REM ==============================
REM VERIFY INSTALLATION
REM ==============================

python -c "import cv2, numpy, face_recognition; print('OpenCV:', cv2.__version__, 'NumPy:', numpy.__version__)" || (
echo Fixing dependencies...
pip uninstall numpy opencv-python face-recognition -y
pip install numpy==1.26.4 opencv-python==4.9.0.80 face-recognition
)

REM ==============================
REM START SERVER
REM ==============================

echo Starting FIXED AI Server...
python app_fixed.py

pause
