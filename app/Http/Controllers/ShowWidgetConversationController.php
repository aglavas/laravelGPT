<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ShowWidgetConversationController extends Controller
{
    public function __invoke(Request $request, string $publicId)
    {
        //$conversation = Conversation::query()->where('public_id', $publicId)->firstOrFail();
        //'id' => $conversation->public_id,
//               'messages' => $conversation->messages->map(fn (Message $message) => [
//                    'id' => $message->public_id,
//                    'content' => $message->content,
//                    'role' => $message->role
//               ])->toArray(),

        return Inertia::render('Widget/Widget', [
           'conversation' => [
               'id' => 'foo',
               'messages' => [
                    [
                        'id' => 'foo',
                        'content' => 'Hi',
                        'role' => 'user',
                    ],
                   [
                       'id' => 'bar',
                       'content' => 'Hello, how can I help you?',
                       'role' => 'assistant',
                   ]
               ]
           ]
        ]);
    }
}
