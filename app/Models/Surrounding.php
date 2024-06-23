<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Surrounding extends Model
{
    /*----------------------------------------
     * Relationships
     ----------------------------------------*/

    public function places() : HasMany
    {
        return $this->hasMany(SurroundingPlace::class);
    }
}
