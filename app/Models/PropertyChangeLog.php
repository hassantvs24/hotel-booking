<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One change somebody made to a property: who, when, what it was before and what it is now. */
class PropertyChangeLog extends Model
{
    protected $guarded = [];

    protected $casts = ['meta' => 'array', 'reverted_at' => 'datetime'];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
