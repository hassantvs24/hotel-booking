<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    /*---------------------------------
    Relationships
    ---------------------------------*/

    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function property() : BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function reviewCategory() : BelongsTo
    {
        return $this->belongsTo(ReviewCategory::class);
    }
}
