<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Where a vendor wants to be paid: a bank account or a mobile wallet */
class VendorPayoutAccount extends Model
{
    public const METHODS = ['bank', 'bkash', 'nagad', 'rocket'];

    protected $guarded = [];

    /** The details as one array, in the shape saved on a paid statement and shown on screen */
    public function details(): array
    {
        return [
            'method'         => $this->method,
            'holder_name'    => $this->holder_name,
            'account_number' => $this->account_number,
            'bank_name'      => $this->bank_name,
            'branch_name'    => $this->branch_name,
            'routing_number' => $this->routing_number,
            'updated_at'     => $this->updated_at?->toIso8601String(),
        ];
    }
}
