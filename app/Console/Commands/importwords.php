<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Word;

class ImportWords extends Command
{
    protected $signature = 'words:import';

    protected $description = 'Importa palavras para o banco';

    public function handle()
    {
        $path = storage_path('app/words.txt');

        $content = file_get_contents($path);

        preg_match_all("/'([^']+)'/", $content, $matches);

        $words = $matches[1];

        foreach ($words as $word) {

            $word = strtoupper(trim($word));

            if (mb_strlen($word) !== 5) {
                continue;
            }

            Word::firstOrCreate([
                'word' => $word
            ]);
        }

        $this->info('Palavras importadas com sucesso.');
    }
}