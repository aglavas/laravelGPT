<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;


class ScrapeEmbeddUrl implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var string|mixed
     */
    public string $url;

    /**
     * @var int
     */
    public int $limit = 1;

    public int $current;

    /**
     * Create a new job instance.
     */
    public function __construct(array $params)
    {
        $this->limit = 2;
        $this->url = $params['url'];
        $this->current = $params['current'] ?? 1;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $response = Http::get($this->url);
        } catch (\Exception $e) {
            // Log the error message and return
            logger()->error('Failed to retrieve ' . $this->url . ' Error: ' .  $e->getMessage());
            return;
        }
        $newUrls = $this->detectNewUrl($response->body());
        $currentDepth = $this->current;
        foreach ($newUrls as $newUrl){
            if ($currentDepth < $this->limit) {
                $newDepth = $currentDepth + 1;
                dispatch(new ScrapeEmbeddUrl(['url' => $newUrl, 'depth' => $newDepth]));
            }
        }
    }

    /**
     * Return links from webpage
     *
     * @return array
     */
    public function detectNewUrl($content): array
    {
        $pattern = '~href="(.*?)"~';
        preg_match_all($pattern, $content, $matches);

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
