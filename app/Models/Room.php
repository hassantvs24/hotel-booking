<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;


class Room extends Model
{
    /*----------------------------------------
     * Relationships
     ----------------------------------------*/
    public function primaryImage(): MorphOne
    {
        return $this->morphOne(Media::class, 'media');
    }

    public function images(): MorphMany
    {
        return $this->morphMany(Media::class, 'media');
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function roomtype(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    public function bedtype(): BelongsTo
    {
        return $this->belongsTo(BedType::class);
    }
}
