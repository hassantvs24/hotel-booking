<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SurroundingPlace extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'lat',
        'long',
        'notes',
        'photo',
        'places_id',
        'surroundings_id'
    ];
}
