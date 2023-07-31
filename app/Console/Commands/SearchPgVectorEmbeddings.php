<?php

namespace App\Console\Commands;

use App\Models\Embedding;
use Illuminate\Console\Command;
use OpenAI\Laravel\Facades\OpenAI;
use Pgvector\Laravel\Vector;

class SearchPgVectorEmbeddings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:search-pgvector';

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
        $result = OpenAI::embeddings()->create([
            'model' => 'text-embedding-ada-002',
            'input' => 'What do dogs say?',
        ]);

        $embedding = new Vector($result->embeddings[0]->embedding);

        $this->table(
            ['saying'],
            Embedding::query()
                ->orderByRaw('embedding <-> ?', [$embedding])
                ->take(2)
                ->pluck('metadata')
        );
    }
}
