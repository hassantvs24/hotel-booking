<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Booking extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(BookingGroup::class, 'booking_group_id');
    }

    public function scopeCheckDateOverlap($query, array $roomIds, string $checkIn, string $checkOut)
    {
        return $query
            ->whereIn('status', ['reserved', 'approved'])
            ->where('checkin', '<', $checkOut)
            ->where('checkout', '>', $checkIn);
    }

    public function transaction():HasOne
    {
        return $this->hasOne(Transaction::class,'booking_id', 'booking_number');
    }

    public function refundRequests(): HasMany
    {
        return $this->hasMany(RefundRequest::class);
    }

}
