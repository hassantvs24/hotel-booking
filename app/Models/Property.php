<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Property extends Model
{
    /*----------------------------------------
     * Relationships
     ----------------------------------------*/
    public function primaryImage() : MorphOne
    {
        return $this->morphOne(Media::class, 'media');
    }

    public function images() : MorphMany
    {
        return $this->morphMany(Media::class, 'media');
    }
}
