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
     * @var array|string[]
     */
    public array $stabilityModels = [
        'stable-diffusion' => 'stable-diffusion-v1-5',
        'stable-diffusion-2' => 'stable-diffusion-512-v2-1',
        'stable-diffusion-xl' => 'stable-diffusion-xl-beta-v2-2-2',
    ];

    /**
     *
     */
    public function handle(Command $command): array
    {
        $model = $command->argument('model');
        $prompt = $command->argument('prompt');

        $modelCode = $this->stabilityModels[$model] ?? $model;

//        $response = Http::withBasicAuth(config('services.scenario.key'), config('services.scenario.secret'))
//            ->asJson()
//            ->acceptJson()
//            ->post("https://api.cloud.scenario.gg/v1/models/$model/inferences", [
//                'parameters' => [
//                    'type' => 'txt2img',
//                    'prompt' => $prompt,
//                ]
//            ]);

        $apiKey = config('services.stability_ai.key');
        $body = [
            'width' => 512,
            'height' => 512,
            'steps' => 50,
            'seed' => 0,
            'cfg_scale' => 7,
            'samples' => 1,
            'text_prompts' => [
                [
                    'text' => $prompt,
                    'weight' => 1
                ]
            ]
        ];

        $response = Http::asJson()
            ->acceptJson()
            ->withHeader('Authorization', "Bearer $apiKey")
            ->post("https://api.stability.ai/v1/generation/$modelCode/text-to-image", $body);

        dump($response->json());

        return $response->json();
    }
}
