<?php

namespace App\Actions;

use App\Events\ChatResponseUpdated;
use Illuminate\Console\Command;
use App\Models\Message;
use Lorisleiva\Actions\Concerns\AsAction;
use OpenAI\Laravel\Facades\OpenAI;

class StreamingPrompt
{
    /**
     *
     */
    use AsAction;

    /**
     * @param Message $pendingMessage
     * @param array $messages
     * @return void
     */
    public function handle(Message $pendingMessage, array $messages): void
    {
        activity()
            ->event('streaming_prompt')
            //->performedOn($conversation)
            ->withProperties($messages)
            ->log('STREAMING_PROMPT_LOG');

        $stream = OpenAI::chat()->createStreamed([
            'model' => 'gpt-3.5-turbo-16k',
            'messages' => $messages
        ]);

        $buffer = '';
        $content = '';

        foreach ($stream as $response) {
            $delta = $response->choices[0]->delta->content;

            if (empty($delta)) {
                continue;
            }

            $buffer .= $delta;

            if (strlen($buffer) < 50) {
                continue;
            }

            $content .= $buffer;
            ChatResponseUpdated::dispatch($pendingMessage->conversation_id, $content);
            $pendingMessage->content = $content;
            // If using Scout, uncomment these surrounding lines.
            // Message::withoutSyncingEvents(function () use ($pendingMessage) {
            $pendingMessage->save();
            // });

            $buffer = '';
        }

        $content .= $buffer;

        ChatResponseUpdated::dispatch($pendingMessage->conversation_id, $content);

        $pendingMessage->content = $content;
        $pendingMessage->save();
    }
}
