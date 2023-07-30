<?php

namespace App\Actions;

use andreskrey\Readability\Readability;
use andreskrey\Readability\Configuration;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Lorisleiva\Actions\Concerns\AsAction;
use Illuminate\Support\Str;
use OpenAI\Laravel\Facades\OpenAI;
use \Probots\Pinecone\Client as Pinecone;

class EmbeddWebV3
{
    use AsAction;

    /**
     * @var string
     */
    public string $commandSignature = 'embed:web-chatbot {url}';

    /**
     * @var string
     */
    private string $content;

    /**
     * @var string
     */
    private string $contentRaw;

    /**
     * @var array
     */
    private array $links;

    /**
     *
     */
    public function handle(string $url): bool
    {
        $pinecone = new Pinecone(env('PINECONE_API_KEY'), env('PINECONE_ENV'));
        $cacheKey = 'scrape:' . md5($url);
        if (Cache::has($cacheKey)) {
            $scrapedData = Cache::get($cacheKey);
            $this->contentRaw = $scrapedData;
        } else {
            $this->scrapeUrl($url);
            $scrapedData = $this->contentRaw;
            Cache::put($cacheKey, $scrapedData, 60);
        }
        /** @var Readability $readability */
        $readability = new Readability(new Configuration());
        $readability->parse($this->contentRaw);
        $this->content = $readability->getContent();
        $content = Str::of(strip_tags($this->content))
            ->split(1000)
            ->toArray();
        if (count($content) > 1 && strlen($content[count($content) - 1]) < 500) {
            $content[count($content) - 2] .= $content[count($content) - 1];
            array_pop($content);
        }
        $embeddings = OpenAI::embeddings()->create([
            'model' => 'text-embedding-ada-002',
            'input' => $content,
        ])->embeddings;

        $pinecone->index('laravelgpt')->vectors()->upsert(
            collect($embeddings)->map(function ($embedding, $index) use ($content, $url) {
                return [
                    'id' => md5($url) . '-' . $index,
                    'values' => $embedding->embedding,
                    'metadata' => [
                        'text' => $content[$index],
                        'url' => $url,
                        'type' => 'web',
                    ]
                ];
            })->toArray(),
            'chatbot'
        );

        activity()
            ->event('embedd_web')
            ->withProperties([$url])
            ->log('EMBEDD_LOG');

        return true;
    }

    /**
     * Scrape Url
     *
     * @param string $url
     * @return bool
     */
    protected function scrapeUrl(string $url): bool
    {
        $key = config('services.browserless.key');
        $response = Http::post('https://chrome.browserless.io/content?stealth=true&token='.$key, [
            'url' => $url,
            'waitFor' => 1000,
        ]);
        $this->contentRaw = $response->body();
        return true;
    }

    /**
     * Pass argument to action
     *
     * @param Command $command
     * @return void
     */
    public function asCommand(Command $command): void
    {
        $command->comment($this->handle($command->argument('url')));
    }
}
