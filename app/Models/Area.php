<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** The level under a district: an upazila in Bangladesh */
class Area extends Model
{
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
