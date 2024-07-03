<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class ReviewCategory extends Model
{
    /*---------------------------------
    Relationships
    ---------------------------------*/
    public function reviews() : HasMany
    {
        return $this->hasMany(Review::class);
    }
}
