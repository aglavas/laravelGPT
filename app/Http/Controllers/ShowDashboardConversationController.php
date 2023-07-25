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
        return Inertia::render('Conversation', [
           'conversation' => $conversation->load('messages')->toArray()
        ]);
    }
}
