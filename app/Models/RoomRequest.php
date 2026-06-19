<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class RoomRequest extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'check_in'                => 'date',
        'check_out'               => 'date',
        'request_expiration_time' => 'datetime',
        'discount_price'          => 'float',
        'counter_price'           => 'float',
        'bid_number'              => 'integer',
    ];

    // ══════════════════════════════════════════════════════
    //  RELATIONSHIPS
    // ══════════════════════════════════════════════════════

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    // One bid can have one accepted record
    public function acceptedRequest(): HasOne
    {
        return $this->hasOne(RoomRequestAccepted::class, 'room_requests_id');
    }

    // Keep HasMany for backward compat if used elsewhere
    public function acceptedRequests(): HasMany
    {
        return $this->hasMany(RoomRequestAccepted::class, 'room_requests_id');
    }

    // ══════════════════════════════════════════════════════
    //  SCOPES
    // ══════════════════════════════════════════════════════

    /**
     * Active bids — count toward the 3-bid limit.
     * Timeout and Declined are dead — don't count.
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['Pending', 'Approved', 'Counter']);
    }

    /**
     * Correct date overlap check.
     *
     * Two date ranges overlap when:
     *   existing.check_in  < new.check_out
     *   AND
     *   existing.check_out > new.check_in
     *
     * The old whereBetween formula was wrong — it missed ranges
     * that fully contained the search window.
     */
    public function scopeCheckOtherRoomDateOverlap($query, $roomIds, $checkIn, $checkOut)
    {
        $checkIn  = Carbon::parse($checkIn)->format('Y-m-d');
        $checkOut = Carbon::parse($checkOut)->format('Y-m-d');

        return $query
            ->whereIn('room_id', $roomIds)
            ->where('check_in',  '<', $checkOut)
            ->where('check_out', '>', $checkIn);
    }

    /**
     * Pending bids that have passed their expiration time.
     * Used by ExpireRoomRequests job.
     */
    public function scopeExpired($query)
    {
        return $query
            ->where('status', 'Pending')
            ->where('request_expiration_time', '<', now());
    }

    // ══════════════════════════════════════════════════════
    //  HELPERS
    // ══════════════════════════════════════════════════════

    public function isExpired(): bool
    {
        return $this->request_expiration_time
            && $this->request_expiration_time->isPast()
            && $this->status === 'Pending';
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['Pending', 'Approved', 'Counter']);
    }

    public function getNightsAttribute(): int
    {
        return max(1, Carbon::parse($this->check_in)->diffInDays($this->check_out));
    }

    public function getTotalOfferedAttribute(): float
    {
        return $this->discount_price * $this->nights;
    }

    public function getEffectivePriceAttribute(): float
    {
        // Counter price takes priority if set, otherwise use guest's offered price
        return $this->counter_price ?? $this->discount_price;
    }
}
