<?php

namespace App\Actions;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Lorisleiva\Actions\Concerns\AsAction;

class GenerateImage
{
    use AsAction;

    /**
     * @var string
     */
    public string $commandSignature = 'image:generate {model} {prompt}';

    /**
     *
     */
    public function handle(Command $command): bool
    {
        $model = $command->argument('model');
        $prompt = $command->argument('prompt');

        $response = Http::withBasicAuth(config('services.scenario.key'), config('services.scenario.secret'))
            ->asJson()
            ->acceptJson()
            ->post("https://api.cloud.scenario.gg/v1/models/$model/inferences", [
                'parameters' => [
                    'type' => 'txt2img',
                    'prompt' => $prompt,
                ]
            ]);

        return $response->json();
    }

}
