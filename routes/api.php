<?php

use App\Http\Controllers\Api\AiClinicalAnalysisController;
use App\Http\Controllers\Api\AmbulanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\RemissionController;
use App\Http\Controllers\Api\StatsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group.
|
*/

// ==========================================
// 🔓 Rutas Públicas (Autenticación)
// ==========================================
Route::post('/login', [AuthController::class, 'login'])->name('login');

// ==========================================
// 🔐 Rutas Protegidas (Laravel Sanctum)
// ==========================================
Route::middleware('auth:sanctum')->group(function () {

    // --- Sesión & Perfil ---
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/me', [AuthController::class, 'me'])->name('me');

    // --- Módulo de Flota de Ambulancias ---
    Route::get('/ambulances/available', [AmbulanceController::class, 'available'])->name('ambulances.available');
    Route::apiResource('ambulances', AmbulanceController::class);

    // --- Módulo de Pacientes ---
    Route::apiResource('patients', PatientController::class);

    // --- Módulo de Remisiones y Telemetría ---
    Route::get('/remissions/{remission}/locations', [RemissionController::class, 'locations'])->name('remissions.locations');
    Route::post('/remissions/{remission}/location', [RemissionController::class, 'recordLocation'])->name('remissions.record-location');
    Route::patch('/remissions/{remission}/start-transfer', [RemissionController::class, 'startTransfer'])->name('remissions.start-transfer');
    Route::put('/remissions/{remission}/finish', [RemissionController::class, 'finish'])->name('remissions.finish');
    Route::post('/remissions/{remission}/cancel', [RemissionController::class, 'cancel'])->name('remissions.cancel');
    Route::apiResource('remissions', RemissionController::class)->only(['index', 'store', 'show']);

    // --- Módulo de Análisis Clínico Asistido por IA ---
    Route::post('/ai/analyze-observation', [AiClinicalAnalysisController::class, 'analyzeObservation'])->name('ai.analyze-observation');

    // --- Módulo de Estadísticas y Métricas ---
    Route::prefix('stats')->name('stats.')->group(function () {
        Route::get('/fleet', [StatsController::class, 'fleet'])->name('fleet');
        Route::get('/remissions', [StatsController::class, 'remissions'])->name('remissions');
        Route::get('/ambulances/{ambulance}', [StatsController::class, 'ambulance'])->name('ambulance');
    });
});
