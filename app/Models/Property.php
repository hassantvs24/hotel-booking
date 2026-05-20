<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Property extends Model
{
    protected $with = ['primaryImage'];
    protected $appends = ['primary_image_url', 'lowest_room_price'];

    protected $hidden = ['bank_details'];

    public const STATUS_PUBLISHED = 'Published';
    public const STATUS_UNPUBLISHED = 'Unpublished';
    public const STATUS_PENDING = 'Pending';

    /*----------------------------------------
     * Relationships
     ----------------------------------------*/
    public function request(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(PropertyRequest::class);
    }

    public function primaryImage(): MorphOne
    {
        return $this->morphOne(Media::class, 'media')->where('media_role', 'property_image');
    }

    public function images(): MorphMany
    {
        return $this->morphMany(Media::class, 'media')->where('media_role', 'property_gallery_image');
    }
    public function logoImage(): MorphOne
    {
        return $this->morphOne(Media::class, 'media')->where('media_role', 'property_logo_image');
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

    public function staffs(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'property_user')
            ->withPivot(['designation', 'is_active'])
            ->withTimestamps();
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
        return $this->hasMany(PropertyRulesSetup::class, 'property_id', 'id');
    }
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function faqs() : HasMany
    {
        return $this->hasMany(Faq::class);
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


    public function getLowestRoomPriceAttribute(): float|int
    {
        $min = $this->rooms()->min('base_price');
        return $min ? round($min / 100, 2) : 0;
    }

    /*----------------------------------------
     * Attributes
     ----------------------------------------*/
    public function address(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                $first = @unserialize($value);  // first level unserialize

                $address = is_string($first) ? @unserialize($first) : $first; // Second level unserialize if the first is a string

                if (!$address || !is_array($address)) {
                    return null;
                }

                return collect([
                    $address['address'] ?? null,
                    $address['apartment'] ?? null,
                    $address['city'] ?? null,
                    $address['country'] ?? null,
                ])->filter()->implode(', ');
            },

            set: fn ($value) => serialize($value)
        );
    }
     public function bankDetails() : Attribute
     {
         return new Attribute(
             fn($value) => unserialize($value),
             fn($value) => serialize($value)
         );
     }
}
