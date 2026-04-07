import os
from datetime import datetime


RESET = '\033[0m'
COLORS = {
    'INFO': '\033[96m',
    'SUCCESS': '\033[92m',
    'WARNING': '\033[93m',
    'ERROR': '\033[91m',
    'DETECT': '\033[95m',
    'SYSTEM': '\033[96m',
}

_LEVEL_ORDER = {
    'INFO': 20,
    'SUCCESS': 20,
    'DETECT': 20,
    'WARNING': 30,
    'ERROR': 40,
}

_current_level = _LEVEL_ORDER.get(os.getenv('LOG_LEVEL', 'INFO').strip().upper(), 20)


def _should_log(level: str) -> bool:
    try:
        return _LEVEL_ORDER.get(level, 20) >= _current_level
    except Exception:
        return True


def _write(level: str, camera_name: str | None, message: str) -> None:
    try:
        if not _should_log(level):
            return
        timestamp = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
        camera_part = f'[{camera_name}] ' if camera_name else ''
        color = COLORS.get(level, '')
        print(f"{color}[{timestamp}] [{level}] {camera_part}{message}{RESET}", flush=True)
    except Exception:
        try:
            print(f"[LOGGER-FAILSAFE] [{level}] {camera_name or 'SYSTEM'} {message}", flush=True)
        except Exception:
            pass


def log_info(camera_name: str, msg: str) -> None:
    _write('INFO', camera_name, msg)


def log_success(camera_name: str, msg: str) -> None:
    _write('SUCCESS', camera_name, msg)


def log_warning(camera_name: str, msg: str) -> None:
    _write('WARNING', camera_name, msg)


def log_error(camera_name: str, msg: str) -> None:
    _write('ERROR', camera_name, msg)


def log_detect(camera_name: str, msg: str) -> None:
    _write('DETECT', camera_name, msg)


def log_system(msg: str) -> None:
    _write('SYSTEM', None, msg)
