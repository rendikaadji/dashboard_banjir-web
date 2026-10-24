<?php

use App\Http\Controllers\AlertController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NodeController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\RecipientController;
use App\Http\Controllers\SystemHealthController;
use App\Http\Controllers\ThresholdController;
use Illuminate\Support\Facades\Route;

// Landing page publik (tanpa login)
Route::get('/', [PublicController::class, 'landing'])->name('home');
Route::redirect('/public', '/')->name('public');

// Admin: dashboard dan semua halaman pengelolaan
Route::prefix('admin')->group(function () {
    Route::redirect('/', '/admin/dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/kiosk', [DashboardController::class, 'kiosk'])->name('kiosk');

    // Riwayat Alert
    Route::get('/alerts', [AlertController::class, 'index'])->name('alerts.index');

    // Manajemen Node & Kalibrasi
    Route::get('/nodes', [NodeController::class, 'index'])->name('nodes.index');
    Route::get('/nodes/{node_id}', [NodeController::class, 'show'])->name('nodes.show');
    Route::post('/nodes/{node_id}', [NodeController::class, 'update'])->name('nodes.update');

    // Ambang Batas & Dry-Run Simulator
    Route::get('/thresholds', [ThresholdController::class, 'index'])->name('thresholds.index');
    Route::post('/thresholds/{id}', [ThresholdController::class, 'update'])->name('thresholds.update');

    // Penerima WhatsApp
    Route::get('/recipients', [RecipientController::class, 'index'])->name('recipients.index');
    Route::post('/recipients', [RecipientController::class, 'store'])->name('recipients.store');
    Route::delete('/recipients/{id}', [RecipientController::class, 'destroy'])->name('recipients.destroy');

    // Analitik & KPI EPICS in IEEE
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
    Route::get('/export', [AnalyticsController::class, 'export'])->name('analytics.export');

    // Kesehatan Sistem & Audit Log
    Route::get('/system', [SystemHealthController::class, 'index'])->name('system.health');
});
