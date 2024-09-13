<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Property extends Model
{
    protected $with = ['primaryImage'];
    protected $appends = ['primary_image_url', 'lowest_room_price'];

    public const STATUS_PUBLISHED = 'Published';
    public const STATUS_UNPUBLISHED = 'Unpublished';
    public const STATUS_PENDING = 'Pending';

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

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    public function propertyCategory(): BelongsTo
    {
        return $this->belongsTo(PropertyCategory::class);
    }

    public function place(): BelongsTo
    {
        return $this->belongsTo(Place::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function facilities(): BelongsToMany
    {
        return $this->belongsToMany(FacilitySub::class, 'property_facilities')->withTimestamps();
    }

    public function rules(): HasMany
    {
        return $this->hasMany(PropertyRulesSetup::class);
    }
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function bookingAccepteds(): HasMany
    {
        return $this->hasMany(BookingAccepted::class);
    }

    /*----------------------------------------
     * Accessors
     ----------------------------------------*/
    public function getPrimaryImageUrlAttribute(): string
    {

        $imageUrl = asset('assets/default/default_property.jpg');

        if ($this->primaryImage()->exists()) {
            $imageUrl = $this->relations['primaryImage']->url;
        }

        return $imageUrl;
    }


    public function getLowestRoomPriceAttribute()
    {
        return $this->rooms()->min('base_price');
    }
}
