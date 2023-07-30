<?php

namespace Tests\Feature\Ai;

use Illuminate\Support\Facades\Http;
use OpenAI\Laravel\Facades\OpenAI;
use OpenAI\Resources\Embeddings;
use OpenAI\Responses\Embeddings\CreateResponse;
use \Probots\Pinecone\Client as Pinecone;
use App\Actions\EmbeddWebV2;
use Tests\TestCase;

class EmbedWebTest extends TestCase
{
    /**
     * Test EmbedWebV2 action
     *
     *
     * @test
     * @return void
     */
    public function test_it_works(): void
    {
        Http::fake([
            'https://chrome.browserless.io/*' => Http::response(file_get_contents(base_path('tests/stubs/article.html')), 200),
        ]);

        $openai = OpenAI::fake([
            CreateResponse::fake()
        ]);

        $pinecone = $this->mock(Pinecone::class);

        $pinecone->shouldReceive('index->vectors->delete')->with([], 'chatbot', false, \Mockery::on(function($arg) {
            return $arg['url']['$eq'] == 'https://example.com';
        }))->once();

        $pinecone->shouldReceive('index->vectors->upsert')->with(\Mockery::on(function($arg) {
            return count($arg) == 1 &&
                $arg[0]['metadata']['url'] == 'https://example.com';
        }), 'chatbot')->once();

        EmbeddWebV2::make()->handle('https://example.com');

        $openai->assertSent(Embeddings::class, function($method, $parameters) {
            return $parameters['model'] == 'text-embedding-ada-002';
        });
    }
}
