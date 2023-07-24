<?php

namespace App\Actions;

use App\Events\PromptResponseStarted;
use App\Events\PromptResponseUpdated;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Console\Command;
use Lorisleiva\Actions\Concerns\AsAction;
use OpenAI\Laravel\Facades\OpenAI;

class HandlePrompt
{
    /**
     *
     */
    use AsAction;

    /**
     * @param array $messages
     * @param int $id
     * @return mixed
     */
    public function handle(Message $promptMessage, Message $pendingMessage)
    {
        PromptResponseStarted::dispatch($pendingMessage);

        $response = OpenAI::chat()->create([
            'model' => 'gpt-3.5-turbo-16k',
            'messages' => $promptMessage->conversation->toOpenAIChatMessages()
        ]);

        $pendingMessage->content = $response->choices[0]->message->content;
        $pendingMessage->save();

        PromptResponseUpdated::dispatch($pendingMessage);
    }
}
