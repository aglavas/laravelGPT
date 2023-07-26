<?php

namespace App\Http\Controllers;

use App\Actions\EmbedConversationMessage;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UpdateDashboardConversationController extends Controller
{
    /**
     * @param Request $request
     * @param Conversation $conversation
     * @return RedirectResponse
     * @throws \Probots\Pinecone\Requests\Exceptions\MissingNameException
     */
    public function __invoke(Request $request, Conversation $conversation): RedirectResponse
    {
        $messages = $request->input('messages');
        $messageCollection = collect($messages)->sortBy('id');

        foreach ($messageCollection as $requestMessage) {
            /** @var Message $message */
            $message = $conversation->messages->firstWhere('id', $requestMessage['id']);
            $message->content = $requestMessage['content'];
            $message->usable = $requestMessage['usable'];

            if (
                $message->role == 'assistant' && ($message->usable && ($message->hasBeenEmbedded() === false))
            ) {
                EmbedConversationMessage::make()->handle($message);
                $message->markEmbedded();
            } elseif (!$message->usable && $message->hasBeenEmbedded()) {
                //@todo Delete embeddings
                $message->markEmbedded(false);
            }

            $message->save();
        }

        return redirect()->action(ShowDashboardConversationController::class, ['conversation' => $conversation]);
    }
}
