<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('detections')) {
            return;
        }

        Schema::create('detections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('camera_id')->nullable();
            $table->string('camera_name');
            $table->string('camera_location')->default('');
            $table->integer('person_count')->default(0);
            $table->json('carried_objects');
            $table->json('all_objects')->nullable();
            $table->integer('object_count')->default(0);
            $table->float('max_confidence')->default(0);
            $table->string('snapshot_path')->nullable();
            $table->timestamp('detected_at');
            $table->timestamps();

            if (Schema::hasTable('cameras')) {
                $table->foreign('camera_id')->references('id')->on('cameras')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detections');
    }
};
