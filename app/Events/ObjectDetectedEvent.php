<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ObjectDetectedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $cameraId,
        public string $cameraName,
        public string $cameraLocation,
        public int $personCount,
        public array $carriedObjects,
        public array $allObjects,
        public int $objectCount,
        public float $maxConfidence,
        public ?string $snapshotUrl,
        public string $detectedAt,
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel('detections')];
    }

    public function broadcastAs(): string
    {
        return 'object.detected';
    }

    public function broadcastWith(): array
    {
        return [
            'camera_id' => $this->cameraId,
            'camera_name' => $this->cameraName,
            'camera_location' => $this->cameraLocation,
            'person_count' => $this->personCount,
            'carried_objects' => $this->carriedObjects,
            'all_objects' => $this->allObjects,
            'object_count' => $this->objectCount,
            'max_confidence' => $this->maxConfidence,
            'snapshot_url' => $this->snapshotUrl,
            'detected_at' => $this->detectedAt,
        ];
    }
}
