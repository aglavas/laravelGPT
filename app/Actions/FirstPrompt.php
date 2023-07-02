<?php

namespace App\Actions;

use Illuminate\Console\Command;
use Lorisleiva\Actions\Concerns\AsAction;
use OpenAI\Laravel\Facades\OpenAI;

class FirstPrompt
{
    /**
     *
     */
    use AsAction;

    /**
     * @var string
     */
    public $commandSignature = 'init-chat {prompt : The user prompt';

    /**
     * @param string $prompt
     * @return mixed
     */
    public function handle(array $messages)
    {
        return OpenAI::chat()->create([
            'model' => 'gpt-3.5-turbo',
            'messages' => $messages
        ])->choices[0]->message->content;
    }

    /**
     * @param Command $command
     */
    public function asCommand(Command $command)
    {
        $command->comment($this->handle($command->argument('prompt')));
    }
}
