<?php

use Illuminate\Support\Facades\Route;
use App\Actions\FirstPrompt;
use Illuminate\Http\Request;
use App\Models\Conversation;

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

    /** @var \App\Models\Conversation $conversation */
    $conversation->messages()->create([
        'content' => $request->input('prompt'),
        'role' => 'user'
    ]);

    $messages = $conversation->messages->map(function (\App\Models\Message $message) {
        return[
          'content' => $message->content,
          'role' => $message->role,
        ];
    })->toArray();

    $pinecone = new Pinecone(env('PINECONE_API_KEY'), env('PINECONE_ENV'));

    $question = \OpenAI\Laravel\Facades\OpenAI::embeddings()->create([
        'model' => 'text-embedding-ada-002',
        'input' => $request->input('prompt')
    ]);

    $results = $pinecone->index('laravelgpt')->vectors()->query($question->embeddings[0]->embedding, 'podcast', [], 4)->json();

//    $test = collect($results['matches'])->pluck('metadata.text')->join("\n\n---\n\n");
//
//    dd($results, $test);

    $systemMessage = [
        'role' => 'system',
        'content' => sprintf(
            'Base your answer on the February 2023 podcast episode between Tim Urban and Lex Fridman. Here are some snippets from that may help you answer: %s',
                collect($results['matches'])->pluck('metadata.text')->join("\n\n---\n\n"),
            ),
    ];

    $result = $prompt->handle(array_merge([$systemMessage], $messages));

    $conversation->messages()->create([
        'content' => $result,
        'role' => 'assistant'
    ]);

    return redirect()->route('conversation', ['id' => $conversation->id]);
})->name('chat');
