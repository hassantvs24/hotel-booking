<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatMessage extends Model
{
    protected $casts = [
        'is_internal' => 'boolean'
    ];

    // Relations
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ChatConversation::class, 'conversation_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function reads(): HasMany
    {
        return $this->hasMany(ChatMessageRead::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ChatAttachment::class);
    }

}
