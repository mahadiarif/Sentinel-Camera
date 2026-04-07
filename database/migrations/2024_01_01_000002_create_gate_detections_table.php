<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gate_detections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('camera_id')->nullable()->constrained('cameras')->onDelete('set null');
            $table->string('camera_name');
            $table->json('detected_objects');
            $table->integer('object_count');
            $table->boolean('person_detected')->default(false);
            $table->float('confidence', 5, 2);
            $table->enum('direction', ['entry', 'exit', 'unknown'])->default('unknown');
            $table->string('snapshot_path')->nullable();
            $table->timestamp('alerted_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gate_detections');
    }
};
