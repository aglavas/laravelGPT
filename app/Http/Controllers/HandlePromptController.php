<?php

namespace App\Http\Controllers;

use App\Actions\HandlePrompt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Models\Conversation;

class HandlePromptController extends Controller
{
    /**
     * @param Request $request
     * @param string $conversationId
     * @return RedirectResponse
     */
    public function __invoke(Request $request, string $conversationId): RedirectResponse
    {
        if ($conversationId == 'new') {
            $conversation = Conversation::create();
        } else {
            $conversation = Conversation::query()->where('public_id', $conversationId)->firstOrFail();
        }

        //Trimming prompt is important!
        $promptMessage = $conversation->messages()->create([
            'content' => trim($request->input('prompt', null)),
            'role' => 'user'
        ]);

        $pendingMessage = $conversation->messages()->create([
            'content' => '',
            'role' => 'assistant'
        ]);

        HandlePrompt::dispatch($promptMessage, $pendingMessage);

        return redirect()->action(ShowWidgetConversationController::class, ['id' => $conversation->public_id]);
    }
}
