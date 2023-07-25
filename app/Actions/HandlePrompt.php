<?php

namespace App\Actions;

use App\Events\PromptResponseStarted;
use App\Events\PromptResponseUpdated;
use App\Models\Message;
use Lorisleiva\Actions\Concerns\AsAction;
use OpenAI\Laravel\Facades\OpenAI;

class HandlePrompt
{
    /**
     *
     */
    use AsAction;

    /**
     * @param Message $promptMessage
     * @param Message $pendingMessage
     * @return void
     */
    public function handle(Message $promptMessage, Message $pendingMessage): void
    {
        PromptResponseStarted::dispatch($pendingMessage);
        AddContextToPromptMessage::make()->handle($promptMessage);;
        $messages = $promptMessage->conversation->toOpenAIChatMessages($promptMessage);
        $response = OpenAI::chat()->create([
            'model' => 'gpt-3.5-turbo-16k',
            'messages' => $messages
        ]);

        $pendingMessage->content = $response->choices[0]->message->content;
        $pendingMessage->save();
        PromptResponseUpdated::dispatch($pendingMessage);
    }
}
