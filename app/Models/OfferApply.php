<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfferApply extends Model
{
    /*---------------------------------
    Relationships
    ---------------------------------*/
    public function offer() : BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
