<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GateDetection extends Model
{
    use HasFactory;

    protected $fillable = [
        'camera_id',
        'camera_name',
        'detected_objects',
        'object_count',
        'person_detected',
        'confidence',
        'direction',
        'snapshot_path',
        'alerted_at',
    ];

    protected $casts = [
        'detected_objects' => 'array',
        'person_detected' => 'boolean',
        'confidence' => 'float',
        'alerted_at' => 'datetime',
    ];

    public function camera(): BelongsTo
    {
        return $this->belongsTo(Camera::class);
    }
}
