import cv2
import os

missing_dir = 'missing'
for f in os.listdir(missing_dir):
    path = os.path.join(missing_dir, f)
    img = cv2.imread(path)
    if img is not None:
        h, w = img.shape[:2]
        print(f'{f}: {w}x{h} - OK')
    else:
        print(f'{f}: INVALID IMAGE')

