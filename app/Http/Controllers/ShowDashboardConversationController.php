<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ShowDashboardConversationController extends Controller
{
    /**
     * @param Request $request
     * @param Conversation $conversation
     * @return \Inertia\Response
     */
    public function __invoke(Request $request, Conversation $conversation): \Inertia\Response
    {
        $conversation->load('messages');
        /** @var \Illuminate\Database\Eloquent\Collection $messages **/
        $messages = $conversation->messages;
        $messages = $messages->sortBy('id');
        $conversationArray = $conversation->toArray();
        $conversationArray['messages'] = $messages->values()->toArray();
        return Inertia::render('Conversation', [
           'conversation' => $conversationArray
        ]);
    }
}
