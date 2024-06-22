<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class City extends Model
{

    /*----------------------------------------
     * Relationships
     ----------------------------------------*/
    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }
}
