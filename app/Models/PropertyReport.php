<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A guest's report about a property (wrong photos, misleading information, something else). */
class PropertyReport extends Model
{
    public const REASONS = ['wrong_photos' => 'Photos do not match the property', 'misleading_info' => 'Misleading information', 'other' => 'Something else'];

    protected $guarded = [];

    protected $casts = ['handled_at' => 'datetime'];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }
}
