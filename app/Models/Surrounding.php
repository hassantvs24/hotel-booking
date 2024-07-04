<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Surrounding extends Model
{
    protected $with = ['icon'];
    protected $appends = ['icon_url'];

    /*----------------------------------------
     * Relationships
     ----------------------------------------*/

    public function places(): HasMany
    {
        return $this->hasMany(SurroundingPlace::class);
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
        $iconUrl = asset('assets/default/default_surrounding.png');

        if ($this->icon()->exists()) {
            $iconUrl = $this->relations['icon']->url;
        }

        return $iconUrl;
    }
}
