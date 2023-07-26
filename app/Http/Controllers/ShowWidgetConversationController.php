<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Database\Eloquent\Collection;
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
        $conversation->load('messages');
        /** @var Collection $messages */
        $messages = $conversation->messages;
        $messages = $messages->sortBy('id');

        return Inertia::render('Widget/Widget', [
           'conversation' => [
               'id' => $conversation->public_id,
               'messages' => $messages->map(fn (Message $message) => [
                   'id' => $message->public_id,
                   'content' => $message->content,
                   'role' => $message->role
               ])->values()->toArray(),
           ]
        ]);
    }
}
