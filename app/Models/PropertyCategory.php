<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class PropertyCategory extends Model
{
    /*----------------------------------------
     * Relationships
     ----------------------------------------*/
    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }
}
