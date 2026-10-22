<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class City extends Model
{
    protected $with = ['photo'];
    protected $appends = ['photo_url'];

    /*----------------------------------------
     * Relationships
     ----------------------------------------*/
    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function photo(): MorphOne
    {
        return $this->morphOne(Media::class, 'media');
    }

    public function areas(): HasMany
    {
        return $this->hasMany(Area::class);
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }

    public function rooms(): HasOneThrough
    {
        return $this->hasOneThrough(Room::class, Property::class);
    }

    /*----------------------------------------
     * Accessors
     ----------------------------------------*/
    public function getPhotoUrlAttribute(): string
    {
        // The photo is always loaded with the city, so a long list of cities does not ask the database once per row
        return $this->photo?->url ?? asset('assets/default/default_icon.svg');
    }
}
