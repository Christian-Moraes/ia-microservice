<?php

use App\Http\Controllers\IA\ResumoController;
use App\Http\Controllers\IA\ResumoExternoController;
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

// Rotas da API de IA (sem throttling para evitar problemas com Redis)
Route::prefix('ia')->middleware('api')->group(function () {
    Route::post('/resumo/historico', [ResumoController::class, 'gerarResumoComHistorico'])->withoutMiddleware('throttle:api');
});

// Rotas da API Externa (para clientes enviarem dados diretamente)
Route::prefix('externo')->middleware('api')->group(function () {
    Route::post('/resumo', [ResumoExternoController::class, 'gerarResumoExterno'])->withoutMiddleware('throttle:api');
    Route::post('/resumo/validar', [ResumoExternoController::class, 'validarDados'])->withoutMiddleware('throttle:api');
    Route::get('/resumo/documentacao', [ResumoExternoController::class, 'documentacao']);
});