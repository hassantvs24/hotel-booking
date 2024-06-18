<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Place extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'lat',
        'long',
        'zip_code',
        'description',
        'nearest_police',
        'nearest_police',
        'nearest_hospital',
        'nearest_fire',
        'photo',
        'cities_id'
    ];
}
