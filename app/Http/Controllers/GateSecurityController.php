<?php

namespace App\Http\Controllers;

use App\Events\GateObjectDetected;
use App\Models\Camera;
use App\Models\GateDetection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;

class GateSecurityController extends Controller
{
    public function index()
    {
        try {
            $today = today();
            $detections = \App\Models\Detection::whereDate('detected_at', $today)
                ->orderByDesc('detected_at')
                ->get();

            $totalObjects = $detections->sum('object_count');

            $stats = [
                'total_detections' => $detections->count(),
                'person_count' => $detections->sum('person_count'),
                'object_count' => $totalObjects,
                'snapshots_count' => $detections->whereNotNull('snapshot_path')->count(),
                'latest_time' => optional($detections->first()?->detected_at)->format('H:i:s'),
            ];

            $cameras = Camera::where('status', 'active')->get();
            $recent_snapshots = \App\Models\Detection::whereNotNull('snapshot_path')
                ->orderByDesc('detected_at')
                ->limit(8)
                ->get();

            return view('gate.dashboard', compact('stats', 'cameras', 'recent_snapshots'));
        } catch (\Exception $e) {
            Log::error('GateSecurityController@index error: ' . $e->getMessage());
            return back()->with('error', 'Error loading monitor dashboard.');
        }
    }

    public function frameView($cameraId)
    {
        $path = public_path("gate_frames/cam_{$cameraId}.jpg");

        if (file_exists($path)) {
            // Read into memory immediately so file handle is released fast (Windows lock fix)
            $data = file_get_contents($path);
            if ($data !== false) {
                return response($data, 200)
                    ->header('Content-Type', 'image/jpeg')
                    ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                    ->header('Pragma', 'no-cache')
                    ->header('Expires', '0');
            }
        }

        // Return a "No Signal" SVG when the frame file doesn't exist yet
        $svg  = '<svg xmlns="http://www.w3.org/2000/svg" width="800" height="480" viewBox="0 0 800 480">';
        $svg .= '<rect width="800" height="480" fill="#050a0e"/>';
        $svg .= '<text x="50%" y="44%" text-anchor="middle" fill="#00e5ff" font-family="monospace" font-size="22" font-weight="bold">NO SIGNAL</text>';
        $svg .= '<text x="50%" y="52%" text-anchor="middle" fill="#557" font-family="monospace" font-size="13">CAMERA ' . (int)$cameraId . ' — AWAITING STREAM...</text>';
        $svg .= '<text x="50%" y="60%" text-anchor="middle" fill="#334" font-family="monospace" font-size="11">AI Worker connecting to camera device</text>';
        $svg .= '</svg>';

        return response($svg, 200)
            ->header('Content-Type', 'image/svg+xml')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
    }

    public function log(Request $request)
    {
        try {
            $query = GateDetection::orderBy('created_at', 'desc');

            if ($request->camera_id) {
                $query->where('camera_id', $request->camera_id);
            }
            if ($request->date) {
                $query->whereDate('created_at', $request->date);
            }
            if ($request->object_type) {
                $query->whereJsonContains('detected_objects', $request->object_type);
            }

            return response()->json($query->paginate(20));
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function report()
    {
        try {
            $today = now()->startOfDay();
            // Order by latest first so detections->first() is actually the latest
            $detections = GateDetection::where('created_at', '>=', $today)
                ->orderByDesc('created_at')
                ->get();

            $total_objects = 0;
            $total_persons = $detections->where('person_detected', true)->count();
            $breakdown = [];

            foreach ($detections as $d) {
                $total_objects += $d->object_count;
                foreach ($d->detected_objects as $obj) {
                    $breakdown[$obj] = ($breakdown[$obj] ?? 0) + 1;
                }
            }

            arsort($breakdown);

            $formatted_breakdown = [];
            foreach ($breakdown as $object => $count) {
                $formatted_breakdown[] = ['object' => $object, 'count' => $count];
            }

            $snapshots = $detections->whereNotNull('snapshot_path')
                ->take(8)
                ->map(fn($d) => [
                    'snapshot_url' => asset('gate_snapshots/' . basename($d->snapshot_path)),
                    'detected_at' => $d->created_at->toDateTimeString()
                ])->values();

            $activity = $detections->take(20)
                ->map(fn($d) => [
                    'camera_name' => $d->camera_name,
                    'person_count' => $d->person_detected ? 1 : 0,
                    'display_objects' => $d->detected_objects,
                    'detected_at' => $d->created_at->toDateTimeString()
                ])->values();

            return response()->json([
                'total_detections' => $detections->count(),
                'total_persons'    => $total_persons,
                'total_objects'    => $total_objects,
                'last_detection'   => $detections->first()?->created_at?->format('H:i:s'),
                'breakdown'        => $formatted_breakdown,
                'snapshots'        => $snapshots,
                'activity'         => $activity
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function internalAlert(Request $request)
    {
        try {
            // Simple Token Auth
            $token = $request->bearerToken();
            if ($token !== config('services.internal_api_token', env('INTERNAL_API_TOKEN'))) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $validated = $request->validate([
                'camera_name' => 'required|string',
                'all_objects' => 'required|array',
                'person_count' => 'required|integer',
                'max_confidence' => 'required|numeric',
                'snapshot_path' => 'nullable|string',
            ]);

            $camera = Camera::where('name', $validated['camera_name'])->first();

            $detection = GateDetection::create([
                'camera_id' => $camera?->id,
                'camera_name' => $validated['camera_name'],
                'detected_objects' => $validated['all_objects'],
                'object_count' => count($validated['all_objects']),
                'person_detected' => $validated['person_count'] > 0,
                'confidence' => $validated['max_confidence'],
                'direction' => 'unknown',
                'snapshot_path' => $validated['snapshot_path'],
                'alerted_at' => now(),
            ]);

            if ($camera) {
                $camera->update(['last_seen_at' => now()]);
            }

            $snapshot_url = $detection->snapshot_path
                ? asset('gate_snapshots/' . basename($detection->snapshot_path))
                : null;

            try {
                broadcast(new GateObjectDetected(
                    $detection->camera_name,
                    $detection->detected_objects,
                    $detection->object_count,
                    $detection->person_detected,
                    $detection->confidence,
                    $detection->direction,
                    $snapshot_url,
                    $detection->alerted_at->toDateTimeString()
                ))->toOthers();
            } catch (\Throwable $be) {
                Log::warning('GateObjectDetected broadcast failed: ' . $be->getMessage());
            }

            return response()->json(['success' => true, 'id' => $detection->id]);
        } catch (\Exception $e) {
            Log::error('GateSecurityController@internalAlert error: ' . $e->getMessage(), [
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function clearLog()
    {
        try {
            GateDetection::truncate();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function exportCsv()
    {
        try {
            $detections = GateDetection::all();
            $csvHeader = ['ID', 'Camera', 'Objects', 'Count', 'Person Detected', 'Confidence', 'Direction', 'Time'];
            $handle = fopen('php://output', 'w');

            return response()->stream(function () use ($detections, $csvHeader, $handle) {
                fputcsv($handle, $csvHeader);
                foreach ($detections as $d) {
                    fputcsv($handle, [
                        $d->id,
                        $d->camera_name,
                        implode(', ', $d->detected_objects),
                        $d->object_count,
                        $d->person_detected ? 'Yes' : 'No',
                        $d->confidence . '%',
                        $d->direction,
                        $d->alerted_at,
                    ]);
                }
                fclose($handle);
            }, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="gate_detections_export_' . now()->format('Ymd_His') . '.csv"',
            ]);
        } catch (\Exception $e) {
            Log::error('GateSecurityController@exportCsv error: ' . $e->getMessage());
            return back()->with('error', 'Export failed.');
        }
    }

    public function stats()
    {
        return $this->report();
    }

}
