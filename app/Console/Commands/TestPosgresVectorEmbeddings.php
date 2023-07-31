<?php

namespace App\Console\Commands;

use App\Models\Embedding;
use Illuminate\Console\Command;
use OpenAI\Laravel\Facades\OpenAI;

class TestPosgresVectorEmbeddings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-pgvector';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $sayings = [
            'Felines say meow',
            'Canines say woof',
            'Birds say tweet',
            'Humans say hello',
        ];

        $result = OpenAI::embeddings()->create([
            'model' => 'text-embedding-ada-002',
            'input' => $sayings
        ]);

        foreach ($sayings as $key=>$saying) {
            Embedding::query()->create([
                'embedding' => $result->embeddings[$key]->embedding,
                'metadata' => [
                    'saying' => $saying,
                ]
            ]);
        }
    }
}
