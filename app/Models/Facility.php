<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Facility extends Model
{
    /*----------------------------------------
     * Relationships
     ----------------------------------------*/

    public function subFacilities() : HasMany
    {
        return $this->hasMany(FacilitySub::class);
    }
}
