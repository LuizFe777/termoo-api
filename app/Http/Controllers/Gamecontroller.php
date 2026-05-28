<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GameController extends Controller
{
    private string $secret = 'chave-secreta-termo';

    private function cors(): array
    {
        return [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'POST, GET, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization',
        ];
    }

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
        $words = file(app_path('words.txt'), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if (!$words || count($words) === 0) {
            return response()->json(['error' => 'Nenhuma palavra encontrada.'], 404, $this->cors());
        }

        $word = strtoupper(trim($words[array_rand($words)]));
        $idJogo = $this->encrypt($word);

        return response()->json([
            'idJogo' => $idJogo,
            'tamanhoPalavra' => 5,
            'tentativasMaximas' => 6
        ], 200, $this->cors());
    }

    public function validarTentativa(Request $request, $idJogo = null)
    {
        $idJogo = $idJogo ?? $request->idJogo;

        if (!$idJogo) {
            return response()->json(['error' => 'idJogo obrigatório.'], 400, $this->cors());
        }

        $word = $this->decrypt($idJogo);

        if (!$word || strlen($word) !== 5) {
            return response()->json(['error' => 'Jogo não encontrado.'], 404, $this->cors());
        }

        $guess = strtoupper(trim($request->palavra ?? ''));

        if (strlen($guess) !== 5) {
            return response()->json(['error' => 'Palavra deve ter 5 letras.'], 400, $this->cors());
        }

        // Valida se a palavra existe no dicionário
        $words = file(app_path('words.txt'), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $wordList = array_map(fn($w) => strtoupper(trim($w)), $words);

        if (!in_array($guess, $wordList)) {
            return response()->json([
                'resultado' => [],
                'venceu' => false,
                'tentativasRestantes' => 5,
                'palavraValida' => false
            ], 200, $this->cors());
        }

        // Lógica de comparação com letras repetidas
        $result = array_fill(0, 5, null);
        $wordLetters = str_split($word);
        $guessLetters = str_split($guess);

        // Primeiro passa: marcar corretas
        for ($i = 0; $i < 5; $i++) {
            if ($guessLetters[$i] === $wordLetters[$i]) {
                $result[$i] = ['letra' => strtolower($guessLetters[$i]), 'status' => 'correta'];
                $wordLetters[$i] = null;
                $guessLetters[$i] = null;
            }
        }

        // Segunda passa: marcar presentes e ausentes
        for ($i = 0; $i < 5; $i++) {
            if ($guessLetters[$i] === null) continue;

            $pos = array_search($guessLetters[$i], $wordLetters);
            if ($pos !== false) {
                $result[$i] = ['letra' => strtolower($guessLetters[$i]), 'status' => 'presente'];
                $wordLetters[$pos] = null;
            } else {
                $result[$i] = ['letra' => strtolower($guessLetters[$i]), 'status' => 'ausente'];
            }
        }

        $venceu = strtoupper($request->palavra) === $word;

        return response()->json([
            'resultado' => $result,
            'venceu' => $venceu,
            'tentativasRestantes' => 5,
            'palavraValida' => true
        ], 200, $this->cors());
    }
}