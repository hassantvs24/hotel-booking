<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

/** What the admin saw arrive in the bank from SSLCommerz. Never edited or deleted: a wrong one is voided (with a reason). */
class BankDeposit extends Model
{
    protected $guarded = [];

    protected $casts = ['period_from' => 'date', 'period_to' => 'date', 'received_on' => 'date', 'voided_at' => 'datetime'];

    private const VOID_ONLY = ['voided_at', 'voided_by', 'voided_by_name', 'void_reason', 'updated_at'];

    protected static function booted(): void
    {
        static::deleting(fn () => throw new LogicException('A bank deposit is never deleted. Void it with a reason.'));

        static::updating(function (self $d) {
            if ($d->getOriginal('voided_at') !== null) {
                throw new LogicException('A voided bank deposit cannot be changed.');
            }
            if (array_diff(array_keys($d->getDirty()), self::VOID_ONLY)) {
                throw new LogicException('A bank deposit cannot be edited. Void it and enter it again.');
            }
        });
    }

    public function isVoided(): bool
    {
        return $this->voided_at !== null;
    }
}
