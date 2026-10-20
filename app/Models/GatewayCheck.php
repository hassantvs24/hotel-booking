<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** What SSLCommerz says about one payment (or refund) next to what we have. See the reconciliation migration. */
class GatewayCheck extends Model
{
    public const OK = 'ok';

    /** Verdicts that mean "a person should look" */
    public const PROBLEMS = ['amount_differs', 'status_differs', 'fee_differs', 'not_at_gateway', 'paid_unconfirmed', 'gateway_error'];

    protected $guarded = [];

    protected $casts = ['bookings' => 'array', 'checked_at' => 'datetime', 'subject_at' => 'datetime', 'resolved_at' => 'datetime'];

    /** The rows that still need a person: a problem nobody looked at (a few failed tries in a row are not a finding yet) */
    public function scopeNeedingLook($q)
    {
        return $q->where('result', '!=', self::OK)->whereNull('resolved_at')
            ->where(fn ($w) => $w->where('result', '!=', 'gateway_error')->orWhere('attempts', '>=', 6));
    }

    /** A verdict that still needs a person (not fine, and not yet looked at) */
    public function needsLook(): bool
    {
        if ($this->result === 'gateway_error') {
            return $this->attempts >= 6 && $this->resolved_at === null;   // a few failed tries in a row are not a finding yet
        }

        return $this->result !== self::OK && $this->resolved_at === null;
    }
}
