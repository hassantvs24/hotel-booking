<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Property extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'lat',
        'long',
        'photo',
        'address',
        'zip_code',
        'total_room',
        'currency',
        'property_class',
        'rating',
        'google_review',
        'seo_title',
        'seo_meta',
        'status',
        'property_categories_id',
        'places_id',
        'users_id'
    ];
}
