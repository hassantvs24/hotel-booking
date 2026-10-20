<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

/**
 * One payout to one vendor. A draft can be changed; once it is PAID it is locked (only reversing it is allowed),
 * and a statement is never deleted. See the payouts migration.
 */
class PayoutStatement extends Model
{
    public const DRAFT     = 'draft';
    public const PAID      = 'paid';
    public const REVERSED  = 'reversed';
    public const CANCELLED = 'cancelled';

    public const METHODS = ['bank', 'bkash', 'nagad', 'rocket', 'cash', 'other'];

    protected $guarded = [];

    protected $casts = [
        'payable_until'            => 'date',
        'paid_at'                  => 'datetime',
        'reversed_at'              => 'datetime',
        'cancelled_at'             => 'datetime',
        'emailed_at'               => 'datetime',
        'account_snapshot'         => 'array',
        'account_recently_changed' => 'boolean',
    ];

    /** What may still be written on a paid statement: reversing it, and the note that the email went out */
    private const ALLOWED_AFTER_PAID = ['status', 'reversed_at', 'reversed_by', 'reversed_by_name', 'reverse_reason', 'emailed_at', 'updated_at'];

    protected static function booted(): void
    {
        static::deleting(fn () => throw new LogicException('A payout statement is never deleted. Cancel a draft, or reverse a paid one.'));

        static::updating(function (self $s) {
            $was = $s->getOriginal('status');

            if (in_array($was, [self::REVERSED, self::CANCELLED], true)) {
                if (array_diff(array_keys($s->getDirty()), ['emailed_at', 'updated_at'])) {
                    throw new LogicException("A {$was} payout statement cannot be changed.");
                }
            }

            if ($was === self::PAID) {
                $illegal = array_diff(array_keys($s->getDirty()), self::ALLOWED_AFTER_PAID);

                if ($illegal) {
                    throw new LogicException('A paid payout statement is locked (' . implode(', ', $illegal) . '). Reverse it to correct a mistake.');
                }

                if ($s->isDirty('status') && $s->status !== self::REVERSED) {
                    throw new LogicException('A paid payout statement can only be reversed.');
                }
            }
        });
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PayoutStatementLine::class, 'statement_id');
    }

    public function isDraft(): bool
    {
        return $this->status === self::DRAFT;
    }

    public function isPaid(): bool
    {
        return $this->status === self::PAID;
    }
}
