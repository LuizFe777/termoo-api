<?php

use App\Http\Controllers\GameController;
use Illuminate\Support\Facades\Route;

Route::post('/jogos', [GameController::class, 'iniciarJogo']);
Route::post('/jogos/{idJogo}/tentativas', [GameController::class, 'validarTentativa']);

Route::post('/iniciar-jogo', [GameController::class, 'iniciarJogo']);
Route::post('/validar-tentativa', [GameController::class, 'validarTentativa']);