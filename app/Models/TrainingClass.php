<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingClass extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'image_count',
        'labeled_count',
        'status',
        'model_path',
        'training_epochs',
        'training_accuracy',
        'trained_at',
    ];

    protected $casts = [
        'training_accuracy' => 'float',
        'trained_at' => 'datetime',
    ];

    public function trainingImages(): HasMany
    {
        return $this->hasMany(TrainingImage::class, 'class_id');
    }

    public function getIsReadyToTrainAttribute(): bool
    {
        return $this->labeled_count >= 30;
    }
}
