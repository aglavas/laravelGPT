<?php

namespace App\Actions;

use andreskrey\Readability\Readability;
use andreskrey\Readability\Configuration;
use Lorisleiva\Actions\Concerns\AsAction;
use Illuminate\Support\Facades\Http;

class GetWebpageContent
{
    use AsAction;

    /**
     * @var string
     */
    private string $content;

    /**
     * @param string $url
     * @return string
     * @throws \andreskrey\Readability\ParseException
     */
    public function handle(string $url): string
    {
        $key = config('services.browserless.key');

        $response = Http::post('https://chrome.browserless.io/content?stealth=true&token='.$key, [
            'url' => $url,
            'waitFor' => 500,
        ]);

        $readability = new Readability(new Configuration);
        $readability->parse($response->body());

        $this->content = trim(strip_tags($readability->getContent()));

        return $this->content;
    }
}
