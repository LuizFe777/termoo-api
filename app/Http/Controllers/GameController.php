<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GameController extends Controller
{
    private const TAMANHO_PALAVRA = 5;
    private const TENTATIVAS_MAXIMAS = 6;

    public function iniciarJogo(): JsonResponse
    {
        $idJogo = (string) Str::uuid();

        $jogos = $this->carregarJogos();

        $jogos[$idJogo] = [
            'palavraSecreta' => $this->sortearPalavra(),
            'tentativasUsadas' => 0,
            'venceu' => false,
        ];

        $this->salvarJogos($jogos);

        return response()->json([
            'idJogo' => $idJogo,
            'tamanhoPalavra' => self::TAMANHO_PALAVRA,
            'tentativasMaximas' => self::TENTATIVAS_MAXIMAS,
        ]);
    }

    public function validarTentativa(Request $request, ?string $idJogo = null): JsonResponse
    {
        $idJogo = $idJogo ?? $request->input('idJogo');
        $palavra = $this->normalizarPalavra($request->input('palavra'));

        if (!$idJogo || !$palavra) {
            return $this->erro('Informe idJogo e palavra.', 400);
        }

        return $this->validarPalavra($idJogo, $palavra);
    }

    private function validarPalavra(string $idJogo, string $palavra): JsonResponse
    {
        $jogos = $this->carregarJogos();

        if (!isset($jogos[$idJogo])) {
            return $this->erro('Jogo não encontrado.', 404);
        }

        if (!$this->temCincoLetras($palavra)) {
            return $this->erro('A palavra deve ter exatamente 5 letras.', 400);
        }

        $jogo = $jogos[$idJogo];

        if ($jogo['venceu'] || $jogo['tentativasUsadas'] >= self::TENTATIVAS_MAXIMAS) {
            return $this->erro('Esta partida já foi encerrada.', 400);
        }

        $dicionario = $this->carregarDicionario();
        $palavraValida = in_array($palavra, $dicionario, true);

        if (!$palavraValida) {
            return response()->json([
                'resultado' => [],
                'venceu' => false,
                'tentativasRestantes' => self::TENTATIVAS_MAXIMAS - $jogo['tentativasUsadas'],
                'palavraValida' => false,
            ]);
        }

        $jogo['tentativasUsadas']++;
        $jogo['venceu'] = $palavra === $jogo['palavraSecreta'];
        $jogos[$idJogo] = $jogo;

        $this->salvarJogos($jogos);

        return response()->json([
            'resultado' => $this->compararPalavras($palavra, $jogo['palavraSecreta']),
            'venceu' => $jogo['venceu'],
            'tentativasRestantes' => self::TENTATIVAS_MAXIMAS - $jogo['tentativasUsadas'],
            'palavraValida' => true,
        ]);
    }

    private function compararPalavras(string $tentativa, string $palavraSecreta): array
    {
        $letrasTentativa = mb_str_split($tentativa);
        $letrasSecretas = mb_str_split($palavraSecreta);
        $resultado = [];
        $letrasRestantes = [];

        for ($i = 0; $i < self::TAMANHO_PALAVRA; $i++) {
            $resultado[$i] = [
                'letra' => $letrasTentativa[$i],
                'status' => 'ausente',
            ];

            if ($letrasTentativa[$i] === $letrasSecretas[$i]) {
                $resultado[$i]['status'] = 'correta';
            } else {
                $letra = $letrasSecretas[$i];
                $letrasRestantes[$letra] = ($letrasRestantes[$letra] ?? 0) + 1;
            }
        }

        for ($i = 0; $i < self::TAMANHO_PALAVRA; $i++) {
            if ($resultado[$i]['status'] === 'correta') {
                continue;
            }

            $letra = $letrasTentativa[$i];

            if (($letrasRestantes[$letra] ?? 0) > 0) {
                $resultado[$i]['status'] = 'presente';
                $letrasRestantes[$letra]--;
            }
        }

        return $resultado;
    }

    private function carregarDicionario(): array
    {
        $palavras = file(app_path('words.txt'), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        $palavras = array_map(fn (string $palavra) => $this->normalizarPalavra($palavra), $palavras ?: []);
        $palavras = array_filter($palavras, fn (string $palavra) => $this->temCincoLetras($palavra));

        return array_values(array_unique($palavras));
    }

    private function sortearPalavra(): string
    {
        $dicionario = $this->carregarDicionario();

        return $dicionario[array_rand($dicionario)];
    }

    private function carregarJogos(): array
    {
        $arquivo = storage_path('app/jogos.json');

        if (!file_exists($arquivo)) {
            return [];
        }

        $jogos = json_decode(file_get_contents($arquivo), true);

        return is_array($jogos) ? $jogos : [];
    }

    private function salvarJogos(array $jogos): void
    {
        file_put_contents(
            storage_path('app/jogos.json'),
            json_encode($jogos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            LOCK_EX
        );
    }

    private function normalizarPalavra(mixed $palavra): string
    {
        return mb_strtolower(trim((string) $palavra), 'UTF-8');
    }

    private function temCincoLetras(string $palavra): bool
    {
        return mb_strlen($palavra, 'UTF-8') === self::TAMANHO_PALAVRA
            && preg_match('/^[a-záàâãéêíóôõúç]+$/iu', $palavra) === 1;
    }

    private function erro(string $mensagem, int $status): JsonResponse
    {
        return response()->json(['error' => $mensagem], $status);
    }
}