<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    use HasFactory;

    public function booking() :BelongsTo
    {
        return $this->belongsTo(Booking::class, 'booking_id', 'booking_number');
    }

    public function user () :BelongsTo
    {
        return $this->belongsTo(User::class,'user_id');
    }

    public function refundRequests(): HasMany
    {
        return $this->hasMany(RefundRequest::class);
    }
}
