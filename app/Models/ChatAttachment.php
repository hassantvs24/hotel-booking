<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatAttachment extends Model
{
    protected $hidden = ['disk', 'path'];

    // Relations
    public function message(): BelongsTo
    {
        return $this->belongsTo(ChatMessage::class, 'chat_message_id');
    }
}
