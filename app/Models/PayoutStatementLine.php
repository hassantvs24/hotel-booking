<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

/** One ledger line inside a payout statement, with its own copy of the amounts. Only ever freed (active -> null), never edited. */
class PayoutStatementLine extends Model
{
    protected $guarded = [];

    protected $casts = ['checkout_date' => 'date'];

    protected static function booted(): void
    {
        static::updating(function (self $l) {
            if (array_diff(array_keys($l->getDirty()), ['active', 'updated_at'])) {
                throw new LogicException('A payout statement line cannot be edited.');
            }
            if ($l->isDirty('active') && $l->active !== null) {
                throw new LogicException('A freed payout statement line cannot be taken again; a new line is added instead.');
            }
        });

        static::deleting(function (self $l) {
            if (PayoutStatement::where('id', $l->statement_id)->value('status') !== PayoutStatement::DRAFT) {
                throw new LogicException('Lines of a statement that is not a draft cannot be deleted.');
            }
        });
    }
}
