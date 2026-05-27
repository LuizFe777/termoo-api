<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GameController;

Route::post('/jogos', [GameController::class, 'iniciarJogo']);
Route::post('/validar-tentativa', [GameController::class, 'validarTentativa']);