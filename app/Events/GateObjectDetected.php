<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GateObjectDetected implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $camera_name;
    public $detected_objects;
    public $object_count;
    public $person_detected;
    public $confidence;
    public $direction;
    public $snapshot_url;
    public $alerted_at;

    /**
     * Create a new event instance.
     */
    public function __construct(
        string $camera_name,
        array $detected_objects,
        int $object_count,
        bool $person_detected,
        float $confidence,
        string $direction,
        ?string $snapshot_url,
        string $alerted_at
    ) {
        $this->camera_name = $camera_name;
        $this->detected_objects = $detected_objects;
        $this->object_count = $object_count;
        $this->person_detected = $person_detected;
        $this->confidence = $confidence;
        $this->direction = $direction;
        $this->snapshot_url = $snapshot_url;
        $this->alerted_at = $alerted_at;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('gate-security'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'object.detected';
    }
}
