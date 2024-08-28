<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BookingRequest extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function acceptedHotels(): HasMany
    {
        return $this->hasMany(BookingAccepted::class, 'booking_requests_id');
    }
}
