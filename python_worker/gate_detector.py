import cv2
import numpy as np
from ultralytics import YOLO
import torch
import os
import glob
import logging

# Configure Logger
logging.basicConfig(level=logging.INFO, format='%(asctime)s - %(name)s - %(levelname)s - %(message)s')
logger = logging.getLogger("GateDetector")

# Fix for PyTorch 2.6+ weights_only=True security restriction
# Monkey-patching torch.load to allow legacy loading for YOLOv8
import torch
original_load = torch.load
def patched_load(*args, **kwargs):
    if 'weights_only' not in kwargs:
        kwargs['weights_only'] = False
    return original_load(*args, **kwargs)
torch.load = patched_load

class GateDetector:
    def __init__(self, base_model_path='yolov8n.pt', custom_models_dir='../public/custom_models'):
        logger.info(f"Initializing GateDetector with base model: {base_model_path}")
        self.base_model = YOLO(base_model_path)
        self.custom_models_dir = custom_models_dir
        self.custom_models = self.load_custom_models()

    def load_custom_models(self):
        models = []
        pattern = os.path.join(self.custom_models_dir, '*', 'best.pt')
        for model_path in glob.glob(pattern):
            try:
                logger.info(f"Loading custom model: {model_path}")
                models.append(YOLO(model_path))
            except Exception as e:
                logger.error(f"Failed to load custom model {model_path}: {e}")
        return models

    def detect(self, frame, min_confidence=0.45):
        all_detections = []
        
        # 1. Base YOLOv8 Model (COCO 80 classes)
        results = self.base_model(frame, verbose=False, conf=min_confidence)[0]
        for box in results.boxes:
            all_detections.append({
                'class': results.names[int(box.cls[0])],
                'confidence': float(box.conf[0]),
                'bbox': box.xyxy[0].tolist() # x1, y1, x2, y2
            })
        
        # 2. Custom Models
        for cm in self.custom_models:
            c_results = cm(frame, verbose=False, conf=min_confidence)[0]
            for box in c_results.boxes:
                all_detections.append({
                    'class': c_results.names[int(box.cls[0])],
                    'confidence': float(box.conf[0]),
                    'bbox': box.xyxy[0].tolist()
                })

        # 3. Process Detections and Annotate Frame
        annotated_frame = frame.copy()
        object_names = []
        person_detected = False
        max_confidence = 0.0

        for det in all_detections:
            cls = det['class']
            conf = det['confidence']
            bbox = [int(v) for v in det['bbox']]
            
            object_names.append(cls)
            if cls == 'person':
                person_detected = True
            
            max_confidence = max(max_confidence, conf)

            # Assign color based on class
            if cls == 'person': color = (0, 0, 255) # Red
            elif cls in ['car', 'truck', 'bus', 'motorcycle']: color = (255, 229, 0) # Cyan-ish
            else: color = (0, 229, 255) # MetroNet Blue

            # Draw Box
            cv2.rectangle(annotated_frame, (bbox[0], bbox[1]), (bbox[2], bbox[3]), color, 2)
            
            # Draw Label
            label = f"{cls} {conf:.1%}"
            (w, h), _ = cv2.getTextSize(label, cv2.FONT_HERSHEY_SIMPLEX, 0.5, 1)
            cv2.rectangle(annotated_frame, (bbox[0], bbox[1] - 20), (bbox[0] + w, bbox[1]), color, -1)
            cv2.putText(annotated_frame, label, (bbox[0], bbox[1] - 5), cv2.FONT_HERSHEY_SIMPLEX, 0.5, (0, 0, 0), 1)

        return {
            'detections': all_detections,
            'object_names': object_names,
            'person_detected': person_detected,
            'object_count': len(all_detections),
            'annotated_frame': annotated_frame,
            'max_confidence': max_confidence
        }

if __name__ == "__main__":
    # Test Detector
    detector = GateDetector()
    cap = cv2.VideoCapture(0)
    while True:
        ret, frame = cap.read()
        if not ret: break
        
        result = detector.detect(frame)
        cv2.imshow("MetroNet Test", result['annotated_frame'])
        
        if cv2.waitKey(1) & 0xFF == ord('q'):
            break
    cap.release()
    cv2.destroyAllWindows()
