<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Camera extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'vendor',
        'source',
        'settings',
        'location',
        'status',
        'last_seen_at',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
        'settings' => 'array',
    ];

    public function gateDetections(): HasMany
    {
        return $this->hasMany(GateDetection::class);
    }
}
