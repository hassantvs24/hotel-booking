<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Place extends Model
{
    /*----------------------------------------
     * Relationships
     ----------------------------------------*/
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function surroundingPlace(): HasMany
    {
        return $this->hasMany(SurroundingPlace::class);
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }
}
