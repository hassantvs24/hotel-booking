<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RoomRequestAccepted extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function room_request():BelongsTo
    {
        return $this->belongsTo(RoomRequest::class,'room_requests_id');
    }


}
