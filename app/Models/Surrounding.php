<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Surrounding extends Model
{
    public function surroundingPlace(): HasMany
    {
        return $this->hasMany(SurroundingPlace::class);
    }
}
