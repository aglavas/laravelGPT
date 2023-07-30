<?php
namespace App\Actions;

use App\Models\Conversation;
use App\Models\Enums\UsageType;
use App\Models\Message;
use App\Models\User;
use Mis3085\Tiktoken\Facades\Tiktoken;
use OpenAI\Laravel\Facades\OpenAI;
use Lorisleiva\Actions\Concerns\AsAction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Bus;
use Illuminate\Http\Client\Pool;
use Illuminate\Console\Command;

class Sandbox
{
    use AsAction;

    /**
     * @var string
     */
    public string $commandSignature = 'sandbox';

    /**
     * @return void
     */
    public function handle(): void
    {
        $this->usageCostsTrack();
        $this->benchmarkEmbeddings();
        $this->benchmarkChat();
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function usageCostsTrack(): void
    {
        try {
            $this->calculatePrompt();
            $this->calculateStream();
        } catch (\Exception $exception) {
            info($exception->getMessage());
        }
    }

    /**
     * Calculate prompt usage
     *
     * @return bool
     * @throws \Exception
     */
    private function calculatePrompt(): bool
    {
        $response = OpenAI::chat()->create([
            'model' => $model = 'gpt-4',
            'messages' => $messages = [
                [
                    'role' => 'user',
                    'content' => $prompt = 'Say "hi". Dont be shy'
                ]
            ],
            'temperature' => 0,
            'max_tokens' => 10,
        ]);

        $conversation = Conversation::create();
        $user = User::factory()->create();

        $responseUsage = $response->usage;
        $calculatedUsage = $this->tokensFromMessages($messages, $model);
        $responseContent = $response->choices[0]->message->content;

        /** @var Message $userMessage */
        $userMessage = Message::create([
            'content' => $prompt,
            'conversation_id' => $conversation->id,
            'role' => 'user'
        ]);

        $userMessage->usages()->create([
            'usage_type' => UsageType::Gpt4ChatPrompt,
            'usage_amount' => $responseUsage->promptTokens,
            'user_id' => $user->id
        ]);

        activity()
            ->event('TEST')
            ->withProperties([
                'usage_calc' => $calculatedUsage,
                'usage_api' => json_encode($responseUsage),
                'type' => 'prompt'
            ])
            ->log('TEST_LOG');


        $assistantMessage = Message::create([
            'content' => $responseContent,
            'conversation_id' => $conversation->id,
            'role' => 'assistant'
        ]);

        $assistantMessage->usages()->create([
            'usage_type' => UsageType::Gpt4ChatResponse,
            'usage_amount' => $responseUsage->completionTokens,
            'user_id' => $user->id
        ]);

        $tokenCount = Tiktoken::count($responseContent);

        activity()
            ->event('TEST')
            ->withProperties([
                'usage_calc' => $tokenCount,
                'usage_api' => json_encode($responseUsage),
                'type' => 'response'
            ])
            ->log('TEST_LOG');

        return true;
    }

    /**
     * Calculate stream usage
     *
     * @return bool
     * @throws \Exception
     */
    private function calculateStream(): bool
    {
        $streamResponse = OpenAI::chat()->createStreamed([
            'model' => 'gpt-4',
            'messages' => $messages = [
                [
                    'role' => 'user',
                    'content' => $prompt = 'Say "Well, hello there! My name is DAN, what is your name?"'
                ],
                [
                    'role' => 'assistant',
                    'content' => 'Hi Dan, nice to meet you. My name...'
                ],
            ],
            'temperature' => 0,
            'max_tokens' => 10,
        ]);

        $promptTokens = $this->tokensFromMessages($messages, 'gpt-4');

        $tokens = [];
        foreach ($streamResponse as $response) {
            $content = $response->choices[0]->delta->content;

            if (!$content) {
                continue;
            }

            $tokens[] = $content;
        }

        $result = collect($tokens)->flatten()->join('');
        $responseCount = Tiktoken::count($result);

        $conversation = Conversation::create();
        $user = User::factory()->create();

        /** @var Message $userMessage */
        $userMessage = Message::create([
            'content' => $prompt,
            'conversation_id' => $conversation->id,
            'role' => 'user'
        ]);

        $userMessage->usages()->create([
            'usage_type' => UsageType::Gpt4ChatPrompt,
            'usage_amount' => $promptTokens,
            'user_id' => $user->id
        ]);

        $assistantMessage = Message::create([
            'content' => $result,
            'conversation_id' => $conversation->id,
            'role' => 'assistant'
        ]);

        $assistantMessage->usages()->create([
            'usage_type' => UsageType::Gpt4ChatResponse,
            'usage_amount' => $responseCount,
            'user_id' => $user->id
        ]);

        return true;
    }

    /**
     * @param $messages
     * @param $model
     * @return int
     * @throws \Exception
     */
    public function tokensFromMessages($messages, $model="gpt-3.5-turbo-0613"): int
    {
        // Return the number of tokens used by a list of messages.
        try {
            $encoding = Tiktoken::setEncoderForModel($model);
        } catch (\Exception $e) {
            echo "Warning: model not found. Using cl100k_base encoding.";
            $encoding = Tiktoken::getEncoding("cl100k_base");
        }

        $tokensPerMessage = 0;
        $tokensPerName = 0;

        if (in_array($model, ["gpt-3.5-turbo-0613", "gpt-3.5-turbo-16k-0613", "gpt-4-0314", "gpt-4-32k-0314", "gpt-4-0613", "gpt-4-32k-0613"])) {
            $tokensPerMessage = 3;
            $tokensPerName = 1;
        } elseif ($model == "gpt-3.5-turbo-0301") {
            $tokensPerMessage = 4;  // every message follows <|im_start|>{role/name}\n{content}<|end|>\n
            $tokensPerName = -1;  // if there's a name, the role is omitted
        } elseif (str_contains($model, "gpt-3.5-turbo")) {
            echo "Warning: gpt-3.5-turbo may update over time. Returning num tokens assuming gpt-3.5-turbo-0613.";
            return $this->tokensFromMessages($messages, $model="gpt-3.5-turbo-0613");
        } elseif (str_contains($model, "gpt-4")) {
            echo "Warning: gpt-4 may update over time. Returning num tokens assuming gpt-4-0613.";
            return $this->tokensFromMessages($messages, $model="gpt-4-0613");
        } else {
            throw new \Exception(
                "num_tokens_from_messages() is not implemented for model {$model}. See https://github.com/openai/openai-python/blob/main/chatml.md for information on how messages are converted to tokens."
            );
        }

        $numTokens = 0;
        foreach ($messages as $message) {
            $numTokens += $tokensPerMessage;
            foreach ($message as $key => $value) {
                $numTokens += count($encoding->encode($value));
                if ($key == "name") {
                    $numTokens += $tokensPerName;
                }
            }
        }

        $numTokens += 3;  // every reply is primed with <|im_start|>assistant<|im_sep|>

        return $numTokens;
    }

    /**
     * @return void
     */
    public function benchmarkEmbeddings(): void
    {
        $values = [];
        for ($i = 0; $i < 50; $i++) {
            $values[] = fake()->paragraphs(nb: 10, asText: true);
        }

        $start = microtime(true);

        OpenAI::embeddings()->create([
            'model' => 'text-embedding-ada-002',
            'input' => $values,
        ]);

        $multiEmbed = microtime(true) - $start;

        $key = config('openai.api_key');

        $start = microtime(true);

        Http::pool(fn (Pool $pool) => collect($values)
            ->map(fn (string $value) => $pool->withToken($key)
                ->post('https://api.openai.com/v1/embeddings', [
                    'model' => 'text-embedding-ada-002',
                    'input' => $value,
                ])
            )
        );

        $pool = microtime(true) - $start;

        dump('embed',$multiEmbed, $pool);
    }

    /**
     * @return void
     */
    public function benchmarkChat(): void
    {
        $max = 3;

        $values = explode("\n", str_repeat("Who are you?\n", $max));

        $start = microtime(true);

        for ($i = 0; $i < $max; $i++) {
            OpenAI::chat()->create([
                'model' => 'gpt-3.5-turbo',
                'max_tokens' => 50,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $values[$i]
                    ]
                ]
            ]);
        }
        $sequentialChat = microtime(true) - $start;

        $key = config('openai.api_key');

        $start = microtime(true);

        // Consider
        // Bus::batch([
        //     new AgentTaskOne(),
        //     new AgentTaskTwo(),
        // ]);

        $responses = Http::pool(fn (Pool $pool) => collect($values)
            ->map(fn (string $value) => $pool->withToken($key)
                ->timeout(10)
                ->retry(2, 500)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-3.5-turbo',
                    'max_tokens' => 50,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $value
                        ]
                    ]
                ])
            )
        );

        $pool = microtime(true) - $start;

        dump('chat', $sequentialChat, $pool);
    }

    /**
     * @param Command $command
     * @return void
     */
    public function asCommand(Command $command): void
    {
        $this->handle();
    }
}
