<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GameController;

Route::options('/{any}', function() {
    return response()->json([], 200, [
        'Access-Control-Allow-Origin' => '*',
        'Access-Control-Allow-Methods' => 'POST, GET, OPTIONS',
        'Access-Control-Allow-Headers' => 'Content-Type, Authorization',
    ]);
})->where('any', '.*');

Route::post('/jogos', [GameController::class, 'iniciarJogo']);
Route::post('/validar-tentativa', [GameController::class, 'validarTentativa']);