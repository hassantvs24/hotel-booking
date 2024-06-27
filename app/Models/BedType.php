<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class BedType extends Model
{

    /*----------------------------------------
     * Relationships
     ----------------------------------------*/
    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }
}
