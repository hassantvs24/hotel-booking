<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatSpecialRequest extends Model
{
    protected $casts = ['responded_at' => 'datetime'];

    // Relations
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ChatConversation::class);
    }
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
    public function responder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responded_by');
    }
}
