<?php

namespace App\Models;

use Illuminate\Support\Str;

class PropertyRequest extends Model
{
    /*----------------------------------------
    | Table Properties |
    |----------------------------------------*/

    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($request) {
            $request->unique_request_number = 'req-' . Str::lower(Str::random(10));
        });
    }


    /*----------------------------------------
    | Relationship Methods |
    |----------------------------------------*/

    public function property(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}
