<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Offer extends Model
{
    /*---------------------------------
    Relationships
    ---------------------------------*/

    public function room() : BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function applications() : HasMany
    {
        return $this->hasMany(OfferApply::class);
    }
}
