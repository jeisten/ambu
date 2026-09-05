<?php

use App\Http\Controllers\Api\AiClinicalAnalysisController;
use App\Http\Controllers\Api\AmbulanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DriverController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\RemissionController;
use App\Http\Controllers\Api\StatsController;
use App\Http\Controllers\Api\UserController;
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

    // --- Módulo Driver ---
    Route::get('/drivers/me/ambulance', [DriverController::class, 'myAmbulance'])->name('drivers.me.ambulance');

    // --- Módulo de Usuarios (Conductores, Admins, etc) ---
    Route::apiResource('users', UserController::class)->only(['store', 'index', 'show', 'update', 'destroy']);

    // --- Módulo de Flota de Ambulancias ---
    Route::get('/ambulances/available', [AmbulanceController::class, 'available'])->name('ambulances.available');
    Route::apiResource('ambulances', AmbulanceController::class);

    // --- Módulo de Pacientes ---
    Route::get('/patients/search', [PatientController::class, 'searchByIdentification'])->name('patients.search');
    Route::apiResource('patients', PatientController::class);

    // --- Módulo de Remisiones y Telemetría ---
    Route::get('/remissions/{remission}/locations', [RemissionController::class, 'locations'])->name('remissions.locations');
    Route::get('/remissions/{remission}/ambulance', [RemissionController::class, 'show'])->name('remissions.ambulance');
    Route::post('/remissions/{remission}/location', [RemissionController::class, 'recordLocation'])->name('remissions.record-location');
    Route::post('/remissions/{remission}/fuel-consumed', [RemissionController::class, 'recordFuelConsumed'])->name('remissions.fuel-consumed');
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

    // --- Admin Endpoints ---
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/stats/fleet', [StatsController::class, 'adminFleetStats'])->name('stats.fleet');
        Route::get('/alerts/documents', [StatsController::class, 'documentAlerts'])->name('alerts.documents');
    });
});
