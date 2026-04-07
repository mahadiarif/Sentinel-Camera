<?php

use App\Http\Controllers\CameraConfigController;
use App\Http\Controllers\DetectionController;
use App\Http\Controllers\GateSecurityController;
use App\Http\Controllers\TrainingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Internal Python API (Token Based Auth)
|--------------------------------------------------------------------------
*/
Route::get('/internal/cameras', [CameraConfigController::class, 'internalIndex'])->name('api.internal.cameras');
Route::post('/gate/internal/alert', [GateSecurityController::class, 'internalAlert'])->name('api.gate.internal.alert');
Route::post('/internal/alert', [DetectionController::class, 'internalAlert']);

/*
|--------------------------------------------------------------------------
| Authenticated Dashboard API
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    
    // Gate Monitoring API
    Route::get('/gate/log', [GateSecurityController::class, 'log'])->name('api.gate.log');
    Route::get('/gate/report', [GateSecurityController::class, 'report'])->name('api.gate.report');
    Route::delete('/gate/log', [GateSecurityController::class, 'clearLog'])->name('api.gate.clear_log');
    Route::get('/gate/export', [GateSecurityController::class, 'exportCsv'])->name('api.gate.export');
    Route::get('/gate/stats', [GateSecurityController::class, 'stats'])->name('api.gate.stats');

    // Training API
    Route::post('/training/classes', [TrainingController::class, 'createClass'])->name('api.training.create_class');
    Route::delete('/training/classes/{id}', [TrainingController::class, 'deleteClass'])->name('api.training.delete_class');
    Route::post('/training/classes/{id}/upload', [TrainingController::class, 'uploadImages'])->name('api.training.upload_images');
    Route::post('/training/classes/{id}/train', [TrainingController::class, 'startTraining'])->name('api.training.start_training');
    Route::get('/training/classes/{id}/status', [TrainingController::class, 'trainingStatus'])->name('api.training.status');
    Route::get('/training/classes/{id}/images', [TrainingController::class, 'getImages'])->name('api.training.get_images');
    Route::post('/training/images/{id}/label', [TrainingController::class, 'saveLabel'])->name('api.training.save_label');

});

// Note: If Sanctum is not used, replace 'auth:sanctum' with 'auth'.
// In this project structure, Breeze session-based 'auth' is sufficient for browser-originated AJAX.
Route::middleware(['web', 'auth'])->group(function () {
    // Re-registering the above grouped routes for session-based access
    Route::get('/v1/gate/report', [GateSecurityController::class, 'report']);
    Route::get('/v1/gate/stats', [GateSecurityController::class, 'stats']);
    Route::get('/v1/gate/log', [GateSecurityController::class, 'log']);
    Route::delete('/v1/gate/log', [GateSecurityController::class, 'clearLog']);
    Route::get('/detections/report', [DetectionController::class, 'report']);
    Route::get('/detections/latest', [DetectionController::class, 'latest']);
    Route::get('/detections/export', [DetectionController::class, 'exportCsv']);
    Route::delete('/detections', [DetectionController::class, 'clearAll']);
    Route::post('/v1/training/classes', [TrainingController::class, 'createClass']);
    Route::delete('/v1/training/classes/{id}', [TrainingController::class, 'deleteClass']);
    Route::post('/v1/training/classes/{id}/upload', [TrainingController::class, 'uploadImages']);
    Route::post('/v1/training/classes/{id}/train', [TrainingController::class, 'startTraining']);
    Route::get('/v1/training/classes/{id}/status', [TrainingController::class, 'trainingStatus']);
    Route::get('/v1/training/classes/{id}/images', [TrainingController::class, 'getImages']);
    Route::post('/v1/training/images/{id}/label', [TrainingController::class, 'saveLabel']);
});
