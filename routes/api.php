<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GameController;

Route::options('/jogos', function() {
    return response()->json([], 200);
});

Route::post('/jogos', [GameController::class, 'iniciarJogo']);
Route::post('/validar-tentativa', [GameController::class, 'validarTentativa']);