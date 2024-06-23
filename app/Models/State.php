<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class State extends Model
{
    /*----------------------------------------
     * Relationships
     ----------------------------------------*/
    public function country() : BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
}
