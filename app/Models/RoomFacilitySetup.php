<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomFacilitySetup extends Model
{
    use HasFactory;

    protected $fillable = [
        'facility_subs_id',
        'rooms_id'
    ];
}
