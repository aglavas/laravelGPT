<?php

namespace App\Console\Commands;

use App\Actions\GenerateImage;
use App\Actions\GeneratePrompt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;
use Illuminate\Console\Command;

class GenerateImageWithExternalService extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:image:external {scene}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates image with external AI service - Stability';

    /**
     * Execute the console command.
     */
    public function handle(GeneratePrompt $generatePrompt, GenerateImage $generateImage)
    {
        $requestedPrompt = $this->argument('scene');
        $filename = Str::slug($requestedPrompt);
        $this->info($requestedPrompt);
        $enhancedPrompt = $generatePrompt->handle($this);
        dump($enhancedPrompt);

        $this->getDefinition()->addArguments([
            new InputArgument('model'),
            new InputArgument('prompt')
        ]);

        $this->input->setArgument('model', 'stable-diffusion-xl');
        $this->input->setArgument('prompt', $enhancedPrompt);
        $resultArray = $generateImage->handle($this);
        $base64Image = $resultArray['artifacts'][0]['base64'];
        $fileName = $filename . uniqid() . '.png';
        $decodedData = base64_decode($base64Image);
        Storage::disk('ai_images')->put($fileName, $decodedData);
        $this->info("Image is generated in storage folder");
    }
}
