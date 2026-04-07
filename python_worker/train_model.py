import argparse
import json
import shutil
import time
from datetime import datetime
from pathlib import Path

from ultralytics import YOLO


def now_str() -> str:
    try:
        return datetime.now().strftime('%Y-%m-%d %H:%M:%S')
    except Exception:
        return '1970-01-01 00:00:00'


def write_progress(progress_file: Path, data: dict) -> None:
    try:
        progress_file.parent.mkdir(parents=True, exist_ok=True)
        progress_file.write_text(json.dumps(data, indent=2), encoding='utf-8')
    except Exception as exc:
        print(f"Failed to write progress file: {exc}", flush=True)


def build_parser():
    try:
        parser = argparse.ArgumentParser(description='Train custom YOLO26 model for MetroNet')
        parser.add_argument('--class_id', type=int, required=True)
        parser.add_argument('--class_name', type=str, required=True)
        parser.add_argument('--dataset_path', type=str, required=True)
        parser.add_argument('--epochs', type=int, default=50)
        parser.add_argument('--output_path', type=str, required=True)
        parser.add_argument('--progress_file', type=str, required=True)
        return parser
    except Exception as exc:
        print(f"Failed to build parser: {exc}", flush=True)
        raise


def main():
    parser = build_parser()
    args = parser.parse_args()

    dataset_path = Path(args.dataset_path).resolve()
    output_path = Path(args.output_path).resolve()
    progress_file = Path(args.progress_file).resolve()
    data_yaml = dataset_path / 'data.yaml'
    train_dir = output_path / 'train'
    start_time = time.time()

    if not data_yaml.exists():
        write_progress(progress_file, {
            'status': 'failed',
            'error': f'Missing data.yaml at {data_yaml}',
            'updated_at': now_str(),
        })
        raise SystemExit(1)

    model = YOLO('yolo26n.pt')

    class ProgressCallback:
        @staticmethod
        def on_train_epoch_end(trainer):
            try:
                current_epoch = int(getattr(trainer, 'epoch', 0)) + 1
                total_epochs = int(getattr(trainer.args, 'epochs', args.epochs))
                metrics = getattr(trainer, 'metrics', {}) or {}
                train_loss = None
                val_loss = None

                if hasattr(trainer, 'tloss') and trainer.tloss is not None:
                    try:
                        train_loss = round(float(sum(trainer.tloss.tolist())), 4)
                    except Exception:
                        train_loss = None

                try:
                    if isinstance(metrics, dict):
                        val_loss = metrics.get('val/box_loss') or metrics.get('val/loss')
                        if val_loss is not None:
                            val_loss = round(float(val_loss), 4)
                except Exception:
                    val_loss = None

                write_progress(progress_file, {
                    'status': 'training',
                    'current_epoch': current_epoch,
                    'total_epochs': total_epochs,
                    'train_loss': train_loss,
                    'val_loss': val_loss,
                    'accuracy': None,
                    'elapsed_seconds': int(time.time() - start_time),
                    'updated_at': now_str(),
                })
            except Exception as exc:
                print(f"Progress callback warning: {exc}", flush=True)

    try:
        write_progress(progress_file, {
            'status': 'training',
            'current_epoch': 0,
            'total_epochs': args.epochs,
            'train_loss': None,
            'val_loss': None,
            'accuracy': None,
            'elapsed_seconds': 0,
            'updated_at': now_str(),
        })

        model.add_callback('on_train_epoch_end', ProgressCallback.on_train_epoch_end)
        results = model.train(
            data=str(data_yaml),
            epochs=args.epochs,
            imgsz=640,
            batch=8,
            project=str(output_path),
            name='train',
            exist_ok=True,
            verbose=False,
        )

        best_src = train_dir / 'weights' / 'best.pt'
        best_dst = output_path / 'best.pt'
        output_path.mkdir(parents=True, exist_ok=True)
        if best_src.exists():
            shutil.copy(best_src, best_dst)
        else:
            raise FileNotFoundError(f"best.pt not found at {best_src}")

        accuracy = 0.0
        try:
            accuracy = float(results.results_dict.get('metrics/mAP50(B)', 0))
        except Exception:
            accuracy = 0.0

        write_progress(progress_file, {
            'status': 'completed',
            'current_epoch': args.epochs,
            'total_epochs': args.epochs,
            'accuracy': round(accuracy, 4),
            'elapsed_seconds': int(time.time() - start_time),
            'updated_at': now_str(),
        })
        print(f"Training complete. Model saved to: {best_dst}", flush=True)
    except Exception as exc:
        write_progress(progress_file, {
            'status': 'failed',
            'error': str(exc),
            'updated_at': now_str(),
        })
        raise


if __name__ == '__main__':
    main()
