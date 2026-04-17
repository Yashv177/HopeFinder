import cv2
print('Simple webcam test - CAP_DSHOW')
cap = cv2.VideoCapture(0, cv2.CAP_DSHOW)
cap.set(cv2.CAP_PROP_BUFFERSIZE, 1)
ret = True
count = 0
while ret and count < 100:
    ret, frame = cap.read()
    count += 1
cap.release()
print('Test complete - frames read:', count)
