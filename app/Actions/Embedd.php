<?php

namespace App\Actions;

use andreskrey\Readability\Readability;
use andreskrey\Readability\Configuration;
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
    public string $commandSignature = 'embed';

    /**
     *
     */
    public function handle(): bool
    {
        $pinecone = new Pinecone(env('PINECONE_API_KEY'), env('PINECONE_ENV'));
        /** @var Readability $readability */
        $readability = new Readability(new Configuration());
        $podcastHtml = file_get_contents("https://podscript.ai/podcasts/lex-fridman-podcast/360-tim-urban-tribalism-marxism-liberalism-social-justice-and-politics/");
        $readability->parse($podcastHtml);

        $content = Str::of(strip_tags($readability->getContent()))
            ->split(1000)
            ->toArray();

        $contentCount = count($content);

        if ($contentCount > 1 && strlen($content[$contentCount - 1]) < 500) {
            $content[$contentCount - 2] .= $content[$contentCount - 1];
            array_pop($content);
        }

        $embeddings = OpenAI::embeddings()->create([
            'model' => 'text-embedding-ada-002',
            'input' => $content,
        ])->embeddings;

        $pinecone->index('laravelgpt')->vectors()->delete([], 'podcast', true);

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

        $pinecone->index('laravelgpt')->vectors()->query($embeddings[0]->embedding, 'podcast', [], 4)->json();
        return true;
    }
}
