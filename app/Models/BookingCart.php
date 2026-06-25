<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingCart extends Model
{
    protected $fillable = [
        'user_id',
        'room_id',
        'check_in',
        'check_out',
        'adult',
        'children',
        'rooms',
        'price',
        'expires_at',
        'is_bid',       // true = bid/offer, false = direct booking
        'offer_price',  // guest's offered price per night
        'bid_message',  // optional note to owner
    ];

    protected $casts = [
        'check_in'    => 'date',
        'check_out'   => 'date',
        'expires_at'  => 'datetime',
        'price'       => 'decimal:2',
        'offer_price' => 'decimal:2',
        'is_bid'      => 'boolean',   // ensures true/false not 0/1 in JSON
    ];

    // ── Relationships ────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    // ── Helpers ──────────────────────────────────────────

    // Bid items never expire from cart (expires_at = null)
    // Only direct room holds expire after 30 minutes
    public function isExpired(): bool
    {
        if ($this->is_bid) return false;
        return $this->expires_at && $this->expires_at->isPast();
    }
}
