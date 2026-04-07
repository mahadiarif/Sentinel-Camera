<?php

use App\Http\Controllers\CameraConfigController;
use App\Http\Controllers\CameraController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GateSecurityController;
use App\Http\Controllers\TrainingController;
use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

// Auth Routes (Custom Sentinel Logic)
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware(['auth'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Camera Config
    Route::get('/camera-config', [CameraConfigController::class, 'index'])->name('cameras.index');
    Route::post('/camera-config', [CameraConfigController::class, 'store'])->name('cameras.store');
    Route::put('/camera-config/{camera}', [CameraConfigController::class, 'update'])->name('cameras.update');
    Route::delete('/camera-config/{camera}', [CameraConfigController::class, 'destroy'])->name('cameras.destroy');

    // Gate Monitor
    Route::get('/gate', [GateSecurityController::class, 'index'])->name('gate.monitor');
    Route::get('/gate/frame/{camId}', [GateSecurityController::class, 'frameView'])->name('gate.frame');
    Route::get('/cameras/{cameraId}/frame', [CameraController::class, 'frameView'])->name('camera.frame');
    Route::get('/cameras/{cameraId}/stream', [CameraController::class, 'streamView'])->name('camera.stream');
    Route::get('/gate/report', [GateSecurityController::class, 'report'])->name('gate.report');
    Route::get('/gate/log', [GateSecurityController::class, 'log'])->name('gate.log');
    Route::delete('/gate/log', [GateSecurityController::class, 'clearLog'])->name('gate.clear_log');

    // Training UI
    Route::get('/training', [TrainingController::class, 'index'])->name('training.index');
    Route::get('/training/{id}/label', [TrainingController::class, 'labelView'])->name('training.label');
    Route::get('/training/{id}/progress', [TrainingController::class, 'progressView'])->name('training.progress');

    // Generic Profile placeholder
    Route::get('/profile', fn() => 'PROFILE VIEW - CONFIGURATION REQUIRED')->name('profile.edit');
});
