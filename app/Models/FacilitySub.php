<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacilitySub extends Model
{
    /*----------------------------------------
     * Relationships
     ----------------------------------------*/

    public function facility() : BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }
}
