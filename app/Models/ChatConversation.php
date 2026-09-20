<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatConversation extends Model
{
    protected $casts = [
        'last_message_at' => 'datetime',
        'closed_at' => 'datetime'
    ];

    // Relations

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'conversation_id');
    }

    public function lastMessage(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ChatMessage::class, 'conversation_id')->latestOfMany();
    }

    public function specialRequests(): HasMany
    {
        return $this->hasMany(ChatSpecialRequest::class, 'conversation_id');
    }
}
