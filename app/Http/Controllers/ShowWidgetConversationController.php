<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ShowWidgetConversationController extends Controller
{
    /**
     * @param Request $request
     * @param string $publicId
     * @return \Inertia\Response
     */
    public function __invoke(Request $request, string $publicId): \Inertia\Response
    {
        $conversation = Conversation::query()->where('public_id', $publicId)->firstOrFail();

        return Inertia::render('Widget/Widget', [
           'conversation' => [
               'id' => $conversation->public_id,
               'messages' => $conversation->messages->map(fn (Message $message) => [
                   'id' => $message->public_id,
                   'content' => $message->content,
                   'role' => $message->role
               ])->toArray(),
           ]
        ]);
    }
}
