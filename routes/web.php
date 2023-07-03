<?php

use Illuminate\Support\Facades\Route;
use App\Actions\FirstPrompt;
use Illuminate\Http\Request;
use App\Models\Conversation;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

use \Probots\Pinecone\Client as Pinecone;
use Illuminate\Support\Facades\Artisan;

Route::get('/', function (FirstPrompt $prompt) {
    return $prompt->handle("Hello how are you?");
});

Route::get('/conversations/{id}', function ($id) {
    $conversation = ($id == 'new') ? null : Conversation::find($id);
    return view('conversation', [
        'conversation' => $conversation
    ]);
})->name('conversation');

Route::post('/chat/{id}', function (Request $request, FirstPrompt $prompt, $id) {
    if ($id == 'new') {
        $conversation = Conversation::create();
    } else {
        $conversation = Conversation::findOrFail($id);
    }

    $newPrompt = $request->input('prompt');

    /** @var \App\Models\Conversation $conversation */
    $conversation->messages()->create([
        'content' => $newPrompt,
        'role' => 'user'
    ]);

    $messages = $conversation->messages->map(function (\App\Models\Message $message) {
        return[
          'content' => $message->content,
          'role' => $message->role,
        ];
    })->toArray();

    $pinecone = new Pinecone(env('PINECONE_API_KEY'), env('PINECONE_ENV'));

    $pattern = '~https?://\S+~';
    preg_match_all($pattern, $newPrompt, $matches);
    $urls = collect($matches[0] ?? []);
    $urls->each(function($url) {
        Artisan::call('embed:web', ['url' => $url]);
    });

    $cleanPrompt = $urls->reduce(function (string $carry, string $url) {
        return Str::remove($url, $carry);
    }, $request->input('prompt'));


    $question = \OpenAI\Laravel\Facades\OpenAI::embeddings()->create([
        'model' => 'text-embedding-ada-002',
        'input' => $cleanPrompt
    ]);

//    $results = $pinecone->index('laravelgpt')->vectors()->query($question->embeddings[0]->embedding, 'podcast', [], 5)->json();
    //    $test = collect($results['matches'])->pluck('metadata.text')->join("\n\n---\n\n");
    //    $systemMessage = [
//        'role' => 'system',
//        'content' => sprintf(
//            'Base your answer on the February 2023 podcast episode between Tim Urban and Lex Fridman. Here are some snippets from that may help you answer: %s',
//            collect($results['matches'])->pluck('metadata.text')->join("\n\n---\n\n"),
//            ),
//    ];

//    $results = $pinecone->index('laravelgpt')->vectors()->query($question->embeddings[0]->embedding, 'wef', [], 4)->json();
//    $context = collect($results['matches'])
//        ->map(function ($match) {
//            return 'From page number: '. $match['metadata']['page'] . "\n" . $match['metadata']['text'];
//        })->join("\n\n---\n\n");


    $results = $pinecone->index('laravelgpt')->vectors()->query($question->embeddings[0]->embedding, 'web', [], 4)->json();

    $context = collect($results['matches'])
        ->map(function ($match) {
            return $match['metadata']['text'];
        })->join("\n\n---\n\n");

    if ($urls->isNotEmpty()) {
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
    $result = $prompt->handle(array_merge([$systemMessage], $messages), $conversation->id);

    $conversation->messages()->create([
        'content' => $result . "\n" . collect($results['matches'])->pluck('metadata.page')->join(','),
        'role' => 'assistant'
    ]);

    return redirect()->route('conversation', ['id' => $conversation->id]);
})->name('chat');
