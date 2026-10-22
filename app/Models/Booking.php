<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Booking extends Model
{
    /** refund_policy: the refund rules the guest was shown when booking (see RefundPolicyService::snapshot) */
    protected $casts = ['refund_policy' => 'array', 'main_guest_id_number' => 'encrypted'];

    /** The ID of the person who will stay never goes out in a normal answer: the owner and the admin ask for it on purpose */
    protected $hidden = ['main_guest_id_number'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
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

    public function reviewSubmission(): HasOne
    {
        return $this->hasOne(ReviewSubmission::class);
    }

}
