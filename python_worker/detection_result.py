from dataclasses import dataclass
from typing import Any


@dataclass
class DetectionResult:
    persons: list[dict[str, Any]]
    carried_objects: list[str]
    all_objects: list[str]
    person_count: int
    object_count: int
    max_confidence: float
    annotated_frame: Any

    def to_payload(self, camera_id: str, camera_name: str, camera_location: str, snapshot_path: str | None) -> dict:
        return {
            'camera_id': str(camera_id),
            'camera_name': camera_name,
            'camera_location': camera_location,
            'person_count': self.person_count,
            'carried_objects': self.carried_objects,
            'all_objects': self.all_objects,
            'object_count': self.object_count,
            'max_confidence': round(self.max_confidence * 100, 1),
            'snapshot_path': snapshot_path,
        }
