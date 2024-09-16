<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingAccepted extends Model
{
    public function bookingRequest(): BelongsTo
    {
        return $this->belongsTo(BookingRequest::class, 'booking_requests_id');
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'property_id');
    }
    
}
