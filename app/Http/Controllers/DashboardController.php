<?php

namespace App\Http\Controllers;

use App\Models\Camera;
use App\Models\GateDetection;
use App\Models\TrainingClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function index()
    {
        try {
            $today = now()->toDateString();
            
            $stats = [
                'total_cameras' => Camera::count(),
                'detections_today' => GateDetection::whereDate('created_at', $today)->count(),
                'alerts_today' => GateDetection::whereDate('created_at', $today)
                    ->where('person_detected', true)
                    ->count(),
                'trained_models' => TrainingClass::where('status', 'trained')->count(),
            ];

            // Calculate Breakdown for the main dashboard from today's detections
            $detections = GateDetection::whereDate('created_at', $today)->get();
            $objectCounts = [];
            foreach ($detections as $d) {
                if (is_array($d->detected_objects)) {
                    foreach ($d->detected_objects as $obj) {
                        $objectCounts[$obj] = ($objectCounts[$obj] ?? 0) + 1;
                    }
                }
            }
            arsort($objectCounts);
            
            $breakdown = collect($objectCounts)->map(fn($count, $name) => [
                'name' => $name,
                'count' => $count
            ])->values()->take(5);

            $recent_detections = GateDetection::orderBy('created_at', 'desc')->limit(10)->get();

            return view('dashboard.index', compact('stats', 'recent_detections', 'breakdown'));
        } catch (\Exception $e) {
            Log::error('DashboardController@index error: ' . $e->getMessage());
            return back()->with('error', 'Error loading dashboard data.');
        }
    }
}
