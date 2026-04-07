<?php

namespace App\Jobs;

use App\Models\TrainingClass;
use App\Models\TrainingImage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Process\Process;
use Symfony\Component\Process\Process as SymfonyProcess;

class TrainObjectModel implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 7200; // 2 hours

    /**
     * Create a new job instance.
     */
    public function __construct(protected int $class_id)
    {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $class = TrainingClass::with('trainingImages')->findOrFail($this->class_id);
        
        try {
            Log::info("Starting training for class: {$class->name}");

            // 1. Prepare YOLO Dataset Directories
            $datasetPath = storage_path("app/yolo_datasets/{$this->class_id}");
            File::cleanDirectory($datasetPath);
            File::makeDirectory("{$datasetPath}/images/train", 0775, true);
            File::makeDirectory("{$datasetPath}/images/val", 0775, true);
            File::makeDirectory("{$datasetPath}/labels/train", 0775, true);
            File::makeDirectory("{$datasetPath}/labels/val", 0775, true);

            // 2. Split Data (80/20) and Create Labels
            $images = $class->trainingImages()->where('is_labeled', true)->get();
            $shuffled = $images->shuffle();
            $splitPoint = ceil($shuffled->count() * 0.8);
            
            $trainSet = $shuffled->take($splitPoint);
            $valSet = $shuffled->skip($splitPoint);

            $this->copyImagesAndLabels($trainSet, "{$datasetPath}/images/train", "{$datasetPath}/labels/train");
            $this->copyImagesAndLabels($valSet, "{$datasetPath}/images/val", "{$datasetPath}/labels/val");

            // 3. Create data.yaml
            $yamlContent = "path: {$datasetPath}\n"
                         . "train: images/train\n"
                         . "val: images/val\n"
                         . "nc: 1\n"
                         . "names: ['{$class->name}']\n";
            File::put("{$datasetPath}/dataset.yaml", $yamlContent);

            // 4. Run Python Training Process
            $pythonPath = base_path('python_worker/venv/Scripts/python.exe');
            if (!File::exists($pythonPath)) {
                $pythonPath = 'python'; // Fallback to system python
            }

            $scriptPath = base_path('python_worker/train_model.py');
            $outputPath = public_path("custom_models/{$this->class_id}");
            $progressPath = storage_path("app/training_progress/{$this->class_id}.json");

            $process = new SymfonyProcess([
                $pythonPath,
                $scriptPath,
                '--class_id', $this->class_id,
                '--class_name', $class->name,
                '--dataset_path', "{$datasetPath}/dataset.yaml",
                '--epochs', $class->training_epochs,
                '--output_path', $outputPath,
                '--progress_file', $progressPath,
            ]);

            $process->setTimeout($this->timeout);
            $process->start();

            // 5. Monitor progress while process is running
            while ($process->isRunning()) {
                $class->update(['status' => 'training']); // Keep status alive
                sleep(15);
            }

            if (!$process->isSuccessful()) {
                throw new \Exception($process->getErrorOutput());
            }

            // 6. Finalize TrainingClass
            // Read progress JSON for final results
            if (File::exists($progressPath)) {
                $finalStats = json_decode(File::get($progressPath), true);
                $accuracy = $finalStats['accuracy'] ?? 0.0;
                
                $class->update([
                    'status' => 'trained',
                    'training_accuracy' => $accuracy,
                    'model_path' => "custom_models/{$this->class_id}/best.pt",
                    'trained_at' => now(),
                ]);
            } else {
                $class->update(['status' => 'trained', 'trained_at' => now()]);
            }

            Log::info("Training completed for class: {$class->name}");

        } catch (\Exception $e) {
            Log::error("Training failed for class: {$class->name}. Error: " . $e->getMessage());
            $class->update(['status' => 'failed']);
            
            // Save fail log to progress file
            Storage::put("training_progress/{$this->class_id}.json", json_encode([
                'status' => 'failed',
                'error' => $e->getMessage()
            ]));
        }
    }

    private function copyImagesAndLabels($images, $targetImg, $targetLbl)
    {
        foreach ($images as $img) {
            $srcPath = public_path($img->file_path);
            $ext = File::extension($srcPath);
            $newImgName = "{$img->id}.{$ext}";
            $newLblName = "{$img->id}.txt";

            if (File::exists($srcPath)) {
                File::copy($srcPath, "{$targetImg}/{$newImgName}");

                // YOLO Format: "0 x_center y_center width height"
                $bbox = $img->label_data['bbox']; // [x,y,w,h] already normalized 0-1
                $labelStr = "0 {$bbox[0]} {$bbox[1]} {$bbox[2]} {$bbox[3]}";
                File::put("{$targetLbl}/{$newLblName}", $labelStr);
            }
        }
    }
}
