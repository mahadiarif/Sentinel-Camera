<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('training_classes')->onDelete('cascade');
            $table->string('filename');
            $table->string('original_name');
            $table->string('file_path');
            $table->json('label_data')->nullable(); // Coordinates for YOLO
            $table->boolean('is_labeled')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_images');
    }
};
