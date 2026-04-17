#!/usr/bin/env python3
\"\"\"Production-Ready Real-Time Face Recognition System
Senior CV Engineer Implementation
Robust threading, high FPS, safe queues, ArcFace, clean shutdown\"\"\"

import cv2
import os
import numpy as np
import time
import logging
from datetime import datetime
from deepface import DeepFace
from collections import deque
import threading
from queue import Queue, Empty
import warnings
warnings.filterwarnings('ignore')

# Suppress DeepFace logs
logging.getLogger('deepface').setLevel(logging.ERROR)
logging.basicConfig(level=logging.INFO)

class RobustFaceRecognizer:
    def __init__(self, missing_dir='missing', found_dir='found'):
        self.missing_dir = missing_dir
        self.found_dir = found_dir
        self.all_embeddings = None
        self.target_names = []
        self.target_ids = []
        self.match_threshold = 0.60
        
        self.cap = None
        self.frame_queue = Queue(maxsize=3)
        self.shutdown_event = threading.Event()
        
        self.best_match = {'idx': -1, 'distance': 1.0}
        self.match_lock = threading.Lock()
