import os
import sys
from dataclasses import dataclass
from pathlib import Path

from dotenv import load_dotenv

from camera_config import load_camera_configs


def _parse_bool(value: str | None, default: bool) -> bool:
    try:
        if value is None:
            return default
        return str(value).strip().lower() in {'1', 'true', 'yes', 'on'}
    except Exception:
        return default


def _fail(message: str) -> None:
    print(f"[CONFIG ERROR] {message}", file=sys.stderr)
    raise SystemExit(1)


@dataclass
class Config:
    CAMERAS: list
    LARAVEL_URL: str
    LARAVEL_INTERNAL_TOKEN: str
    MODEL_PATH: str
    FRAMES_DIR: str
    SNAPSHOTS_DIR: str
    DETECTION_INTERVAL: int
    MIN_CONFIDENCE: float
    SNAPSHOT_ON_DETECTION: bool
    MAX_SNAPSHOTS: int
    LOG_LEVEL: str
    BASE_DIR: str
    CUSTOM_MODELS_DIR: str
    CAMERA_SYNC_INTERVAL: int

    def __init__(self) -> None:
        try:
            base_dir = Path(__file__).resolve().parent
            load_dotenv(base_dir / '.env')

            self.BASE_DIR = str(base_dir)
            self.LARAVEL_URL = os.getenv('LARAVEL_URL', 'http://127.0.0.1:8000').strip()
            self.LARAVEL_INTERNAL_TOKEN = os.getenv('LARAVEL_INTERNAL_TOKEN', '').strip()
            self.MODEL_PATH = str((base_dir / os.getenv('MODEL_PATH', 'yolo26n.pt')).resolve())
            self.FRAMES_DIR = str((base_dir / os.getenv('FRAMES_DIR', '../public/frames')).resolve())
            self.SNAPSHOTS_DIR = str((base_dir / os.getenv('SNAPSHOTS_DIR', '../public/snapshots')).resolve())
            self.CUSTOM_MODELS_DIR = str((base_dir / '../public/custom_models').resolve())
            self.DETECTION_INTERVAL = int(os.getenv('DETECTION_INTERVAL', '3'))
            self.MIN_CONFIDENCE = float(os.getenv('MIN_CONFIDENCE', '0.45'))
            self.SNAPSHOT_ON_DETECTION = _parse_bool(os.getenv('SNAPSHOT_ON_DETECTION'), True)
            self.MAX_SNAPSHOTS = int(os.getenv('MAX_SNAPSHOTS', '500'))
            self.LOG_LEVEL = os.getenv('LOG_LEVEL', 'INFO').strip().upper() or 'INFO'
            self.CAMERA_SYNC_INTERVAL = int(os.getenv('CAMERA_SYNC_INTERVAL', '15'))
            self.CAMERAS = load_camera_configs(os.getenv('CAMERAS', ''))

            self._validate()
        except SystemExit:
            raise
        except Exception as exc:
            _fail(f"Unexpected configuration error: {exc}")

    def _validate(self) -> None:
        try:
            if not self.LARAVEL_INTERNAL_TOKEN:
                _fail("LARAVEL_INTERNAL_TOKEN must not be empty.")
            if not os.path.exists(self.MODEL_PATH):
                _fail(f"MODEL_PATH does not exist: {self.MODEL_PATH}")
            if self.DETECTION_INTERVAL < 1:
                _fail("DETECTION_INTERVAL must be at least 1.")
            if not 0 < self.MIN_CONFIDENCE <= 1:
                _fail("MIN_CONFIDENCE must be between 0 and 1.")
            if self.MAX_SNAPSHOTS < 1:
                _fail("MAX_SNAPSHOTS must be at least 1.")
            if self.CAMERA_SYNC_INTERVAL < 5:
                _fail("CAMERA_SYNC_INTERVAL must be at least 5 seconds.")

            os.makedirs(self.FRAMES_DIR, exist_ok=True)
            os.makedirs(self.SNAPSHOTS_DIR, exist_ok=True)
        except SystemExit:
            raise
        except Exception as exc:
            _fail(f"Validation failed: {exc}")
