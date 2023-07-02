<?php

namespace App\Actions;

use Illuminate\Support\Facades\File;
use Lorisleiva\Actions\Concerns\AsAction;
use Illuminate\Support\Str;
use OpenAI\Laravel\Facades\OpenAI;
use \Probots\Pinecone\Client as Pinecone;

class Embedd
{
    use AsAction;

    /**
     * @var string
     */
    public $commandSignature = 'embed';

    /**
     *
     */
    public function handle()
    {
        $pinecone = new Pinecone(env('PINECONE_API_KEY'), env('PINECONE_ENV'));

         $podcastHtml = file_get_contents("https://podscript.ai/podcasts/lex-fridman-podcast/360-tim-urban-tribalism-marxism-liberalism-social-justice-and-politics/");
//        $content = Str::of(File::get(storage_path('app/podcast.html')))
        $content = Str::of($podcastHtml)
            ->after('<strong>')
            ->split('/<strong>/')
            ->map(function($bit) {
                return strip_tags($bit);
            })->toArray();

        array_pop($content);

        $embeddings = OpenAI::embeddings()->create([
            'model' => 'text-embedding-ada-002',
            'input' => $content,
        ])->embeddings;

        $pinecone->index('laravelgpt')->vectors()->upsert(
            collect($embeddings)->map(function ($embedding, $index) use ($content) {
                return [
                    'id' => (string) $index,
                    'values' => $embedding->embedding,
                    'metadata' => [
                        'text' => $content[$index]
                    ]
                ];
            })->toArray(),
            'podcast'
        );

        return true;
    }
}
