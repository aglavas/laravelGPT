<?php

namespace App\Actions;

use App\Models\Message;
use Lorisleiva\Actions\Concerns\AsAction;
use OpenAI\Laravel\Facades\OpenAI;
use Probots\Pinecone\Client as Pinecone;
use Probots\Pinecone\Requests\Exceptions\MissingNameException;

class AddContextToPromptMessage
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

        $embeddings = OpenAI::embeddings()->create([
            'model' => 'text-embedding-ada-002',
            'input' => $promptMessage->content,
        ])->embeddings;

        $conversationResults = $pinecone->index('laravelgpt')
            ->vectors()
            ->query(
                $embeddings[0]->embedding,
                'chatbot',
                [
                    'type' => [
                        '$eq' => 'conversation'
                    ]
                ],
                2
            )->json('matches');

        $results = $pinecone->index('laravelgpt')
            ->vectors()
            ->query(
                $embeddings[0]->embedding,
                'chatbot',
                [
                    'type' => [
                        '$ne' => 'conversation'
                    ]
                ],
                2
            )->json('matches');

        $metadata = $promptMessage->metadata ?? [];
        $metadata['context'] = [...$results, ... $conversationResults];
        $promptMessage->metadata = $metadata;
        $promptMessage->save();
    }
}
