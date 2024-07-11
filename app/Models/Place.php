<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Place extends Model
{
    protected $with = ['primaryImage', 'icon'];
    protected $appends = ['primary_image_url', 'icon_url'];
    /*----------------------------------------
     * Relationships
     ----------------------------------------*/
    public function primaryImage(): MorphOne
    {
        return $this->morphOne(Media::class, 'media');
    }

    public function icon() : MorphOne
    {
        return $this->morphOne(Media::class, 'media');
    }

    public function images(): MorphMany
    {
        return $this->morphMany(Media::class, 'media');
    }
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


    public function rooms(): HasOneThrough
    {
        return $this->hasOneThrough(Room::class, Property::class);
    }
    /*----------------------------------------
     * Accessors
     ----------------------------------------*/
    public function getPrimaryImageUrlAttribute(): string
    {

        $imageUrl = asset('assets/default/default_place.jpg');

        if ($this->primaryImage()->exists()) {
            $imageUrl = $this->relations['primaryImage']->url;
        }

        return $imageUrl;
    }

    public function getIconUrlAttribute(): string
    {

        $iconUrl = asset('assets/default/default_place.jpg');

        if ($this->icon()->exists()) {
            $iconUrl = $this->relations['icon']->url;
        }

        return $iconUrl;
    }
}
