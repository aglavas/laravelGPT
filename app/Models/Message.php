<?php

namespace App\Models;

use App\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use HasFactory, HasPublicId;

    /**
     * @var string
     */
    protected $table = 'messages';

    /**
     * @var array
     */
    protected $fillable = ['content', 'role', 'metadata', 'usable', 'conversation_id'];

    /**
     * @var string[]
     */
    protected $casts = [
        'metadata' => 'array',
        'usable' => 'boolean',
    ];

    /**
     * Message belongs to conversation
     *
     * @return BelongsTo
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class, 'conversation_id', 'id');
    }

    /**
     * Usage costs
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function usages()
    {
        return $this->morphMany(Usage::class, 'usageable');
    }

    /**
     * Is message pending check
     *
     * @return bool
     */
    public function isPending():bool
    {
        return (($this->role === 'assistant') && ($this->content === ''));
    }

//    /**
//     * Content with context
//     *
//     * @return string
//     */
//    public function contentWithContextResults(): string
//    {
//        $context = collect($this->metadata['context'] ?? [])->pluck('metadata.text');
//        return $this->content . "\n\n" . $context->map(fn (string $text) => "Context: $text")->implode("\n");
//    }

    /**
     * Return next assistant message
     *
     * @return Message
     */
    public function userMessage(): Message
    {
        return $this->conversation->messages()->where('role', 'user')->where('id', '<', $this->id)->orderBy('id', 'ASC')->first();
    }

    /**
     * Has message already been embedded
     *
     * @return bool
     */
    public function hasBeenEmbedded(): bool
    {
        return $this->metadata['embedded'] ?? false;
    }

    /**
     * Mark message as embedded
     *
     * @param bool $embedded
     * @return void
     */
    public function markEmbedded(bool $embedded = true): void
    {
        $metadata = $this->metadata ?? [];
        $metadata['embedded'] = $embedded;
        $this->metadata = $metadata;

        if ($this->role === 'assistant') {
            $queryBuilder = $this->newQuery();
            $currentId = $this->id;
            $userId = $currentId - 1;
            $userMessage = $queryBuilder->where('id', $userId)->where('role', 'user')->first();
            $metadata = $userMessage->metadata;
            $metadata['embedded'] = $embedded;
            $userMessage->metadata = $metadata;
            $userMessage->save();
        }
    }
}
