import json
import sys
from dataclasses import dataclass


def fail_camera_config(message: str) -> None:
    print(f"[CAMERA CONFIG ERROR] {message}", file=sys.stderr)
    raise SystemExit(1)


@dataclass
class CameraConfig:
    id: str
    name: str
    type: str
    source: str
    vendor: str = 'generic'
    location: str = ''
    settings: dict | None = None

    def to_dict(self) -> dict:
        return {
            'id': self.id,
            'name': self.name,
            'type': self.type,
            'vendor': self.vendor,
            'source': self.source,
            'location': self.location,
            'settings': self.settings or {},
        }


def load_camera_configs(cameras_raw: str) -> list[CameraConfig]:
    try:
        if not cameras_raw.strip():
            return []

        cameras = json.loads(cameras_raw)
        if not isinstance(cameras, list):
            fail_camera_config("CAMERAS must be a valid JSON array.")
        if not cameras:
            return []

        validated = []
        camera_ids = set()

        for index, camera in enumerate(cameras, start=1):
            if not isinstance(camera, dict):
                fail_camera_config(f"Camera #{index} must be an object.")

            missing = [key for key in ('id', 'name', 'type', 'source') if key not in camera]
            if missing:
                fail_camera_config(f"Camera #{index} is missing required fields: {', '.join(missing)}")

            cam_type = str(camera['type']).strip().lower()
            if cam_type not in {'usb', 'rtsp'}:
                fail_camera_config(f"Camera #{index} type must be 'usb' or 'rtsp'.")

            camera_id = str(camera['id']).strip()
            if not camera_id:
                fail_camera_config(f"Camera #{index} id must not be empty.")
            if camera_id in camera_ids:
                fail_camera_config(f"Duplicate camera id found: {camera_id}")
            camera_ids.add(camera_id)

            camera_name = str(camera['name']).strip()
            if not camera_name:
                fail_camera_config(f"Camera #{index} name must not be empty.")

            source = str(camera['source']).strip()
            if not source:
                fail_camera_config(f"Camera #{index} source must not be empty.")
            if cam_type == 'usb' and not source.lstrip('-').isdigit():
                fail_camera_config(f"Camera #{index} source must be a numeric string for usb cameras.")

            settings = camera.get('settings', {})
            if settings is None or settings == '':
                settings = {}
            elif isinstance(settings, str):
                try:
                    decoded_settings = json.loads(settings)
                except json.JSONDecodeError:
                    fail_camera_config(f"Camera #{index} settings must be valid JSON when passed as a string.")
                settings = decoded_settings
            if not isinstance(settings, dict):
                fail_camera_config(f"Camera #{index} settings must be an object when provided.")

            validated.append(CameraConfig(
                id=camera_id,
                name=camera_name,
                type=cam_type,
                source=source,
                vendor=str(camera.get('vendor', 'generic')).strip().lower() or 'generic',
                location=str(camera.get('location', '')).strip(),
                settings=settings,
            ))

        return validated
    except json.JSONDecodeError as exc:
        fail_camera_config(f"CAMERAS contains invalid JSON: {exc}")
    except SystemExit:
        raise
    except Exception as exc:
        fail_camera_config(f"Failed to load CAMERAS: {exc}")
