<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Booking extends Model
{
    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function room() : BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
}
