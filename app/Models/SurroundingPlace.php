<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class SurroundingPlace extends Model
{
    protected $with = ['icon'];
    protected $appends = ['icon_url'];

    /*----------------------------------------
     * Relationships
     ----------------------------------------*/

    public function surrounding(): BelongsTo
    {
        return $this->belongsTo(Surrounding::class);
    }

    public function place(): BelongsTo
    {
        return $this->belongsTo(Place::class);
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
