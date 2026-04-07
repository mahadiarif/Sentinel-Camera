import signal
import sys
import time

from camera_thread import CameraThread
from camera_config import CameraConfig
from config import Config
from detector import load_custom_models
from logger import log_system
from notifier import LaravelNotifier


def camera_signature(camera: CameraConfig) -> tuple[str, str, str, str, str, str, str]:
    return (
        str(camera.id),
        camera.name,
        camera.location,
        camera.type,
        getattr(camera, 'vendor', 'generic'),
        str(camera.source),
        str(getattr(camera, 'settings', {}) or {}),
    )


def build_thread(camera: CameraConfig, notifier: LaravelNotifier, config: Config) -> CameraThread:
    thread = CameraThread(camera, notifier, config)
    thread.name = f"Camera-{camera.id}"
    return thread


def fetch_desired_cameras(config: Config, notifier: LaravelNotifier, fallback_logged: list[bool]) -> list[CameraConfig]:
    remote_cameras = notifier.fetch_active_cameras()
    if remote_cameras is not None:
        fallback_logged[0] = False
        return remote_cameras

    if not fallback_logged[0]:
        log_system("Camera sync API unavailable - using local CAMERAS fallback.")
        fallback_logged[0] = True

    return config.CAMERAS


def start_camera_thread(
    camera: CameraConfig,
    notifier: LaravelNotifier,
    config: Config,
    threads: dict[str, CameraThread],
    signatures: dict[str, tuple[str, str, str, str, str]],
) -> None:
    thread = build_thread(camera, notifier, config)
    thread.start()
    camera_id = str(camera.id)
    threads[camera_id] = thread
    signatures[camera_id] = camera_signature(camera)
    log_system(f"Camera active: {camera.name} [{camera.type}] -> {camera.source}")


def stop_camera_thread(camera_id: str, threads: dict[str, CameraThread]) -> CameraThread | None:
    thread = threads.pop(camera_id, None)
    if thread:
        try:
            thread.stop()
        except Exception:
            pass
    return thread


def main():
    log_system("=" * 55)
    log_system("  METRONET - Multi-Camera Detection Worker v1.0")
    log_system("=" * 55)

    try:
        config = Config()
    except SystemExit:
        sys.exit(1)
    except Exception as exc:
        log_system(f"Fatal config error: {exc}")
        sys.exit(1)

    log_system(f"Laravel URL: {config.LARAVEL_URL}")
    log_system(f"Model path: {config.MODEL_PATH}")
    log_system(f"Frames dir: {config.FRAMES_DIR}")
    log_system(f"Detection interval: every {config.DETECTION_INTERVAL} frames")
    log_system(f"Min confidence: {config.MIN_CONFIDENCE}")
    log_system(f"Camera sync interval: {config.CAMERA_SYNC_INTERVAL}s")

    try:
        load_custom_models(config.CUSTOM_MODELS_DIR)
    except Exception as exc:
        log_system(f"Custom model preload warning: {exc}")

    notifier = LaravelNotifier(
        base_url=config.LARAVEL_URL,
        token=config.LARAVEL_INTERNAL_TOKEN,
    )

    threads: dict[str, CameraThread] = {}
    signatures: dict[str, tuple[str, str, str, str, str, str, str]] = {}
    fallback_logged = [False]
    desired_cameras = fetch_desired_cameras(config, notifier, fallback_logged)

    log_system(f"Cameras configured: {len(desired_cameras)}")
    for cam_config in desired_cameras:
        try:
            start_camera_thread(cam_config, notifier, config, threads, signatures)
            time.sleep(0.3)
        except Exception as exc:
            log_system(f"Failed to start {cam_config.name}: {exc}")

    def shutdown(sig, frame):
        try:
            log_system("Shutting down all cameras...")
            for camera_id in list(threads.keys()):
                stop_camera_thread(camera_id, threads)
            sys.exit(0)
        except Exception:
            sys.exit(1)

    signal.signal(signal.SIGINT, shutdown)
    signal.signal(signal.SIGTERM, shutdown)

    log_system(f"All {len(threads)} camera thread(s) running.")
    log_system("Press Ctrl+C to stop.")

    while True:
        try:
            time.sleep(config.CAMERA_SYNC_INTERVAL)
            desired_cameras = fetch_desired_cameras(config, notifier, fallback_logged)
            desired_map = {str(camera.id): camera for camera in desired_cameras}

            for camera_id in list(threads.keys()):
                if camera_id not in desired_map:
                    thread = stop_camera_thread(camera_id, threads)
                    signatures.pop(camera_id, None)
                    if thread:
                        log_system(f"Camera removed from active set: {thread.cam_name}")

            for camera_id, camera in desired_map.items():
                desired_signature = camera_signature(camera)
                existing_thread = threads.get(camera_id)

                if existing_thread is None:
                    start_camera_thread(camera, notifier, config, threads, signatures)
                    time.sleep(0.3)
                    continue

                if signatures.get(camera_id) != desired_signature:
                    log_system(f"Camera config changed - restarting: {existing_thread.cam_name}")
                    stop_camera_thread(camera_id, threads)
                    time.sleep(0.5)
                    start_camera_thread(camera, notifier, config, threads, signatures)
                    time.sleep(0.3)
                    continue

                if existing_thread.is_alive():
                    continue

                log_system(f"WARNING: {existing_thread.cam_name} thread died - restarting...")
                stop_camera_thread(camera_id, threads)
                time.sleep(0.5)
                start_camera_thread(camera, notifier, config, threads, signatures)
                time.sleep(0.3)

            alive = sum(1 for thread in threads.values() if thread.is_alive())
            log_system(f"Status: {alive}/{len(threads)} cameras active")
        except KeyboardInterrupt:
            shutdown(None, None)
        except Exception as exc:
            log_system(f"Worker monitor error: {exc}")
            time.sleep(5)


if __name__ == '__main__':
    main()
