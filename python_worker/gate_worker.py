import cv2
import json
import logging
import os
import threading
import time
from datetime import datetime
from queue import Queue

import numpy as np
import requests
from dotenv import load_dotenv

from gate_detector import GateDetector

load_dotenv()

logging.basicConfig(level=logging.INFO, format='%(asctime)s - %(levelname)s - %(message)s')
logger = logging.getLogger("GateWorker")


def load_camera_config():
    cameras_json = os.getenv('CAMERAS', '').strip()
    if cameras_json:
        try:
            cameras = json.loads(cameras_json)
            if isinstance(cameras, list) and cameras:
                return cameras[0]
        except Exception as exc:
            logger.warning(f"Failed to parse CAMERAS env, using legacy camera config: {exc}")

    return {
        'id': os.getenv('GATE_CAMERA_ID', '1'),
        'name': os.getenv('GATE_CAMERA_NAME', 'Laptop Camera'),
        'location': os.getenv('GATE_CAMERA_LOCATION', 'Main Entrance'),
        'type': os.getenv('GATE_CAMERA_TYPE', 'usb'),
        'source': os.getenv('GATE_CAMERA_SOURCE', '0'),
    }


CAMERA_CONFIG = load_camera_config()
CAMERA_TYPE = str(CAMERA_CONFIG.get('type', 'usb'))
CAMERA_SOURCE = str(CAMERA_CONFIG.get('source', '0'))
CAMERA_ID = str(CAMERA_CONFIG.get('id', '1'))
CAMERA_NAME = str(CAMERA_CONFIG.get('name', 'Laptop Camera'))
CAMERA_LOCATION = str(CAMERA_CONFIG.get('location', 'Main Entrance'))
LARAVEL_URL = os.getenv('LARAVEL_URL', 'http://127.0.0.1:8000')
TOKEN = os.getenv('LARAVEL_INTERNAL_TOKEN', 'secret')
DETECTION_INTERVAL = float(os.getenv('DETECTION_INTERVAL', '3'))
MIN_CONFIDENCE = float(os.getenv('MIN_CONFIDENCE', '0.45'))

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
FRAMES_DIR = os.path.normpath(os.path.join(BASE_DIR, os.getenv('FRAMES_DIR', '../public/frames')))
SNAPSHOT_DIR = os.path.normpath(os.path.join(BASE_DIR, os.getenv('SNAPSHOTS_DIR', os.getenv('SNAPSHOT_DIR', '../public/snapshots'))))
FRAME_SAVE_PATH = os.path.join(FRAMES_DIR, f'cam_{CAMERA_ID}.jpg')

os.makedirs(FRAMES_DIR, exist_ok=True)
os.makedirs(SNAPSHOT_DIR, exist_ok=True)

logger.info(f"Camera Source:   {CAMERA_SOURCE}")
logger.info(f"Frame Save Path: {FRAME_SAVE_PATH}")
logger.info(f"Snapshot Dir:    {SNAPSHOT_DIR}")


def write_jpeg(path, frame, quality=70):
    tmp = path + '.tmp'
    ret, buf = cv2.imencode('.jpg', frame, [cv2.IMWRITE_JPEG_QUALITY, quality])
    if not ret:
        return False

    try:
        with open(tmp, 'wb') as file_handle:
            file_handle.write(buf.tobytes())

        for _ in range(5):
            try:
                if os.path.exists(path):
                    os.remove(path)
                os.rename(tmp, path)
                return True
            except OSError:
                time.sleep(0.005)
    except Exception as exc:
        logger.warning(f"write_jpeg error: {exc}")

    return False


def make_no_signal_frame():
    frame = np.zeros((480, 800, 3), dtype=np.uint8)
    frame[:, :] = [14, 10, 5]
    cv2.putText(frame, 'NO SIGNAL', (235, 200),
                cv2.FONT_HERSHEY_SIMPLEX, 2.0, (0, 229, 255), 3, cv2.LINE_AA)
    cv2.putText(frame, 'AI Worker: searching for camera device...', (95, 270),
                cv2.FONT_HERSHEY_SIMPLEX, 0.65, (70, 85, 95), 2, cv2.LINE_AA)
    cv2.putText(frame, 'MetroNet Surveillance System', (215, 330),
                cv2.FONT_HERSHEY_SIMPLEX, 0.6, (35, 55, 65), 1, cv2.LINE_AA)
    return frame


NO_SIGNAL_FRAME = make_no_signal_frame()


class VideoStream:
    def __init__(self, source):
        self.source = source
        self.cap = None
        self.frame = None
        self.stopped = False
        self.lock = threading.Lock()
        self._try_connect()

    def _open_http_or_rtsp(self, source_raw):
        logger.info(f"Connecting to stream: {source_raw}")
        for backend in [cv2.CAP_FFMPEG, cv2.CAP_ANY]:
            cap = cv2.VideoCapture(source_raw, backend)
            if not cap.isOpened():
                cap.release()
                continue

            time.sleep(1.5)
            for _ in range(15):
                ret, frame = cap.read()
                if ret and frame is not None:
                    logger.info("CONNECTED: network stream is live.")
                    self.cap = cap
                    return
                time.sleep(0.2)
            cap.release()

    def _try_connect(self):
        source_raw = self.source
        is_network = isinstance(source_raw, str) and (
            source_raw.startswith('rtsp://') or
            source_raw.startswith('http://') or
            source_raw.startswith('https://')
        )

        if is_network:
            self._open_http_or_rtsp(source_raw)
            if self.cap:
                return
            logger.warning("Network camera stream could not be read. Check the phone IP Webcam app and URL.")
        else:
            source_val = int(source_raw) if str(source_raw).isdigit() else 0
            indices = [source_val] + [i for i in range(3) if i != source_val]
            for idx in indices:
                for backend in [cv2.CAP_ANY, cv2.CAP_MSMF, cv2.CAP_DSHOW]:
                    logger.info(f"Trying camera {idx} with backend {backend}...")
                    cap = cv2.VideoCapture(idx, backend)
                    if not cap.isOpened():
                        cap.release()
                        continue
                    time.sleep(0.5)
                    for _ in range(10):
                        ret, frame = cap.read()
                        if ret and frame is not None:
                            logger.info(f"CONNECTED: Camera {idx} (backend {backend})")
                            self.cap = cap
                            return
                        time.sleep(0.1)
                    logger.warning(f"Camera {idx} backend {backend}: opened but no frames. Releasing.")
                    cap.release()
                    time.sleep(1)

        logger.warning("No camera found. The worker will keep retrying and show NO SIGNAL.")

    def start(self):
        threading.Thread(target=self._update_loop, daemon=True).start()
        return self

    def _update_loop(self):
        while not self.stopped:
            if not self.cap or not self.cap.isOpened():
                self._try_connect()
                if not self.cap:
                    time.sleep(5)
                    continue

            ret, frame = self.cap.read()
            if not ret:
                logger.warning("Frame read failed. Reconnecting...")
                self.cap.release()
                self.cap = None
                continue

            with self.lock:
                self.frame = frame

    def read(self):
        with self.lock:
            return self.frame.copy() if self.frame is not None else None

    def stop(self):
        self.stopped = True
        if self.cap:
            self.cap.release()


class NotificationQueue:
    def __init__(self):
        self.queue = Queue()
        threading.Thread(target=self._worker, daemon=True).start()

    def push(self, data):
        self.queue.put(data)

    def _worker(self):
        while True:
            data = self.queue.get()
            try:
                headers = {
                    'Authorization': f'Bearer {TOKEN}',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                }
                response = requests.post(
                    f"{LARAVEL_URL}/api/gate/internal/alert",
                    json=data,
                    headers=headers,
                    timeout=5,
                )
                if response.status_code != 200:
                    logger.error(f"Laravel alert failed: {response.status_code} {response.text}")
                else:
                    logger.info(f"Laravel alert ok: {response.text}")
            except Exception as exc:
                logger.error(f"Network error in alert thread: {exc}")
            self.queue.task_done()


def run_worker():
    logger.info("INIT: Starting MetroNet AI Worker...")

    stream = VideoStream(CAMERA_SOURCE).start()
    notifier = NotificationQueue()
    detector = GateDetector()

    logger.info("READY: System is live and monitoring.")

    last_ai_run = 0
    last_frame_save = 0
    frame_interval = 0.04

    while True:
        try:
            now = time.time()

            if now - last_frame_save >= frame_interval:
                frame = stream.read()
                display = frame if frame is not None else NO_SIGNAL_FRAME
                if not write_jpeg(FRAME_SAVE_PATH, display, quality=70):
                    logger.warning(f"Failed to save frame to {FRAME_SAVE_PATH}")
                last_frame_save = now

            if now - last_ai_run >= DETECTION_INTERVAL:
                frame = stream.read()
                if frame is not None:
                    result = detector.detect(frame, min_confidence=MIN_CONFIDENCE)
                    if result['object_count'] > 0:
                        timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
                        snap_name = f"cam_{CAMERA_ID}_{timestamp}.jpg"
                        snap_path = os.path.join(SNAPSHOT_DIR, snap_name)
                        write_jpeg(snap_path, result['annotated_frame'], quality=90)

                        all_objects = result['object_names']
                        carried_objects = [obj for obj in all_objects if obj != 'person']
                        person_count = sum(1 for obj in all_objects if obj == 'person')

                        notifier.push({
                            'camera_id': CAMERA_ID,
                            'camera_name': CAMERA_NAME,
                            'camera_location': CAMERA_LOCATION,
                            'person_count': person_count,
                            'carried_objects': carried_objects,
                            'all_objects': all_objects,
                            'object_count': len(carried_objects),
                            'max_confidence': float(round(result['max_confidence'], 4)),
                            'snapshot_path': snap_path,
                        })
                        logger.info(f"DETECTED: {all_objects} ({snap_name})")

                last_ai_run = now

            time.sleep(0.01)
        except Exception as exc:
            logger.error(f"Main loop error: {exc}")
            time.sleep(1)


if __name__ == "__main__":
    try:
        run_worker()
    except KeyboardInterrupt:
        logger.info("MetroNet stopped by user.")
    except Exception as exc:
        logger.critical(f"FATAL ERROR: {exc}")
