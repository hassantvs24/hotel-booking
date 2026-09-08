<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    public function booking() :BelongsTo
    {
        return $this->belongsTo(Booking::class,'booking_id');
    }

    public function user () :BelongsTo
    {
        return $this->belongsTo(User::class,'user_id');
    }
}
