<?php

namespace App\Actions;

use App\Events\PromptResponseStarted;
use App\Events\PromptResponseUpdated;
use App\Models\Message;
use Lorisleiva\Actions\Concerns\AsAction;
use OpenAI\Laravel\Facades\OpenAI;

class HandlePrompt
{
    use AsAction;

    /**
     * @param Message $promptMessage
     * @param Message $pendingMessage
     * @return void
     * @throws \Probots\Pinecone\Requests\Exceptions\MissingNameException
     */
    public function handle(Message $promptMessage, Message $pendingMessage): void
    {
        //@todo Maybe add prompt message here, so it can be displayed immediatelly and not when response comes.
        PromptResponseStarted::dispatch($pendingMessage);

        //Add count and pass it to AddContext
        $conversationMessages = $this->addConversationMessages($promptMessage);
        $systemMessage = AddContextToPromptMessage::make()->handle($promptMessage);
        $allMessages = array_merge([$systemMessage], $conversationMessages);

//        $response = OpenAI::chat()->create([
//            'model' => 'gpt-3.5-turbo-16k',
//            'messages' => $allMessages
//        ]);

        try {
            $response = retry(2, fn() => OpenAI::chat()->create([
                'model' => 'gpt-3.5-turbo-16k',
                'messages' => $allMessages
            ]), 5000);
        } catch (\Exception $exception) {
            $pendingMessage->markEmbedded(false);
            //PromptResponseError::disptach($pendingMessage);
            //report($exception);
            return;
        }

        activity()
            ->event('PROMPT_HANDLED')
            ->withProperties([
                'chat_messages_context' => $allMessages,
                'assistant_response' => $response->choices[0]->message->content,
            ])
            ->log('Logging chat messages (with system prompt acting as context) along LLM response');

        $pendingMessage->content = $response->choices[0]->message->content;
        $pendingMessage->save();
        PromptResponseUpdated::dispatch($pendingMessage);
    }

    /**
     * Add previous messages
     *
     * @param Message $promptMessage
     * @return array
     */
    protected function addConversationMessages(Message $promptMessage): array
    {
        //@todo refactor to repository
        $conversationId = $promptMessage->conversation_id;
        $messagesCollection = Message::where('conversation_id', $conversationId)->where('id', '<=', $promptMessage->id)->orderBy('id', 'ASC')->get();
        return $messagesCollection->map(function (\App\Models\Message $message) {
            return[
                'content' => $message->content,
                'role' => $message->role,
            ];
        })->toArray();
    }
}
