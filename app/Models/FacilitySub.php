<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class FacilitySub extends Model
{
    protected $with = ['icon'];
    protected $appends = ['icon_url'];

    /*----------------------------------------
     * Relationships
     ----------------------------------------*/

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function properties() : BelongsToMany
    {
        return $this->belongsToMany(Property::class);
    }

    public function icon(): MorphOne
    {
        return $this->morphOne(Media::class, 'media');
    }

    /*----------------------------------------
     * Accessors
     ----------------------------------------*/
    public function getIconUrlAttribute(): string
    {
        $iconUrl = asset('assets/default/default_subfacilites.png');

        if ($this->icon()->exists()) {
            $iconUrl = $this->relations['icon']->url;
        }

        return $iconUrl;
    }
}
