<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessageRead extends Model
{
    protected $casts = ['read_at' => 'datetime'];

    // Relations
    public function message(): BelongsTo
    {
        return $this->belongsTo(ChatMessage::class, 'chat_message_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

}
