<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SurroundingPlace extends Model
{
    public function surrounding(): BelongsTo
    {
        return $this->belongsTo(Surrounding::class);
    }

    public function place(): BelongsTo
    {
        return $this->belongsTo(Place::class);
    }
}
