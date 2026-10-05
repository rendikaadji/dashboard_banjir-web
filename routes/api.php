<?php

use App\Http\Controllers\Api\StatusApiController;
use App\Http\Controllers\Api\TelemetryApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes (Prefix: /api/v1)
|--------------------------------------------------------------------------
*/

// Status Terkini & Streaming Realtime
Route::get('/status/current', [StatusApiController::class, 'current']);
Route::get('/public/status', [StatusApiController::class, 'publicStatus']);
Route::get('/stream', [StatusApiController::class, 'stream']); // Server-Sent Events (SSE)

// Ingest Telemetri & Pembacaan Sensor
Route::post('/telemetry', [TelemetryApiController::class, 'ingest']);
Route::get('/readings', [TelemetryApiController::class, 'readings']);
Route::get('/nodes', [TelemetryApiController::class, 'nodes']);

// Aksi Operator & Safety
Route::post('/alerts/manual', [TelemetryApiController::class, 'manualAlert']);
Route::post('/alerts/{id}/acknowledge', [TelemetryApiController::class, 'acknowledge']);
Route::post('/alerts/{id}/resolve', [TelemetryApiController::class, 'resolve']);
Route::post('/alerts/{id}/cancel', [TelemetryApiController::class, 'cancel']);
Route::post('/system/siren-test', [TelemetryApiController::class, 'sirenTest']);
