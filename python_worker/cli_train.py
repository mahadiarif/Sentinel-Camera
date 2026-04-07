import argparse
from pathlib import Path

import yaml
from ultralytics import YOLO


def build_parser():
    try:
        parser = argparse.ArgumentParser(description='MetroNet advanced YOLO26 training CLI')
        parser.add_argument('--name', required=True, help='Class/model name')
        parser.add_argument('--labels_dir', required=True, help='Path to labeled dataset root')
        parser.add_argument('--epochs', type=int, default=100, help='Training epochs')
        parser.add_argument('--output_dir', required=True, help='Output directory')
        return parser
    except Exception as exc:
        print(f"Failed to build parser: {exc}", flush=True)
        raise


def validate_labels_dir(labels_dir: Path) -> None:
    try:
        required_paths = [
            labels_dir / 'images' / 'train',
            labels_dir / 'images' / 'val',
            labels_dir / 'labels' / 'train',
            labels_dir / 'labels' / 'val',
        ]
        missing = [str(path) for path in required_paths if not path.exists()]
        if missing:
            raise FileNotFoundError(f"Dataset structure missing paths: {missing}")
    except Exception:
        raise


def generate_data_yaml(name: str, labels_dir: Path) -> Path:
    try:
        data_yaml = labels_dir / 'data.yaml'
        content = {
            'path': str(labels_dir.resolve()),
            'train': 'images/train',
            'val': 'images/val',
            'names': {0: name},
        }
        data_yaml.write_text(yaml.safe_dump(content, sort_keys=False), encoding='utf-8')
        return data_yaml
    except Exception:
        raise


def main():
    parser = build_parser()
    args = parser.parse_args()

    labels_dir = Path(args.labels_dir).resolve()
    output_dir = Path(args.output_dir).resolve()

    validate_labels_dir(labels_dir)
    data_yaml = generate_data_yaml(args.name, labels_dir)

    print(f"Starting training for class: {args.name}", flush=True)

    model = YOLO('yolo26n.pt')

    class ConsoleProgress:
        @staticmethod
        def on_train_epoch_end(trainer):
            try:
                epoch = int(getattr(trainer, 'epoch', 0)) + 1
                total = int(getattr(trainer.args, 'epochs', args.epochs))
                print(f"Epoch {epoch}/{total} completed", flush=True)
            except Exception as exc:
                print(f"Epoch progress error: {exc}", flush=True)

    model.add_callback('on_train_epoch_end', ConsoleProgress.on_train_epoch_end)
    results = model.train(
        data=str(data_yaml),
        epochs=args.epochs,
        imgsz=640,
        batch=8,
        project=str(output_dir),
        name=args.name,
        exist_ok=True,
        verbose=False,
    )

    best_model = output_dir / args.name / 'weights' / 'best.pt'
    accuracy = 0.0
    try:
        accuracy = float(results.results_dict.get('metrics/mAP50(B)', 0))
    except Exception:
        accuracy = 0.0

    print(f"Training complete. Model path: {best_model}", flush=True)
    print(f"Accuracy (mAP50): {accuracy:.4f}", flush=True)


if __name__ == '__main__':
    main()
