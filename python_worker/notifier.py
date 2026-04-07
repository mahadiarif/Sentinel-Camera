import json
import requests

from camera_config import load_camera_configs
from logger import log_error, log_success


class LaravelNotifier:
    def __init__(self, base_url, token):
        try:
            self.base_url = base_url.rstrip('/')
            self.token = token
            self.headers = {
                'Authorization': f'Bearer {token}',
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            }
        except Exception:
            self.base_url = base_url
            self.token = token
            self.headers = {}

    def send_detection(self, camera_id, camera_name, camera_location, result, snapshot_path):
        # Build payload with BoT-SORT person_summary support
        payload = {
            'camera_id':        str(camera_id),
            'camera_name':      camera_name,
            'camera_location':  camera_location,
            'person_count':     result['person_count'],
            'carried_objects':  result['carried_objects'],
            'all_objects':      result['all_objects'],
            'object_count':     result['object_count'],
            'max_confidence':   round(result['max_confidence'] * 100, 1),
            'snapshot_path':    snapshot_path,
            'person_summary':   result.get('person_summary', []),
        }

        try:
            response = requests.post(
                f"{self.base_url}/api/internal/alert",
                json=payload,
                headers=self.headers,
                timeout=5,
            )
            if response.status_code == 200:
                log_success(
                    camera_name,
                    f"Alert sent -> {result['person_count']} person(s), carried: {result['carried_objects']}",
                )
                return True

            log_error(camera_name, f"Alert failed: HTTP {response.status_code} - {response.text[:100]}")
            return False
        except requests.exceptions.ConnectionError:
            log_error(camera_name, "Laravel not reachable - connection refused")
            return False
        except requests.exceptions.Timeout:
            log_error(camera_name, "Laravel timeout (>5s)")
            return False
        except Exception as exc:
            log_error(camera_name, f"Notifier error: {exc}")
            return False

    def fetch_active_cameras(self):
        try:
            response = requests.get(
                f"{self.base_url}/api/internal/cameras",
                headers=self.headers,
                timeout=8,
            )
            if response.status_code != 200:
                return None

            payload = response.json()
            cameras = payload.get('cameras', [])
            return load_camera_configs(json.dumps(cameras))
        except SystemExit:
            return None
        except Exception:
            return None
