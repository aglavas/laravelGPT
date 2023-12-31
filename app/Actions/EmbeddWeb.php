<?php

namespace App\Actions;

use andreskrey\Readability\Readability;
use andreskrey\Readability\Configuration;
use App\Jobs\ScrapeEmbeddUrl;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Lorisleiva\Actions\Concerns\AsAction;
use Illuminate\Support\Str;
use OpenAI\Laravel\Facades\OpenAI;
use \Probots\Pinecone\Client as Pinecone;

class EmbeddWeb
{
    use AsAction;

    /**
     * @var string
     */
    public string $commandSignature = 'embed:web {argument}';

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
     * @var bool
     */
    public bool $externalOnly = true;

    /**
     *
     */
    public function handle(Command $command): bool
    {
        $url = $command->argument('argument');
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
        $this->links = $this->getAllLinks();

        //All urls
        if (!$this->externalOnly) {
            $this->content = $this->contentRaw;
            $this->links = $this->getAllLinks();
            $this->content = $readability->getContent();
        }

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
        $pinecone->index('laravelgpt')->vectors()->delete([], 'web', true);
        $pinecone->index('laravelgpt')->vectors()->upsert(
            collect($embeddings)->map(function ($embedding, $index) use ($content, $url) {
                return [
                    'id' => md5($url) . '-' . $index,
                    'values' => $embedding->embedding,
                    'metadata' => [
                        'text' => $content[$index],
                        'type' => 'web-scrapping',
                        'category' => 'podcast',
                        'url' => md5($url)
                    ]
                ];
            })->toArray(),
            'web'
        );

//        $results = $pinecone->index('laravelgpt')->vectors()->query($embeddings[0]->embedding, 'web', [
//            'type' => [
//                '$eq' => 'web-scrapping'
//            ]
////            'category' => [
////                '$in' => ['product']
////            ]
//        ], 4)->json();

        foreach ($this->links as $link) {
            ScrapeEmbeddUrl::dispatch(['url' => $link, 'depth' => 1]);
        }

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
     * @return void
     */
    protected function scrapeUrl(string $url)
    {
        $key = config('services.browserless.key');
        $response = Http::post('https://chrome.browserless.io/content?stealth=true&token='.$key, [
            'url' => $url,
            'waitFor' => 1000,
        ]);
        $this->contentRaw = $response->body();
    }

    /**
     * Return links from webpage
     *
     * @return array
     */
    public function getAllLinks(): array
    {
        $pattern = '~href="(.*?)"~';
        preg_match_all($pattern, $this->content, $matches);

        return collect($matches[0] ?? [])
            ->map(function ($link) {
                return Str::after($link, 'href="');
            })->map(function ($link) {
                // Clean up quotes
                return trim(Str::before($link, '"'),'\'');
            })
            ->map(function ($link) {
                // Remove hash value so that we get the base url, in case there are multiple instances
                // Up to you if you need this.
                return Str::before($link, '#');
            })->reject(function ($link) {
                return Str::contains(
                    $link,
                    // media + docs
                    ['.ico', '.jpg', '.jpeg', '.png', '.bmp', '.gif', '.svg', '.pdf', '.doc', '.docx', '.xls', '.xlsx', '.ppt', '.pptx', '.mp3', '.mp4', '.avi', '.mov', '.wmv', '.flv', '.mkv', '.mpg', '.mpeg', '.m4v', '.webm', '.ogg', '.ogv', '.wav', '.aac', '.m4a', '.wma', '.flac', '.3gp']
                );
            })->reject(function ($link) {
                return Str::contains(
                    $link,
                    // other
                    ['.gz', '.bz2', '.zip', '.7z', '.tar', '.rar', '.js', '.css', '.json', '.xml']
                );
            })->reject(function($link) {
                return Str::contains(
                    $link,
                    // other
                    ['wp-json','xmlrpc','feed','wp-includes']
                );
            })->unique()->values()->all();
    }
}
