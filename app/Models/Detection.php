<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Detection extends Model
{
    use HasFactory;

    protected $fillable = [
        'camera_id', 'camera_name', 'camera_location',
        'person_count', 'carried_objects', 'all_objects',
        'object_count', 'max_confidence',
        'snapshot_path', 'detected_at',
    ];

    protected $casts = [
        'carried_objects' => 'array',
        'all_objects' => 'array',
        'max_confidence' => 'float',
        'detected_at' => 'datetime',
    ];

    public function camera(): BelongsTo
    {
        return $this->belongsTo(Camera::class)->withDefault();
    }
}
