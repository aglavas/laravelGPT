<?php

namespace App\Actions;

use App\Models\Message;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;
use OpenAI\Laravel\Facades\OpenAI;
use Probots\Pinecone\Client as Pinecone;
use Probots\Pinecone\Requests\Exceptions\MissingNameException;

class AddContextToPromptMessage
{
    use AsAction;

    /**
     * @var bool
     */
    public bool $hasUrls = false;

    /**
     *
     * Add context to prompt message
     *
     * @param Message $promptMessage
     * @return void
     * @throws MissingNameException
     */
    public function handle(Message $promptMessage): array
    {
        $pinecone = new Pinecone(env('PINECONE_API_KEY'), env('PINECONE_ENV'));
        $newPromptContent = $promptMessage->content;
        $pattern = '~https?://\S+~';
        preg_match_all($pattern, $newPromptContent, $matches);
        $urls = collect($matches[0] ?? []);

        if (count($urls)) {
            $this->hasUrls = true;
            $this->handleUrls($urls);
            $cleanPrompt = $urls->reduce(function (string $carry, string $url) {
                return Str::remove($url, $carry);
            }, $newPromptContent);
            //Add URLS back
            $urlsString = implode(', ', $urls->toArray());
            $newPromptContent = $cleanPrompt . " $urlsString";
        }

        $embeddings = OpenAI::embeddings()->create([
            'model' => 'text-embedding-ada-002',
            'input' => $newPromptContent,
        ])->embeddings;

        if ($this->hasUrls) {
            $results = $pinecone->index('laravelgpt')
                ->vectors()
                ->query(
                    $embeddings[0]->embedding,
                    'chatbot',
                    [
                        'type' => [
                            '$eq' => 'web'
                        ]
                    ],
                    4
                )->json('matches');
        } else {
            //@todo Learning from conversation should be handled better. Does it needs to learn from all conversations?
            //@todo What if inital prompt is "Hello", does it needs to pull all conversation context?
            //@todo If we are pulling context from conversations and we filter per conversations, should it be add if current conversation is long or in any case?

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

            $sourcesResults = $pinecone->index('laravelgpt')
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

            $results = [...$sourcesResults, ... $conversationResults];
        }

        if (count($results)) {
            $metadata = $promptMessage->metadata ?? [];
            $metadata['context'] = $results;
            $promptMessage->metadata = $metadata;
            $promptMessage->save();

            $context = collect($results)
                ->map(function ($match) {
                    return $match['metadata']['text'];
                })->join("\n\n---\n\n");

            $systemMessage = [
                'role' => 'system',
                'content' => sprintf(
                    'Here are relevant snippets. You should base your answer on them: %s',
                    $context,
                ),
            ];

        } else {
            $systemMessage = [
                'role' => 'system',
                'content' => 'You are helpful assistant.',
            ];
        }

        return $systemMessage;
    }

    /**
     * Handle and embed URL's
     *
     * @param Collection $urls
     * @return bool
     */
    protected function handleUrls(Collection $urls): void
    {
        $urls->each(function($url) {
            Artisan::call('embed:web-chatbot', ['url' => $url]);
        });
    }
}
