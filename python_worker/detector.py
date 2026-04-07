import threading
import os
import time
from pathlib import Path

import cv2
import torch
import numpy as np
from ultralytics import YOLO

from detection_result import DetectionResult
from logger import log_system


_original_torch_load = torch.load


def _patched_torch_load(*args, **kwargs):
    try:
        kwargs.setdefault('weights_only', False)
        return _original_torch_load(*args, **kwargs)
    except Exception:
        return _original_torch_load(*args, **kwargs)


torch.load = _patched_torch_load

_model_lock = threading.Lock()
_base_model = None
_custom_models = []
_loaded_custom_paths = set()
_base_model_path = None


def get_base_model(model_path='yolo26n.pt'):
    global _base_model, _base_model_path
    try:
        with _model_lock:
            if _base_model is None or _base_model_path != model_path:
                log_system(f"Loading base YOLO model: {model_path}")
                _base_model = YOLO(model_path)
                _base_model_path = model_path
        return _base_model
    except Exception as exc:
        log_system(f"Failed to load base model: {exc}")
        raise


def load_custom_models(custom_models_dir):
    global _custom_models
    models = []
    try:
        root = Path(custom_models_dir)
        if root.exists():
            for class_dir in root.iterdir():
                if not class_dir.is_dir():
                    continue
                pt = class_dir / 'best.pt'
                if not pt.exists():
                    continue
                resolved = str(pt.resolve())
                if resolved in _loaded_custom_paths:
                    continue
                try:
                    models.append({'model': YOLO(str(pt)), 'name': class_dir.name})
                    _loaded_custom_paths.add(resolved)
                    log_system(f"Loaded custom model: {class_dir.name}")
                except Exception as exc:
                    log_system(f"Failed to load {class_dir.name}: {exc}")
        _custom_models.extend(models)
    except Exception as exc:
        log_system(f"Custom model scan failed: {exc}")
    return _custom_models


def is_near_person(obj_bbox, person_bboxes):
    try:
        obj_cx = (obj_bbox[0] + obj_bbox[2]) / 2
        obj_cy = (obj_bbox[1] + obj_bbox[3]) / 2
        for pb in person_bboxes:
            px1, py1, px2, py2 = pb
            h = py2 - py1
            w = px2 - px1
            expand_h = h * 0.6
            expand_w = w * 0.4
            if (
                px1 - expand_w <= obj_cx <= px2 + expand_w
                and py1 - expand_h <= obj_cy <= py2 + expand_h
            ):
                return True
        return False
    except Exception:
        return False


def calculate_iou(box1, box2):
    try:
        x1 = max(box1[0], box2[0])
        y1 = max(box1[1], box2[1])
        x2 = min(box1[2], box2[2])
        y2 = min(box1[3], box2[3])
        
        intersection = max(0, x2 - x1) * max(0, y2 - y1)
        area1 = (box1[2] - box1[0]) * (box1[3] - box1[1])
        area2 = (box2[2] - box2[0]) * (box2[3] - box2[1])
        union = area1 + area2 - intersection
        
        return intersection / union if union > 0 else 0
    except Exception:
        return 0


def is_near_person_with_id(obj_bbox, persons):
    '''
    Returns (is_near: bool, owner_track_id: int|None)
    Finds which person owns this object based on proximity.
    '''
    obj_cx = (obj_bbox[0] + obj_bbox[2]) / 2
    obj_cy = (obj_bbox[1] + obj_bbox[3]) / 2
    
    best_owner = None
    best_dist  = float('inf')
    
    for p in persons:
        px1, py1, px2, py2 = p['bbox']
        h = py2 - py1
        w = px2 - px1
        expand_h = h * 0.6
        expand_w = w * 0.4
        
        if (px1 - expand_w <= obj_cx <= px2 + expand_w and
            py1 - expand_h <= obj_cy <= py2 + expand_h):
            
            # Find closest person if multiple persons nearby
            p_cx = (px1 + px2) / 2
            p_cy = (py1 + py2) / 2
            dist = ((obj_cx - p_cx)**2 + (obj_cy - p_cy)**2)**0.5
            
            if dist < best_dist:
                best_dist  = dist
                best_owner = p.get('track_id')
    
    if best_owner is not None or best_dist < float('inf'):
        return True, best_owner
    return False, None


def draw_label(frame, text, x, y, color):
    try:
        font = cv2.FONT_HERSHEY_SIMPLEX
        scale = 0.45
        thickness = 1
        (tw, th), _ = cv2.getTextSize(text, font, scale, thickness)
        top_y = max(y - th - 6, 0)
        cv2.rectangle(frame, (x, top_y), (x + tw + 4, max(y, 1)), color, -1)
        cv2.putText(frame, text, (x + 2, max(y - 3, th)), font, scale, (255, 255, 255), thickness, cv2.LINE_AA)
    except Exception:
        pass


def draw_dashed_rect(frame, pt1, pt2, color, thickness):
    try:
        x1, y1 = pt1
        x2, y2 = pt2
        dash = 8
        for x in range(x1, x2, dash * 2):
            cv2.line(frame, (x, y1), (min(x + dash, x2), y1), color, thickness)
            cv2.line(frame, (x, y2), (min(x + dash, x2), y2), color, thickness)
        for y in range(y1, y2, dash * 2):
            cv2.line(frame, (x1, y), (x1, min(y + dash, y2)), color, thickness)
            cv2.line(frame, (x2, y), (x2, min(y + dash, y2)), color, thickness)
    except Exception:
        pass


def detect(frame, min_confidence=0.45, custom_models_dir=None, model_path='yolo26n.pt', fps=0, tracker_name="bytetrack"):
    
    all_detections = []
    base = get_base_model(model_path)
    
    if custom_models_dir:
        load_custom_models(custom_models_dir)

    # Get tracker yaml path (Try bytetrack first as user requested the 'bytetrack feel')
    tracker_yaml = os.path.join(
        os.path.dirname(__file__), 'bytetrack_metronet.yaml'
    )
    if not os.path.exists(tracker_yaml):
        # Fallback to botsort if bytetrack yaml not found yet
        tracker_yaml = os.path.join(os.path.dirname(__file__), 'botsort_metronet.yaml')
    
    if not os.path.exists(tracker_yaml):
        tracker_yaml = 'bytetrack.yaml'  # fallback to built-in
    
    # ── Run YOLO12 with Tracker ──
    try:
        with _model_lock:
            results = base.track(
                frame,
                persist=True,
                tracker=tracker_yaml,
                verbose=False,
                conf=min_confidence,
            )[0]
    except Exception as e:
        # Fallback to detection only if tracking fails
        log_system(f"Tracker error, falling back to detect: {e}")
        with _model_lock:
            results = base(frame, verbose=False, conf=min_confidence)[0]
    
    # ── Parse results ──
    if results is not None and hasattr(results, 'boxes'):
        for box in results.boxes:
            try:
                conf = float(box.conf[0])
                if conf < min_confidence:
                    continue
                
                cls_id   = int(box.cls[0])
                cls_name = results.names[cls_id]
                x1, y1, x2, y2 = [int(v) for v in box.xyxy[0].tolist()]
                
                # Get track_id (None if tracking not available)
                track_id = None
                if hasattr(box, 'id') and box.id is not None:
                    try:
                        track_id = int(box.id[0])
                    except (TypeError, IndexError):
                        track_id = None
                
                all_detections.append({
                    'class':      cls_name,
                    'confidence': conf,
                    'bbox':       [x1, y1, x2, y2],
                    'track_id':   track_id,
                })
            except Exception:
                continue
    
    # ── Run custom models (detection only, no tracking) ──
    if _custom_models:
        for cm in _custom_models:
            try:
                with _model_lock:
                    r = cm['model'](frame, verbose=False,
                                    conf=min_confidence)[0]
                if r is not None and hasattr(r, 'boxes'):
                    for box in r.boxes:
                        try:
                            conf = float(box.conf[0])
                            if conf < min_confidence:
                                continue
                            cls_id   = int(box.cls[0])
                            cls_name = r.names[cls_id]
                            x1,y1,x2,y2 = [int(v) for v in box.xyxy[0].tolist()]
                            all_detections.append({
                                'class':      cls_name,
                                'confidence': conf,
                                'bbox':       [x1, y1, x2, y2],
                                'track_id':   None,
                            })
                        except Exception:
                            continue
            except Exception as e:
                log_system(f"Custom model error: {e}")
    
    # ── Deduplicate overlapping objects (NMS) ──
    all_detections.sort(key=lambda x: x['confidence'], reverse=True)
    kept_detections = []
    for det in all_detections:
        is_dup = False
        for kept in kept_detections:
            # Check overlap if same class (or across classes if they are very similar like TV/Monitor)
            if det['class'] == kept['class'] or (det['class'] in {'tv', 'monitor'} and kept['class'] in {'tv', 'monitor'}):
                if calculate_iou(det['bbox'], kept['bbox']) > 0.45:
                    is_dup = True
                    break
        if not is_dup:
            kept_detections.append(det)
    
    all_detections = kept_detections
    
    # ── Separate persons and objects ──
    persons  = [d for d in all_detections if d['class'] == 'person']
    objects  = [d for d in all_detections if d['class'] != 'person']
    
    # ── Find carried objects + link to person track_id ──
    carried = []
    not_carried = []
    
    for obj in objects:
        near, owner_id = is_near_person_with_id(
            obj['bbox'], persons
        )
        if near:
            obj['carried_by'] = owner_id  # track_id of owner person
            carried.append(obj)
        else:
            obj['carried_by'] = None
            not_carried.append(obj)
    
    # ── Build per-person summary ──
    person_summary = {}
    for p in persons:
        pid = p['track_id'] if p['track_id'] is not None else f"unk_{id(p)}"
        person_summary[pid] = {
            'track_id':       p['track_id'],
            'confidence':     p['confidence'],
            'bbox':           p['bbox'],
            'carried_objects': [],
        }
    
    for obj in carried:
        owner = obj.get('carried_by')
        if owner is not None and owner in person_summary:
            person_summary[owner]['carried_objects'].append(
                obj['class']
            )
        else:
            if person_summary:
                first_key = next(iter(person_summary))
                person_summary[first_key]['carried_objects'].append(
                    obj['class']
                )
    
    # ── Draw annotations ──
    annotated = frame.copy()
    
    # Persons → BRIGHT GREEN thick box (Classic Tracker Look)
    for p in persons:
        x1,y1,x2,y2 = p['bbox']
        cv2.rectangle(annotated, (x1,y1), (x2,y2), (0,255,100), 2)
        tid = f" ID:{p['track_id']}" if p['track_id'] is not None else ""
        label = f"person{tid} {p['confidence']:.2f}"
        draw_label(annotated, label, x1, y1, (0,255,100))
    
    # Carried objects → ORANGE solid box
    for o in carried:
        x1,y1,x2,y2 = o['bbox']
        cv2.rectangle(annotated, (x1,y1), (x2,y2), (0,165,255), 2)
        tid = f" ID:{o['carried_by']}" if o.get('carried_by') is not None else ""
        label = f"{o['class']}{tid} {o['confidence']:.2f}"
        draw_label(annotated, label, x1, y1, (0,165,255))
    
    # Other objects → CYAN dashed box
    for o in not_carried:
        x1,y1,x2,y2 = o['bbox']
        draw_dashed_rect(annotated, (x1,y1), (x2,y2), (255,255,0), 1)
        label = f"{o['class']} {o['confidence']:.2f}"
        draw_label(annotated, label, x1, y1, (255,255,0))

    # ── Return Values ──
    
    # ── Build return values ──
    carried_names  = [d['class'] for d in carried]
    all_names      = list(set(d['class'] for d in all_detections))
    max_conf       = max(
        (d['confidence'] for d in all_detections), default=0.0
    )
    
    return {
        'persons':          persons,
        'carried_objects':  carried_names,
        'all_objects':      all_names,
        'person_count':     len(persons),
        'object_count':     len(objects),
        'max_confidence':   max_conf,
        'annotated_frame':  annotated,
        'person_summary':   list(person_summary.values()),
    }
