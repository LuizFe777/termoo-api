<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class GameController extends Controller
{
    // 1. INICIAR JOGO
    public function iniciarJogo()   
    {
        $words = file(app_path('words.txt'), FILE_IGNORE_NEW_LINES);

        if (!$words || count($words) === 0) {
            return response()->json([
                'error' => 'Nenhuma palavra encontrada.'
            ], 404);
        }

        $word = strtoupper(trim($words[array_rand($words)]));
        $idJogo = uniqid();

        Cache::put("game:$idJogo", [
            'word' => $word,
            'attempts' => 6
        ], now()->addMinutes(30));

        return response()->json([
            'idJogo' => $idJogo,
            'tamanhoPalavra' => 5,
            'tentativasMaximas' => 6
        ]);
    }

    // 2. VALIDAR TENTATIVA
  public function validarTentativa(Request $request)
{
    $request->validate([
        'idJogo' => 'required|string',
        'palavra' => 'required|string|size:5'
    ]);

    $game = Cache::get("game:{$request->idJogo}");

    if (!$game) {
        return response()->json([
            'error' => 'Jogo não encontrado.'
        ], 404);
        
    }
        if ($game['attempts'] <= 0) {
    return response()->json([
        'error' => 'Tentativas esgotadas.'
    ], 400);
}

    $word = $game['word'];
    $guess = strtoupper($request->palavra);

    $result = [];

    for ($i = 0; $i < 5; $i++) {

        $letter = $guess[$i];

        if ($letter === $word[$i]) {
            $status = 'correta';
        } elseif (str_contains($word, $letter)) {
            $status = 'presente';
        } else {
            $status = 'ausente';
        }

        $result[] = [
            'letra' => strtolower($letter),
            'status' => $status
        ];
    }

    $game['attempts']--;

    Cache::put(
        "game:{$request->idJogo}",
        $game,
        now()->addMinutes(30)
    );

    return response()->json([
        'resultado' => $result,
        'venceu' => $guess === $word,
        'tentativasRestantes' => $game['attempts'],
        'palavraValida' => true
    ]);
}}