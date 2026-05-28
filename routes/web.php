<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GameController;

Route::get('/', function () {
        return view('welcome');
});

// inicia o jogo
Route::get('/start', [GameController::class, 'start']);

// tentativa de adivinhar
Route::post('/guess', [GameController::class, 'guess']);
