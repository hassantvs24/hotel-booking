<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * One line of the money ledger. Written once, never changed, never deleted: a mistake is fixed by a new line.
 * See the migration for what each column means.
 */
class LedgerEntry extends Model
{
    public const TYPE_PAYMENT = 'payment';
    public const TYPE_REFUND  = 'refund';

    protected $guarded = [];

    protected $casts = [
        'occurred_at'        => 'datetime',
        'occurred_on'        => 'date',
        'checkout_date'      => 'date',
        'commission_percent' => 'decimal:2',
        'is_test'            => 'boolean',
        'flags'              => 'array',
    ];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('A ledger line cannot be changed. Add a new line to correct it.'));
        static::deleting(fn () => throw new LogicException('A ledger line cannot be deleted. Add a new line to correct it.'));
    }

    public function hasFlag(string $flag): bool
    {
        return in_array($flag, $this->flags ?? [], true);
    }
}
