<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Facility extends Model
{
    protected $with = ['icon'];
    protected $appends = ['icon_url'];

    /*----------------------------------------
     * Relationships
     ----------------------------------------*/

    public function subFacilities(): HasMany
    {
        return $this->hasMany(FacilitySub::class, 'facility_id', 'id');
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
        $iconUrl = asset('assets/default/default_facilites.png');

        if ($this->icon()->exists()) {
            $iconUrl = $this->relations['icon']->url;
        }

        return $iconUrl;
    }
}
