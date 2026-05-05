"""
AI Missing Person Detection Config
"""

import os

# Paths (absolute, relative to HopeFinder root)
BASE_DIR = r"e:/xmapp/htdocs/HopeFinder"
UPLOADS_DIR = os.path.join(BASE_DIR, "uploads")
AI_FOUND_DIR = os.path.join(BASE_DIR, "uploads", "ai_found")
os.makedirs(AI_FOUND_DIR, exist_ok=True)

# API Endpoints
PHP_API_BASE = "http://localhost/HopeFinder/admin/api/"
GET_TARGETS_URL = f"{PHP_API_BASE}get_missing_persons.php"
REPORT_MATCH_URL = f"{PHP_API_BASE}update_match.php"

# AI Settings
MODEL_NAME = "Facenet"
MATCH_THRESHOLD = 0.60  # Lower for multiple targets (cosine distance)
FRAME_WIDTH = 640
FRAME_HEIGHT = 480
TARGET_SIZE = (160, 160)

# Flask
FLASK_HOST = "127.0.0.1"
FLASK_PORT = 5001
FLASK_DEBUG = False

# Matching
TEMP_FRAME_PATH = "temp_frame.jpg"
