<?php

namespace App\Models;

use Illuminate\Support\Str;

class PropertyRequest extends Model
{
    /*----------------------------------------
    | Casts
    |----------------------------------------*/

    protected $casts = [
        'draft_data'  => 'array',
        'is_draft'    => 'boolean',
        'draft_step'  => 'integer',
        'latitude'    => 'float',
        'longitude'   => 'float',
        'approved_at' => 'datetime',
    ];

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
    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function property(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    // ── Helpers ───────────────────────────────────────────

    public function isDraft(): bool
    {
        return (bool) $this->is_draft;
    }

    public function isSubmitted(): bool
    {
        return !$this->is_draft;
    }
}
