<?php

namespace App\Http\Controllers;

use App\Jobs\TrainObjectModel;
use App\Models\TrainingClass;
use App\Models\TrainingImage;
include_once 'C:\Users\Mahadi Hassan Arif\Desktop\Camera Tracking\sentinel\app\Jobs\TrainObjectModel.php';
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TrainingController extends Controller
{
    public function index()
    {
        try {
            $classes = TrainingClass::withCount('trainingImages')->get();
            $stats = [
                'total' => TrainingClass::count(),
                'ready' => TrainingClass::where('labeled_count', '>=', 30)->where('status', '!=', 'trained')->count(),
                'training' => TrainingClass::where('status', 'training')->count(),
                'trained' => TrainingClass::where('status', 'trained')->count(),
            ];

            return view('training.index', compact('classes', 'stats'));
        } catch (\Exception $e) {
            Log::error('TrainingController@index error: ' . $e->getMessage());
            return back()->with('error', 'Error loading training dashboard.');
        }
    }

    public function labelView($classId)
    {
        try {
            $class = TrainingClass::with('trainingImages')->findOrFail($classId);
            return view('training.label', compact('class'));
        } catch (\Exception $e) {
            return back()->with('error', 'Class not found.');
        }
    }

    public function progressView($classId)
    {
        try {
            $class = TrainingClass::findOrFail($classId);
            return view('training.progress', compact('class'));
        } catch (\Exception $e) {
            return back()->with('error', 'Class not found.');
        }
    }

    public function createClass(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|alpha_dash|unique:training_classes,name',
                'display_name' => 'required|string',
                'description' => 'nullable|string',
            ]);

            $class = TrainingClass::create([
                'name' => $validated['name'],
                'display_name' => $validated['display_name'],
                'description' => $validated['description'],
                'status' => 'pending',
            ]);

            return response()->json(['success' => true, 'class' => $class]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function uploadImages(Request $request, $classId)
    {
        try {
            $request->validate([
                'images' => 'required|array|max:20',
                'images.*' => 'image|mimes:jpg,jpeg,png|max:5120',
            ]);

            $class = TrainingClass::findOrFail($classId);
            $uploadedCount = 0;
            $imageRecords = [];

            $basePath = public_path("training_data/{$classId}/images");
            if (!File::exists($basePath)) {
                File::makeDirectory($basePath, 0775, true);
            }

            foreach ($request->file('images') as $image) {
                $originalName = $image->getClientOriginalName();
                $filename = uniqid() . '.' . $image->getClientOriginalExtension();
                $image->move($basePath, $filename);

                $imageRecord = TrainingImage::create([
                    'class_id' => $classId,
                    'filename' => $filename,
                    'original_name' => $originalName,
                    'file_path' => "training_data/{$classId}/images/{$filename}",
                    'is_labeled' => false,
                ]);

                $imageRecords[] = $imageRecord;
                $uploadedCount++;
            }

            $class->increment('image_count', $uploadedCount);

            return response()->json([
                'success' => true,
                'uploaded_count' => $uploadedCount,
                'images' => $imageRecords
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function saveLabel(Request $request, $imageId)
    {
        try {
            $request->validate([
                'bbox' => 'required|array|size:4',
                'bbox.*' => 'numeric|min:0|max:1',
            ]);

            $image = TrainingImage::findOrFail($imageId);
            $image->update([
                'label_data' => ['bbox' => $request->bbox],
                'is_labeled' => true,
            ]);

            $class = $image->trainingClass;
            $labeledCount = $class->trainingImages()->where('is_labeled', true)->count();
            $class->update(['labeled_count' => $labeledCount]);

            if ($labeledCount >= 30 && $class->status === 'pending') {
                $class->update(['status' => 'ready']);
            } elseif ($labeledCount > 0 && $class->status === 'pending') {
                $class->update(['status' => 'labeling']);
            }

            return response()->json([
                'success' => true,
                'labeled_count' => $labeledCount,
                'status' => $class->status
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getImages($classId)
    {
        try {
            $images = TrainingImage::where('class_id', $classId)->get();
            $formatted = $images->map(function ($img) {
                return [
                    'id' => $img->id,
                    'url' => asset($img->file_path),
                    'is_labeled' => $img->is_labeled,
                    'label_data' => $img->label_data,
                ];
            });
            return response()->json($formatted);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function startTraining(Request $request, $classId)
    {
        try {
            $class = TrainingClass::findOrFail($classId);
            if ($class->labeled_count < 30) {
                return response()->json(['error' => 'Need at least 30 labeled images to start training.'], 400);
            }

            $class->update(['status' => 'training']);
            
            // Dispatch training job
            TrainObjectModel::dispatch($classId)->onQueue('training');

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function trainingStatus($classId)
    {
        try {
            $class = TrainingClass::findOrFail($classId);
            $progressPath = "training_progress/{$classId}.json";
            
            if (Storage::exists($progressPath)) {
                $progressData = json_decode(Storage::get($progressPath), true);
            } else {
                $progressData = [
                    'class_id' => $classId,
                    'status' => $class->status,
                    'progress' => 0,
                    'current_epoch' => 0,
                    'total_epochs' => $class->training_epochs,
                    'logs' => ['Training initiated...', 'Waiting for worker...'],
                ];
            }

            return response()->json([
                'status' => $class->status,
                'accuracy' => $class->training_accuracy,
                'progress' => $progressData
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function deleteClass($classId)
    {
        try {
            $class = TrainingClass::findOrFail($classId);
            
            // Delete files
            File::deleteDirectory(public_path("training_data/{$classId}"));
            File::deleteDirectory(public_path("custom_models/{$classId}"));
            Storage::delete("training_progress/{$classId}.json");

            $class->delete(); // Cascade deletes images in DB if setup in migration

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
