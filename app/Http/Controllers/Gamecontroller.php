<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GameController extends Controller
{
    private string $secret = 'chave-secreta-termo';

    private function encrypt(string $word): string
    {
        return base64_encode(openssl_encrypt($word, 'AES-128-ECB', $this->secret, OPENSSL_RAW_DATA));
    }

    private function decrypt(string $token): string|false
    {
        $decoded = base64_decode($token, true);
        if ($decoded === false) return false;
        return openssl_decrypt($decoded, 'AES-128-ECB', $this->secret, OPENSSL_RAW_DATA);
    }

    public function iniciarJogo()
    {
        $words = file(app_path('words.txt'), FILE_IGNORE_NEW_LINES);

        if (!$words || count($words) === 0) {
            return response()->json(['error' => 'Nenhuma palavra encontrada.'], 404);
        }

        $word = strtoupper(trim($words[array_rand($words)]));
        $idJogo = $this->encrypt($word);

        return response()->json([
            'idJogo' => $idJogo,
            'tamanhoPalavra' => 5,
            'tentativasMaximas' => 6
        ]);
    }

    public function validarTentativa(Request $request)
    {
        $request->validate([
            'idJogo' => 'required|string',
            'palavra' => 'required|string|size:5'
        ]);

        $word = $this->decrypt($request->idJogo);

        if (!$word) {
            return response()->json(['error' => 'Jogo não encontrado.'], 404);
        }

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

            $result[] = ['letra' => strtolower($letter), 'status' => $status];
        }

        return response()->json([
            'resultado' => $result,
            'venceu' => $guess === $word,
            'tentativasRestantes' => 5,
            'palavraValida' => true
        ]);
    }
}