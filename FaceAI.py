from deepface import DeepFace
import cv2

# Step 1: Input photo (target image)
target_img = "person1.jpg"     # yaha apna photo rakhna
person_name = "Yash"           # yaha us photo ka naam likhna

cap = cv2.VideoCapture(0)
print("Press 'q' to exit...")

while True:
    ret, frame = cap.read()
    if not ret:
        print("Camera not working!")
        break

    # Save current frame
    cv2.imwrite("live.jpg", frame)

    match_found = False

    try:
        # Compare live frame with target image
        result = DeepFace.verify(
            img1_path="live.jpg",
            img2_path=target_img,
            model_name="Facenet"
        )

        if result["verified"]:
            match_found = True
            text = f"Match Found: {person_name}"
        else:
            text = "No Match"

    except:
        text = "Face Not Detected"

    # Step 2: Text on Camera Window
    cv2.putText(frame, text, (20, 40),
                cv2.FONT_HERSHEY_SIMPLEX, 1,
                (0, 255, 0) if match_found else (0, 0, 255), 2)

    # Show window
    cv2.imshow("Live Camera", frame)

    if match_found:
        print("✅ Face Match Found!")
        break

    # Exit manually
    if cv2.waitKey(1) & 0xFF == ord('q'):
        break

cap.release()
cv2.destroyAllWindows()
