<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PromptResponseUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var string
     */
    public $connection = 'sync';

    /**
     * Create a new event instance.
     */
    public function __construct(public readonly Message $pendingMessage)
    {
        activity()
            ->event('PROMPT_RESPONSE_UPDATE')
            ->performedOn($this->pendingMessage)
            ->withProperties(['id' => $this->pendingMessage->id, 'public_id' => $this->pendingMessage->public_id])
            ->log('STREAMING_PROMPT_LOG');
    }

    /**
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'PromptResponseUpdated';
    }

    /**
     * @return array
     */
    public function broadcastWith(): array
    {
        return  [
            'id' => $this->pendingMessage->public_id,
            'content' => $this->pendingMessage->content,
            'role' => $this->pendingMessage->role,
        ];
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('conversations.' . $this->pendingMessage->conversation->public_id . '.messages')
        ];
    }
}
