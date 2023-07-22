<?php

namespace App\Actions;

use Illuminate\Support\Collection;
use Spatie\PdfToText\Pdf;
use Lorisleiva\Actions\Concerns\AsAction;
use Illuminate\Support\Str;
use OpenAI\Laravel\Facades\OpenAI;
use \Probots\Pinecone\Client as Pinecone;

class EmbeddPdf
{
    use AsAction;

    /**
     * @var string
     */
    public string $commandSignature = 'embed:pdf';

    /**
     *
     */
    public function handle(): bool
    {
        $fileText = Pdf::getText(
            storage_path('app/WEF_Global_Risks_Report_2023.pdf'),
            config('services.pdftotext.path')
        );

        $content = Str::of($fileText)
            ->split("/\f/")
            ->toArray();

        $pinecone = new Pinecone(env('PINECONE_API_KEY'), env('PINECONE_ENV'));
        $embeddings = OpenAI::embeddings()->create([
            'model' => 'text-embedding-ada-002',
            'input' => $content,
        ])->embeddings;

        $pinecone->index('laravelgpt')->vectors()->delete([], 'wef', true);

        collect($embeddings)->chunk(20)->each(function (Collection $chunk, $chunkIndex) use ($pinecone, $content) {
            $pinecone->index('laravelgpt')->vectors()->upsert(
                $chunk->pluck('embedding')->map(function ($embedding, $index) use ($content, $chunkIndex) {
                    return [
                        'id' => (string) ($chunkIndex * 20 + $index),
                        'values' => $embedding,
                        'metadata' => [
                            'text' => $content[$chunkIndex * 20 + $index],
                            'page' => $chunkIndex * 20 + $index + 1,
                        ]
                    ];
                })->toArray(),
                'wef'
            );
        });
        $pinecone->index('laravelgpt')->vectors()->query($embeddings[0]->embedding, 'wef', [], 4)->json();

        return true;
    }
}
