<?php

namespace App\Actions;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Lorisleiva\Actions\Concerns\AsAction;

class CheckImage
{
    use AsAction;

    /**
     * @var string
     */
    public string $commandSignature = 'image:check {model} {inference_id}';

    /**
     *
     */
    public function handle(Command $command): bool
    {
        $model = $command->argument('model');
        $inferenceId = $command->argument('inference_id');

        $response = Http::withBasicAuth(config('services.scenario.key'), config('services.scenario.key'))
            ->acceptJson()
            ->get("https://api.cloud.scenario.gg/v1/models/$model/inferences/$inferenceId");

        return $response->json();
    }
}
