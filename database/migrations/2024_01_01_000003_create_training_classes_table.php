<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_classes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('display_name');
            $table->text('description')->nullable();
            $table->integer('image_count')->default(0);
            $table->integer('labeled_count')->default(0);
            $table->enum('status', ['pending', 'labeling', 'ready', 'training', 'trained', 'failed'])->default('pending');
            $table->string('model_path')->nullable();
            $table->integer('training_epochs')->default(50);
            $table->float('training_accuracy')->nullable();
            $table->timestamp('trained_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_classes');
    }
};
