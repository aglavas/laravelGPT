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

        $messages = array_reverse($messages);

        foreach ($messages as $requestMessage) {
            /** @var Message $message */
            $message = $conversation->messages->firstWhere('id', $requestMessage['id']);
            $message->content = $requestMessage['content'];
            $message->useable = $requestMessage['useable'];

            if ($message->role == 'user') {
                if ($message->useable && $message->hasBeenEmbedded() === false) {
                    EmbedConversationMessage::make()->handle($message);
                    $message->markEmbedded();
                } elseif (!$message->useable) {
                    //@todo Delete embeddings
                    $message->markEmbedded(false);
                }
            }

            $message->save();
        }

        return redirect()->action(ShowDashboardConversationController::class, ['conversation' => $conversation]);
    }
}
