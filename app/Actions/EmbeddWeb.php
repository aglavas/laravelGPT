<?php

namespace App\Actions;

use andreskrey\Readability\Readability;
use andreskrey\Readability\Configuration;
use Illuminate\Console\Command;
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
    public $commandSignature = 'embed:web {url}';

    /**
     * @var string
     */
    private $content;

//    public function asCommand(Command $command): void
//    {
//        $this->handle(
//            User::findOrFail($command->argument('user_id')),
//            $command->argument('role')
//        );
//
//        $command->info('Done!');
//    }

    /**
     *
     */
    public function handle(Command $command)
    {
        $url = $command->argument('url');

        $key = config('services.browserless.key');
        $response = Http::post('https://chrome.browserless.io/content?stealth=true&token='.$key, [
            'url' => $url,
            'waitFor' => 1000,
        ]);
        /** @var Readability $readability */
        $readability = new Readability(new Configuration());
        $readability->parse($response->body());
        $this->content = $readability->getContent();
//        dd($this->getAllLinks());
        $pinecone = new Pinecone(env('PINECONE_API_KEY'), env('PINECONE_ENV'));

        $content = Str::of(strip_tags($this->content))
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

        $pinecone->index('laravelgpt')->vectors()->delete([], 'web', true);

        $pinecone->index('laravelgpt')->vectors()->upsert(
            collect($embeddings)->map(function ($embedding, $index) use ($content, $url) {
                return [
                    'id' => md5($url) . '-' . $index,
                    'values' => $embedding->embedding,
                    'metadata' => [
                        'text' => $content[$index],
//                        'type' => 'web',
//                        'category' => 'product',
                        'url' => md5($url)
                    ]
                ];
            })->toArray(),
            'web'
        );

        $results = $pinecone->index('laravelgpt')->vectors()->query($embeddings[0]->embedding, 'web', [], 4)->json();


        return true;
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
