#!/usr/bin/env python3
"""Fixed launcher for AI server"""

import runpy
from pathlib import Path

if __name__ == '__main__':
    server_path = Path(__file__).resolve().parent / 'python' / 'fixed_ai_server.py'
    
    print(f'🚀 Starting FIXED AI Server: python {server_path}')
    print('✅ Globals fixed - No UnboundLocalError')
    print('Endpoints:')
    print('  POST http://localhost:5001/start_detection {\\"selected_ids\\":[1,2]}')
    print('  POST http://localhost:5001/stop_detection {}')
    print('  GET  http://localhost:5001/video_feed')
    print('  GET  http://localhost:5001/health')
    print()
    
    runpy.run_path(server_path, run_name='__main__')
