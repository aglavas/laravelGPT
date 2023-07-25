<?php

namespace App\Models;

use App\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    use HasFactory, HasPublicId;

    /**
     * @var string
     */
    protected $table = 'conversations';

    /**
     * Conversation has many messages
     *
     * @return HasMany
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'conversation_id', 'id');
    }

    /**
     * Return all messages from conversation except the pending one
     *
     * @param Message $upTo
     * @return array
     */
    public function toOpenAIChatMessages(Message $upTo): array
    {
        return $this->messages()
            ->where('id', '<=', $upTo->id)
            ->get()
            //->reject(fn (Message $message) => $message->isPending())
            ->map(fn (Message $message) => [
                'content' => $message->content,
                'role' => $message->role
            ])
            ->toArray();
    }
}
