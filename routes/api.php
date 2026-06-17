<?php

use App\Http\Controllers\Api\PredictiveApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// API para verificar duplicados de repases
Route::middleware(['auth'])->get('/repases/verificar-duplicado', function (Request $request) {
    $repaseExistente = \App\Models\Repase::where('clinica_id', $request->clinica_id)
        ->where('fecha', $request->fecha)
        ->first();
    
    return response()->json([
        'existe' => $repaseExistente !== null,
        'repase_id' => $repaseExistente?->id,
        'repase_url' => $repaseExistente ? route('repases.show', $repaseExistente->id) : null,
        'repase_edit_url' => $repaseExistente ? route('repases.edit', $repaseExistente->id) : null,
    ]);
})->name('api.repases.verificar-duplicado');

// API Routes para Módulo de Análisis Predictivo
Route::prefix('predictivo')->middleware(['auth', 'admin', 'throttle:60,1'])->name('api.predictivo.')->group(function () {
    Route::get('/ingresos/{months}', [PredictiveApiController::class, 'getIncomeProjection'])->name('ingresos');
    Route::get('/gastos/{months}', [PredictiveApiController::class, 'getExpenseForecast'])->name('gastos');
    Route::get('/capacidad/actual', [PredictiveApiController::class, 'getCurrentCapacity'])->name('capacidad');
    Route::get('/tendencias/estacionales', [PredictiveApiController::class, 'getSeasonalTrends'])->name('tendencias');
    Route::post('/configuracion', [PredictiveApiController::class, 'updateConfiguration'])->name('configuracion');
});