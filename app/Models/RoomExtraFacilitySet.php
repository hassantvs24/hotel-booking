<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomExtraFacilitySet extends Model
{
    use HasFactory;

    protected $fillable = [
        'description',
        'room_extra_facilities_id',
        'rooms_id'
    ];
}
