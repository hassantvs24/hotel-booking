<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RoomRequest extends Model
{
    protected $guarded = [];

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function room_request_accepteds(): HasMany
    {
        return $this->hasMany(RoomRequestAccepted::class, 'room_requests_id');
    }
    /**
     * Scope to check for room request date overlaps with the given date range
     */
    public function scopeCheckOtherRoomDateOverlap($query, $roomIds, $checkIn, $checkOut)
    {
        // Ensure $checkIn and $checkOut are Carbon instances
        $checkIn = Carbon::parse($checkIn);
        $checkOut = Carbon::parse($checkOut);

        return $query->whereIn('room_id', $roomIds)
            ->where(function ($q) use ($checkIn, $checkOut) {
                $q->whereBetween('check_in', [$checkIn, $checkOut]) 
                    ->orWhereBetween('check_out', [$checkIn, $checkOut])
                    ->orWhere(function ($q) use ($checkIn, $checkOut) {
                        $q->where('check_in', '<=', $checkIn)
                            ->where('check_out', '>=', $checkOut);
                    });
            });
    }
}
