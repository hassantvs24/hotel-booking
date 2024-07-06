<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class City extends Model
{
    protected $with = ['photo'];
    protected $appends = ['photo_url'];

    /*----------------------------------------
     * Relationships
     ----------------------------------------*/
    public function state() : BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function photo() : MorphOne
    {
        return $this->morphOne(Media::class, 'media');
    }

    /*----------------------------------------
     * Accessors
     ----------------------------------------*/
    public function getPhotoUrlAttribute() : string
    {
        $iconUrl = asset('assets/default/default_icon.svg');

        if ($this->photo()->exists()) {
            $iconUrl = $this->relations['photo']->url;
        }

        return $iconUrl;
    }
}
