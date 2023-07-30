<?php

namespace App\Actions;

use App\Models\Message;
use Lorisleiva\Actions\Concerns\AsAction;
use OpenAI\Laravel\Facades\OpenAI;
use Probots\Pinecone\Client as Pinecone;
use Probots\Pinecone\Requests\Exceptions\MissingNameException;

class EmbedConversationMessage
{
    use AsAction;

    /**
     *
     * Add context to prompt message
     *
     * @param Message $promptMessage
     * @return void
     * @throws MissingNameException
     */
    public function handle(Message $promptMessage): void
    {
        $pinecone = new Pinecone(env('PINECONE_API_KEY'), env('PINECONE_ENV'));

        $content = sprintf(
            "user: %s\nassistant: %s",
            $promptMessage->userMessage()->content,
            $promptMessage->content,
        );

        $embeddings = OpenAI::embeddings()->create([
            'model' => 'text-embedding-ada-002',
            'input' => $content,
        ])->embeddings;

        $conversationId = $promptMessage->conversation_id;
        $messageId = $promptMessage->conversation_id;

        $pinecone->index('laravelgpt')->vectors()->upsert([
            [
                'id' => "prompt-$conversationId-$messageId",
                'values' => $embeddings[0]->embedding,
                'metadata' => [
                    'text' => $content,
                    'prompt_message_id' => $promptMessage->id,
                    'type' => 'conversation'
                ]
            ]
        ],'chatbot');
    }
}
