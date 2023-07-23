<?php
namespace App\Actions;

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
        $this->benchmarkEmbeddings();
        $this->benchmarkChat();
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
