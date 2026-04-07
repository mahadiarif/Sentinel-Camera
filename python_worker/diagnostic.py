import cv2
import os
import sys

# CRITICAL FIX for PyTorch 2.6+ model loading
os.environ['TORCH_LOAD_WEIGHTS_ONLY'] = '0'

import torch
from ultralytics import YOLO

# Fix for PyTorch 2.6+ weights_only=True security restriction
# Monkey-patching torch.load to allow legacy loading for YOLOv8
import torch
original_load = torch.load
def patched_load(*args, **kwargs):
    if 'weights_only' not in kwargs:
        kwargs['weights_only'] = False
    return original_load(*args, **kwargs)
torch.load = patched_load

def test():
    print("--- METRONET DIAGNOSTIC TOOL ---")
    
    # 1. Check Directories
    base_dir = os.path.dirname(os.path.abspath(__file__))
    save_path = os.path.join(base_dir, "../public/gate_frames/diag_test.jpg")
    print(f"Base Directory: {base_dir}")
    print(f"Target Save Path: {save_path}")
    
    save_dir = os.path.dirname(save_path)
    if not os.path.exists(save_dir):
        print(f"ERROR: Directory {save_dir} does not exist. Creating it...")
        os.makedirs(save_dir, exist_ok=True)
    else:
        print(f"Directory {save_dir} exists.")

    # 2. Test Model Loading
    print("\n[STEP 1] Testing YOLOv8 Model Loading...")
    try:
        model = YOLO('yolov8n.pt')
        print("SUCCESS: Model loaded.")
    except Exception as e:
        print(f"FAILED: Model loading error: {e}")
        return

    # 3. Test Camera Access
    print("\n[STEP 2] Testing Camera Access...")
    for i in range(3):
        print(f"Trying camera index {i}...")
        cap = cv2.VideoCapture(i)
        if not cap.isOpened():
            print(f"Index {i} could not be opened.")
            continue
        
        ret, frame = cap.read()
        if ret:
            print(f"SUCCESS: Captured frame from index {i}. Size: {frame.shape}")
            
            # 4. Test File Writing
            print("\n[STEP 3] Testing File Writing...")
            try:
                success = cv2.imwrite(save_path, frame)
                if success:
                    print(f"SUCCESS: Image written to {save_path}")
                else:
                    print(f"FAILED: cv2.imwrite returned False for {save_path}")
            except Exception as e:
                print(f"ERROR: File write error: {e}")
            
            cap.release()
            print("\n--- DIAGNOSTIC COMPLETE: CAMERA IS FUNCTIONAL ---")
            return
        else:
            print(f"Index {i} opened but failed to read frame.")
        cap.release()

    print("\nFAILED: No working camera found. Indices 0-2 checked.")

if __name__ == "__main__":
    test()
