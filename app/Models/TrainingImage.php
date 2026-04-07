<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_id',
        'filename',
        'original_name',
        'file_path',
        'label_data',
        'is_labeled',
    ];

    protected $casts = [
        'label_data' => 'array',
        'is_labeled' => 'boolean',
    ];

    public function trainingClass(): BelongsTo
    {
        return $this->belongsTo(TrainingClass::class, 'class_id');
    }
}
