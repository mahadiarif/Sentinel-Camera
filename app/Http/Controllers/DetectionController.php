<?php

namespace App\Http\Controllers;

use App\Events\ObjectDetectedEvent;
use App\Models\Camera;
use App\Models\Detection;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DetectionController extends Controller
{
    public function internalAlert(Request $request): JsonResponse
    {
        $token = $request->bearerToken();
        if (!$token || $token !== config('app.internal_api_token', env('INTERNAL_API_TOKEN'))) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $data = $request->validate([
            'camera_id' => 'nullable|string',
            'camera_name' => 'required|string',
            'camera_location' => 'nullable|string',
            'person_count' => 'required|integer|min:0',
            'carried_objects' => 'present|array',
            'all_objects' => 'nullable|array',
            'object_count' => 'required|integer|min:0',
            'max_confidence' => 'required|numeric',
            'snapshot_path' => 'nullable|string',
        ]);

        $camera = null;
        if (!empty($data['camera_id'])) {
            $camera = Camera::find($data['camera_id']);
        }
        if (!$camera && !empty($data['camera_name'])) {
            $camera = Camera::where('name', $data['camera_name'])->first();
        }

        $snapshotUrl = null;
        if (!empty($data['snapshot_path'])) {
            $filename = basename($data['snapshot_path']);
            $snapshotUrl = asset("gate_snapshots/{$filename}");
        }

        $detection = Detection::create([
            'camera_id' => $camera?->id,
            'camera_name' => $data['camera_name'],
            'camera_location' => $data['camera_location'] ?? '',
            'person_count' => $data['person_count'],
            'carried_objects' => $data['carried_objects'],
            'all_objects' => $data['all_objects'] ?? $data['carried_objects'],
            'object_count' => $data['object_count'],
            'max_confidence' => $data['max_confidence'],
            'snapshot_path' => $data['snapshot_path'] ?? null,
            'detected_at' => now(),
        ]);

        try {
            event(new ObjectDetectedEvent(
                cameraId: (string) ($camera?->id ?? $data['camera_id'] ?? '1'),
                cameraName: $detection->camera_name,
                cameraLocation: $detection->camera_location ?? '',
                personCount: $detection->person_count,
                carriedObjects: $detection->carried_objects ?? [],
                allObjects: $detection->all_objects ?? [],
                objectCount: $detection->object_count,
                maxConfidence: $detection->max_confidence,
                snapshotUrl: $snapshotUrl,
                detectedAt: $detection->detected_at->toDateTimeString(),
            ));
        } catch (\Throwable $e) {
            Log::warning('Broadcast failed: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'detection_id' => $detection->id,
            'message' => 'Detection recorded',
        ]);
    }

    public function report(): JsonResponse
    {
        $detections = Detection::whereDate('detected_at', today())
            ->orderByDesc('detected_at')
            ->get();

        $totalDetections = $detections->count();
        $totalPersons = $detections->sum('person_count');
        $totalObjects = 0;
        $latestDetection = $detections->first();

        $objectCounts = [];
        foreach ($detections as $detection) {
            $objects = $this->displayObjects($detection);
            $totalObjects += count($objects);
            foreach (array_count_values($objects) as $object => $count) {
                $objectCounts[$object] = ($objectCounts[$object] ?? 0) + $count;
            }
        }
        arsort($objectCounts);

        $breakdown = collect($objectCounts)
            ->map(fn ($count, $object) => compact('object', 'count'))
            ->values();

        $snapshots = $detections
            ->filter(fn (Detection $detection) => filled($detection->snapshot_path))
            ->take(8)
            ->map(fn (Detection $detection) => [
                'id' => $detection->id,
                'camera_name' => $detection->camera_name,
                'snapshot_url' => $this->snapshotUrl($detection->snapshot_path),
                'detected_at' => optional($detection->detected_at)->toDateTimeString(),
            ])
            ->values();

        $activity = $detections
            ->take(20)
            ->map(fn (Detection $detection) => $this->serializeDetection($detection))
            ->values();

        return response()->json([
            'total_detections' => $totalDetections,
            'total_persons' => $totalPersons,
            'total_objects' => $totalObjects,
            'last_detection' => $latestDetection?->detected_at
                ? Carbon::parse($latestDetection->detected_at)->format('H:i:s')
                : null,
            'breakdown' => $breakdown,
            'snapshots' => $snapshots,
            'activity' => $activity,
            'latest_event' => $latestDetection ? $this->serializeDetection($latestDetection) : null,
        ]);
    }

    public function latest(): JsonResponse
    {
        $detection = Detection::orderByDesc('detected_at')->first();

        if (!$detection) {
            return response()->json(null);
        }

        return response()->json($this->serializeDetection($detection));
    }

    public function exportCsv(): StreamedResponse
    {
        $detections = Detection::orderByDesc('detected_at')->get();

        return response()->streamDownload(function () use ($detections) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'ID',
                'Camera ID',
                'Camera Name',
                'Camera Location',
                'Person Count',
                'Carried Objects',
                'All Objects',
                'Object Count',
                'Max Confidence',
                'Snapshot Path',
                'Detected At',
            ]);

            foreach ($detections as $detection) {
                fputcsv($handle, [
                    $detection->id,
                    $detection->camera_id,
                    $detection->camera_name,
                    $detection->camera_location,
                    $detection->person_count,
                    implode(', ', $detection->carried_objects ?? []),
                    implode(', ', $detection->all_objects ?? []),
                    $detection->object_count,
                    $detection->max_confidence,
                    $detection->snapshot_path,
                    optional($detection->detected_at)->toDateTimeString(),
                ]);
            }

            fclose($handle);
        }, 'detections_' . now()->format('Ymd_His') . '.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function clearAll(): JsonResponse
    {
        Detection::query()->delete();

        return response()->json([
            'success' => true,
            'message' => 'All detections cleared',
        ]);
    }

    private function serializeDetection(Detection $detection): array
    {
        $displayObjects = $this->displayObjects($detection);

        return [
            'id' => $detection->id,
            'camera_id' => $detection->camera_id,
            'camera_name' => $detection->camera_name,
            'camera_location' => $detection->camera_location,
            'person_count' => $detection->person_count,
            'carried_objects' => $detection->carried_objects ?? [],
            'all_objects' => $detection->all_objects ?? [],
            'display_objects' => $displayObjects,
            'object_count' => count($displayObjects),
            'snapshot_url' => $this->snapshotUrl($detection->snapshot_path),
            'detected_at' => optional($detection->detected_at)->toDateTimeString(),
        ];
    }

    private function displayObjects(Detection $detection): array
    {
        $carriedObjects = array_values(array_filter($detection->carried_objects ?? []));
        if ($carriedObjects !== []) {
            return $carriedObjects;
        }

        return array_values(array_filter($detection->all_objects ?? []));
    }

    private function snapshotUrl(?string $snapshotPath): ?string
    {
        if (!$snapshotPath) {
            return null;
        }

        return asset('gate_snapshots/' . basename($snapshotPath));
    }
}
