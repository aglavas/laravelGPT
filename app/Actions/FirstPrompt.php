<?php

namespace App\Actions;

use App\Models\Conversation;
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
     * @param array $messages
     * @param int $id
     * @return mixed
     */
    public function handle(array $messages, int $id)
    {
        $conversation = Conversation::find($id);

        activity()
            ->event('message_prompt')
            ->performedOn($conversation)
            ->withProperties($messages)
            ->log('PROMPT_LOG');

        return OpenAI::chat()->create([
            'model' => 'gpt-3.5-turbo-16k',
            'messages' => $messages
        ])->choices[0]->message->content;
    }

    /**
     * @param Command $command
     */
    public function asCommand(Command $command)
    {
        $command->comment($this->handle($command->argument('prompt'), $command->argument('id')));
    }
}
