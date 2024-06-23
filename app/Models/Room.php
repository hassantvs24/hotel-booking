<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'photo',
        'room_number',
        'room_size',
        'guest_capacity',
        'extra_bed',
        'total_balcony',
        'total_window',
        'base_price',
        'notes',
        'bed_types_id',
        'room_types_id',
        'properties_id'
    ];
}
