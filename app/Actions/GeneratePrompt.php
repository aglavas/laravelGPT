<?php

namespace App\Actions;

use Illuminate\Console\Command;
use Lorisleiva\Actions\Concerns\AsAction;
use OpenAI\Laravel\Facades\OpenAI;

class GeneratePrompt
{
    use AsAction;

    /**
     * @var string
     */
    public string $commandSignature = 'generate:prompt {scene}';

    /**
     *
     */
    public function handle(Command $command): bool
    {
        $scene = $command->argument('scene');

        $content = sprintf(
            "%s %s %s %s %s %s",
            'Describe a scene as vividly as possible, but also as concisely as possible',
            'using just the key descriptive words regarding what there is,what the colors',
            'are, some of the key elements, the aesthetic visual style, genre, etc.',
            'Comma-separate the individual descriptive parts. Make the most important element',
            '(((surrounded by three parentheses))). The scene is:',
            $scene
        );

        $response = OpenAI::chat()->create([
            'model' => 'gpt-3.5-turbo-16k',
            'messages' => [
                ['role' => 'user', 'content' => $content]
            ]
        ]);

        dump($response->choices[0]->message->content);
        return $response->choices[0]->message->content;
    }
}
