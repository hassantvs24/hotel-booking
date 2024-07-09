<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Room extends Model
{
    protected $with = ['primaryImage'];
    protected $appends = ['image_url'];

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

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    public function bedType(): BelongsTo
    {
        return $this->belongsTo(BedType::class);
    }

    public function extraFacilities(): BelongsToMany
    {
        return $this->belongsToMany(RoomExtraFacility::class);
    }

    /*----------------------------------------
     * Accessors
     ----------------------------------------*/
    public function getImageUrlAttribute(): string
    {

        $imageUrl = asset('assets/default/default_property.png');
        if ($this->primaryImage()->exists()) {
            $imageUrl = $this->relations['primaryImage']->url;
        }

        return $imageUrl;
    }
}
