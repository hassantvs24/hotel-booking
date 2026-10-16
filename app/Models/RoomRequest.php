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

    public const LIVE_STATUSES = ['Pending', 'Approved', 'Counter'];

    /** Live bids a guest may hold on one room at a time */
    public const MAX_BIDS = 3;

    /**
     * Active bids — count toward the 3-bid limit.
     * Timeout and Declined are dead — don't count. A bid whose window has
     * lapsed is dead too, even before the scheduled job flips its status,
     * so the limit never depends on the scheduler running.
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', self::LIVE_STATUSES)
            ->where(function ($q) {
                $q->whereNull('request_expiration_time')
                    ->orWhere('request_expiration_time', '>=', now());
            });
    }

    /**
     * Offers a guest has already spent on a room — what the 3-offer limit counts.
     * Live bids count, and so does a Declined one: the offer was made, the owner
     * (or the guest) turned it down, and it still used one of the three tries.
     * Timeout does not (the owner never answered) and neither does a paid bid.
     */
    public function scopeUsedOffers($query)
    {
        return $query->where(function ($q) {
            $q->where('status', 'Declined')
                ->orWhere(fn ($live) => $live->active());
        });
    }

    /**
     * How many of the 3 offers this guest has used on this room for stay dates
     * that overlap these ones. A different stay on the same room starts fresh.
     */
    public static function offersUsed(int $userId, int $roomId, $checkIn, $checkOut): int
    {
        $in  = Carbon::parse($checkIn)->format('Y-m-d');
        $out = Carbon::parse($checkOut)->format('Y-m-d');

        return static::where('user_id', $userId)
            ->where('room_id', $roomId)
            ->where('check_in', '<', $out)
            ->where('check_out', '>', $in)
            ->usedOffers()
            ->count();
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
     * Live bids (awaiting owner, countered, or awaiting payment) whose
     * window has passed. Used by ExpireRoomRequests job.
     */
    public function scopeExpired($query)
    {
        return $query
            ->whereIn('status', self::LIVE_STATUSES)
            ->whereNotNull('request_expiration_time')
            ->where('request_expiration_time', '<', now());
    }

    // ══════════════════════════════════════════════════════
    //  ONE NEGOTIATION = ONE LINE
    //  Every offer stays its own row (the history), but a negotiation —
    //  same guest, room and stay dates — is shown as one: its latest offer.
    // ══════════════════════════════════════════════════════

    /** Only the latest offer of each negotiation */
    public function scopeLatestPerNegotiation($query)
    {
        return $query->whereIn('room_requests.id', function ($sub) {
            $sub->from('room_requests')
                ->whereNull('deleted_at')
                ->selectRaw('MAX(id)')
                ->groupBy('user_id', 'room_id', 'check_in', 'check_out');
        });
    }

    /** The earlier offers of this negotiation, oldest first */
    public function earlierOffers()
    {
        return static::where('user_id', $this->user_id)
            ->where('room_id', $this->room_id)
            ->where('check_in', $this->check_in)
            ->where('check_out', $this->check_out)
            ->where('id', '<', $this->id)
            ->orderBy('id');
    }

    /** True when the guest has since made a newer offer for the same stay — this one is replaced */
    public function isSuperseded(): bool
    {
        return static::where('user_id', $this->user_id)
            ->where('room_id', $this->room_id)
            ->where('check_in', $this->check_in)
            ->where('check_out', $this->check_out)
            ->where('id', '>', $this->id)
            ->exists();
    }

    // ══════════════════════════════════════════════════════
    //  NEGOTIATION WINDOW
    // ══════════════════════════════════════════════════════

    /**
     * The per-night price range a guest's next bid on this room must fall in,
     * for stay dates that overlap their earlier bids:
     *
     *   floor   — the highest price they already offered (declined or expired
     *             included; only a paid bid is left out). Offering the same or
     *             less again makes no sense — they were already willing to pay that.
     *   ceiling — the lowest counter the owner currently has open. At or above it
     *             the guest should just accept the counter.
     *
     * Either end is null when it does not apply.
     */
    public static function offerWindow(int $userId, int $roomId, $checkIn, $checkOut): array
    {
        $in  = Carbon::parse($checkIn)->format('Y-m-d');
        $out = Carbon::parse($checkOut)->format('Y-m-d');

        $previous = static::where('user_id', $userId)
            ->where('room_id', $roomId)
            ->where('status', '!=', 'Done')
            ->where('check_in', '<', $out)
            ->where('check_out', '>', $in)
            ->get();

        $floor   = $previous->max('discount_price');
        $ceiling = $previous
            ->filter(fn (self $b) => $b->status === 'Counter' && !$b->isExpired())
            ->min('counter_price');

        return [
            'floor'   => $floor   !== null ? (float) $floor   : null,
            'ceiling' => $ceiling !== null ? (float) $ceiling : null,
        ];
    }

    /** Why this offer is outside the window, or null when it is fine. */
    public static function offerWindowError(int $userId, int $roomId, $checkIn, $checkOut, float $offer): ?string
    {
        // An offer the owner already accepted is waiting for payment — no point offering again
        $accepted = static::where('user_id', $userId)
            ->where('room_id', $roomId)
            ->where('status', 'Approved')
            ->where('check_in', '<', Carbon::parse($checkOut)->format('Y-m-d'))
            ->where('check_out', '>', Carbon::parse($checkIn)->format('Y-m-d'))
            ->get()
            ->first(fn (self $b) => !$b->isExpired());

        if ($accepted) {
            return 'The owner has already accepted an offer for this room and these dates. Complete that payment from My Bids first.';
        }

        // An offer still waiting for the owner's reply — wait for the answer before offering again
        $waiting = static::where('user_id', $userId)
            ->where('room_id', $roomId)
            ->where('status', 'Pending')
            ->where('check_in', '<', Carbon::parse($checkOut)->format('Y-m-d'))
            ->where('check_out', '>', Carbon::parse($checkIn)->format('Y-m-d'))
            ->get()
            ->first(fn (self $b) => !$b->isExpired());

        if ($waiting) {
            return 'Your earlier offer for this room and these dates is still waiting for the owner\'s reply. You can offer again once they respond.';
        }

        $window = static::offerWindow($userId, $roomId, $checkIn, $checkOut);
        $cents  = (int) round($offer * 100);
        $money  = fn (float $n) => 'BDT ' . rtrim(rtrim(number_format($n, 2), '0'), '.');

        if ($window['floor'] !== null && $cents <= (int) round($window['floor'] * 100)) {
            return 'Your offer must be higher than your earlier offer of ' . $money($window['floor'])
                . ' per night for this room and these dates.';
        }

        if ($window['ceiling'] !== null && $cents >= (int) round($window['ceiling'] * 100)) {
            return 'The owner has already countered at ' . $money($window['ceiling'])
                . ' per night. Offer less than that, or accept the counter from My Bids.';
        }

        return null;
    }

    // ══════════════════════════════════════════════════════
    //  HELPERS
    // ══════════════════════════════════════════════════════

    /**
     * Why an owner's counter price (per night) is not allowed, or null when it is.
     * A counter has to sit between the guest's offer and the room's price: at or below the guest's own offer the
     * guest would get the room for less than they asked, at or above the room price it is no discount at all.
     */
    public function counterPriceError(float $counter): ?string
    {
        $nights = max(1, Carbon::parse($this->check_in)->diffInDays(Carbon::parse($this->check_out)));
        $offer  = (float) $this->discount_price;
        $base   = (float) ($this->room?->base_price ?? 0);
        $money  = fn (float $n) => 'BDT ' . rtrim(rtrim(number_format($n, 2), '0'), '.');
        $stay   = fn (float $perNight) => $money($perNight * $nights) . ($nights > 1 ? " for {$nights} nights" : '');
        $cents  = fn (float $n) => (int) round($n * 100);

        if ($cents($counter) <= $cents($offer)) {
            return "Your counter must be higher than the guest's offer of " . $stay($offer) . '. To take the offer as it is, use Approve instead.';
        }

        if ($base > 0 && $cents($counter) >= $cents($base)) {
            return 'Your counter must be lower than the room price of ' . $stay($base) . '. A counter at the room price is no discount.';
        }

        return null;
    }

    /**
     * Offers whose time has run out become "Timeout" (and an accepted offer loses its payment slot).
     * The scheduled job does this every few minutes; the lists call it too, so nobody ever sees a lapsed
     * offer still labelled Pending / Counter / Accepted.
     */
    public static function expireLapsed(): int
    {
        $ids = static::expired()->pluck('id');

        if ($ids->isEmpty()) {
            return 0;
        }

        return \Illuminate\Support\Facades\DB::transaction(function () use ($ids) {
            RoomRequestAccepted::whereIn('room_requests_id', $ids)->delete();

            return static::whereIn('id', $ids)
                ->whereIn('status', self::LIVE_STATUSES)
                ->update(['status' => 'Timeout']);
        });
    }

    public function isExpired(): bool
    {
        return $this->request_expiration_time
            && $this->request_expiration_time->isPast()
            && in_array($this->status, self::LIVE_STATUSES);
    }

    public function isActive(): bool
    {
        return in_array($this->status, self::LIVE_STATUSES);
    }

    public function getNightsAttribute(): int
    {
        return max(1, Carbon::parse($this->check_in)->diffInDays($this->check_out));
    }

    public function getTotalOfferedAttribute(): float
    {
        return $this->discount_price * $this->nights;
    }

    /** Per-night price the guest pays: the owner's counter if there was one, else the guest's own offer. */
    public function getAgreedPriceAttribute(): float
    {
        return (float) ($this->counter_price ?? $this->discount_price);
    }

    public function getEffectivePriceAttribute(): float
    {
        // Counter price takes priority if set, otherwise use guest's offered price
        return $this->counter_price ?? $this->discount_price;
    }
}
