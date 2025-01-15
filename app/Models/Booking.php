<?php

namespace App\Models;

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

    public function scopeCheckDateOverlap($query, $roomIds, $checkIn, $checkOut)
    {
        return $query->whereIn('room_id', $roomIds)
            ->where(function ($q) use ($checkIn, $checkOut) {
                $q->whereBetween('checkin', [$checkIn, $checkOut])
                    ->orWhereBetween('checkout', [$checkIn, $checkOut])
                    ->orWhere(function ($q) use ($checkIn, $checkOut) {
                        $q->where('checkin', '<=', $checkIn)
                            ->where('checkout', '>=', $checkOut);
                    });
            });
    }

    public function transaction():HasOne
    {
        return $this->hasOne(Transaction::class,'booking_id', 'booking_number');
    }

}
