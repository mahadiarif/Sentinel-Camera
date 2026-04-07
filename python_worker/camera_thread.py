import os
import threading
import time
from datetime import datetime

import cv2
import numpy as np

from camera_config import CameraConfig
from detector import detect
from logger import log_detect, log_error, log_info, log_success, log_warning


class CameraThread(threading.Thread):
    MAX_RECONNECT_ATTEMPTS = 10
    RECONNECT_DELAY = 5

    def __init__(self, camera_config: CameraConfig, notifier, config):
        super().__init__(daemon=True)
        self.camera_config = camera_config
        self.cam_id = camera_config.id
        self.cam_name = camera_config.name
        self.cam_location = camera_config.location
        self.cam_type = str(camera_config.type).lower()
        self.cam_source = int(camera_config.source) if self.cam_type == 'usb' else camera_config.source
        self.notifier = notifier
        self.config = config
        
        self.cap = None
        self.latest_frame = None
        self.frame_lock = threading.Lock()
        
        self.running = True
        self.frame_count = 0
        self.detection_count = 0
        self.last_detection_time = 0
        self.detecting = False
        self.detect_lock = threading.Lock()
        
        # FPS calculation attributes
        self.fps = 0.0
        self.prev_time = time.time()
        
        self.frame_path = os.path.join(config.FRAMES_DIR, f"cam_{self.cam_id}.jpg")
        os.makedirs(config.FRAMES_DIR, exist_ok=True)
        os.makedirs(config.SNAPSHOTS_DIR, exist_ok=True)
        self.no_signal_frame = self._make_no_signal_frame()

    def _frame_grabber(self):
        """Dedicated thread to constantly grab the latest frame from the source to prevent lag."""
        log_info(self.cam_name, "Grabber thread started")
        while self.running:
            if self.cap and self.cap.isOpened():
                ret, frame = self.cap.read()
                if ret and frame is not None:
                    # Memory Optimization: Downscale immediately if the image is too large
                    # This prevents OpenCV "Insufficient memory" errors for 1080p+ streams on weak machines
                    target_width = 800
                    h, w = frame.shape[:2]
                    if w > target_width:
                        new_h = int(h * (target_width / w))
                        frame = cv2.resize(frame, (target_width, new_h), interpolation=cv2.INTER_LINEAR)
                    
                    with self.frame_lock:
                        self.latest_frame = frame
                else:
                    time.sleep(0.01)
            else:
                time.sleep(0.5)

    def run(self):
        try:
            log_info(self.cam_name, f"Starting - type:{self.cam_type} source:{self.cam_source}")

            # Start the frame grabber thread
            threading.Thread(target=self._frame_grabber, daemon=True).start()

            if not self.connect():
                log_warning(self.cam_name, "Initial connection failed - entering standby reconnect mode")
                self.save_frame(self.no_signal_frame)
            else:
                log_success(self.cam_name, "Camera connected")

            while self.running:
                try:
                    if not self.cap or not self.cap.isOpened():
                        self.save_frame(self.no_signal_frame)
                        if not self.reconnect():
                            time.sleep(self.RECONNECT_DELAY)
                            continue

                    # Get and Copy the latest frame from the grabber
                    frame = None
                    with self.frame_lock:
                        if self.latest_frame is not None:
                            frame = self.latest_frame.copy()
                    
                    if frame is None:
                        time.sleep(0.01)
                        continue

                    # Calculate processing FPS
                    curr_time = time.time()
                    diff = curr_time - self.prev_time
                    if diff > 0:
                        self.fps = 1.0 / diff
                    self.prev_time = curr_time

                    self.frame_count += 1
                    
                    # Update Dashboard Image (Throttled but consistent)
                    # For a 'Live Tracker' feel, we need to save frames that include annotations
                    # So we'll let the detection thread handle the saving when it completes, 
                    # but save raw frames if no detection is running
                    if not self.detecting:
                        if self.frame_count % 2 == 0:
                            # Save frame without overlay
                            self.save_frame(frame)

                    # Periodically run AI Detection
                    if self.frame_count >= self.config.DETECTION_INTERVAL and not self.detecting:
                        self.frame_count = 0
                        self.start_detection(frame)
                        
                    time.sleep(0.02)
                except Exception as exc:
                    log_error(self.cam_name, f"Loop error: {exc}")
                    time.sleep(0.1)
        except Exception as exc:
            log_error(self.cam_name, f"Thread fatal error: {exc}")
        finally:
            self.cleanup()

    def connect(self):
        try:
            if self.cap:
                self.cap.release()

            source_text = str(self.cam_source)
            is_network_source = source_text.startswith('rtsp://') or source_text.startswith('http://') or source_text.startswith('https://')

            if is_network_source:
                self.cap = cv2.VideoCapture(source_text, cv2.CAP_FFMPEG)
                if not self.cap.isOpened():
                    self.cap.release()
                    self.cap = cv2.VideoCapture(source_text)
            else:
                self.cap = cv2.VideoCapture(self.cam_source)

            self.cap.set(cv2.CAP_PROP_FRAME_WIDTH, 640)
            self.cap.set(cv2.CAP_PROP_FRAME_HEIGHT, 480)
            self.cap.set(cv2.CAP_PROP_BUFFERSIZE, 1)
            
            return self.cap.isOpened()
        except Exception as exc:
            log_error(self.cam_name, f"Connect error: {exc}")
            return False

    def reconnect(self):
        try:
            if self.cap:
                self.cap.release()
        except Exception:
            pass

        for attempt in range(1, self.MAX_RECONNECT_ATTEMPTS + 1):
            try:
                log_warning(self.cam_name, f"Reconnect attempt {attempt}/{self.MAX_RECONNECT_ATTEMPTS}...")
                time.sleep(self.RECONNECT_DELAY)
                if self.connect():
                    log_success(self.cam_name, "Reconnected successfully")
                    return True
            except Exception as exc:
                log_error(self.cam_name, f"Reconnect error: {exc}")

        log_error(self.cam_name, "All reconnect attempts failed")
        self.cap = None
        return False

    def save_frame(self, frame):
        """Saves current frame to disk for the web dashboard."""
        try:
            target_width = 800
            height, width = frame.shape[:2]
            if width > target_width:
                target_height = int(height * (target_width / width))
                frame = cv2.resize(frame, (target_width, target_height), interpolation=cv2.INTER_LINEAR)

            root, ext = os.path.splitext(self.frame_path)
            tmp_path = f"{root}.tmp{ext or '.jpg'}"
            ok, encoded = cv2.imencode('.jpg', frame, [int(cv2.IMWRITE_JPEG_QUALITY), 65])
            if not ok:
                return

            with open(tmp_path, 'wb') as file_handle:
                file_handle.write(encoded.tobytes())

            if os.path.exists(self.frame_path):
                os.remove(self.frame_path)
            os.replace(tmp_path, self.frame_path)
        except Exception as exc:
            # log_error(self.cam_name, f"Frame save error: {exc}")
            pass

    def run_detection(self, frame):
        try:
            with self.detect_lock:
                self.detecting = True

            # Perform detection with FPS and Tracker info
            result = detect(
                frame,
                min_confidence=self.config.MIN_CONFIDENCE,
                custom_models_dir=self.config.CUSTOM_MODELS_DIR,
                model_path=self.config.MODEL_PATH,
                fps=self.fps,
                tracker_name="bytetrack"
            )

            # Always save the annotated frame to disk to show it in the dashboard
            self.save_frame(result['annotated_frame'])

            if result['person_count'] > 0 or result['object_count'] > 0:
                summary_parts = []
                for ps in result.get('person_summary', []):
                    tid = ps.get('track_id', '?')
                    objs = ps.get('carried_objects', [])
                    if objs:
                        summary_parts.append(f"Person#{tid}→{objs}")
                    else:
                        summary_parts.append(f"Person#{tid}→[no objs]")
                
                summary_str = ' | '.join(summary_parts) if summary_parts \
                             else f"{result['person_count']} person(s)"
                
                log_detect(self.cam_name,
                    f"{summary_str} | conf:{result['max_confidence']:.0%}")

                snapshot_path = None
                if self.config.SNAPSHOT_ON_DETECTION:
                    snapshot_path = self.save_snapshot(result['annotated_frame'])
                    self.manage_snapshot_limit()

                self.notifier.send_detection(
                    camera_id=self.cam_id,
                    camera_name=self.cam_name,
                    camera_location=self.cam_location,
                    result=result,
                    snapshot_path=snapshot_path,
                )
                self.detection_count += 1
                self.last_detection_time = time.time()
        except Exception as exc:
            log_error(self.cam_name, f"Detection error: {exc}")
        finally:
            with self.detect_lock:
                self.detecting = False

    def start_detection(self, frame):
        try:
            frame_copy = frame.copy()
            worker = threading.Thread(
                target=self.run_detection,
                args=(frame_copy,),
                daemon=True,
            )
            worker.start()
        except Exception as exc:
            log_error(self.cam_name, f"Detection dispatch error: {exc}")
            with self.detect_lock:
                self.detecting = False

    def save_snapshot(self, annotated_frame):
        try:
            ts = datetime.now().strftime('%Y%m%d_%H%M%S_%f')[:19]
            filename = f"cam_{self.cam_id}_{ts}.jpg"
            path = os.path.join(self.config.SNAPSHOTS_DIR, filename)
            cv2.imwrite(path, annotated_frame)
            return path
        except Exception as exc:
            log_error(self.cam_name, f"Snapshot save error: {exc}")
            return None

    def manage_snapshot_limit(self):
        try:
            snaps = sorted(
                [
                    os.path.join(self.config.SNAPSHOTS_DIR, file_name)
                    for file_name in os.listdir(self.config.SNAPSHOTS_DIR)
                    if file_name.endswith('.jpg')
                ],
                key=os.path.getmtime,
            )

            while len(snaps) > self.config.MAX_SNAPSHOTS:
                os.remove(snaps.pop(0))
        except Exception as exc:
            log_error(self.cam_name, f"Snapshot cleanup error: {exc}")

    def stop(self):
        self.running = False

    def _make_no_signal_frame(self):
        try:
            frame = np.zeros((480, 800, 3), dtype=np.uint8)
            frame[:, :] = [14, 10, 5]
            cv2.putText(frame, 'NO SIGNAL', (220, 190),
                        cv2.FONT_HERSHEY_SIMPLEX, 1.8, (0, 229, 255), 3, cv2.LINE_AA)
            cv2.putText(frame, self.cam_name[:28], (240, 245),
                        cv2.FONT_HERSHEY_SIMPLEX, 0.8, (200, 220, 230), 2, cv2.LINE_AA)
            cv2.putText(frame, 'Waiting for camera reconnect...', (180, 290),
                        cv2.FONT_HERSHEY_SIMPLEX, 0.7, (70, 85, 95), 2, cv2.LINE_AA)
            return frame
        except Exception:
            return np.zeros((480, 800, 3), dtype=np.uint8)

    def cleanup(self):
        try:
            if self.cap:
                self.cap.release()
        except Exception:
            pass
        log_info(self.cam_name, "Thread stopped")
