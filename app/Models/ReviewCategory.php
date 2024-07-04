<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class ReviewCategory extends Model
{
    protected $with = ['icon'];
    protected $appends = ['icon_url'];


    /*---------------------------------
    Relationships
    ---------------------------------*/
    public function reviews() : HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function icon() : MorphOne
    {
        return $this->morphOne(Media::class, 'media');
    }

    /*----------------------------------------
     * Accessors
     ----------------------------------------*/
    public function getIconUrlAttribute() : string
    {
        $iconUrl = asset('assets/default/default_review.svg');

        if ($this->icon()->exists()) {
            $iconUrl = $this->relations['icon']->url;
        }

        return $iconUrl;
    }
}
