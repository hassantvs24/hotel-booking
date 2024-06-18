<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FacilitySub extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'icon',
        'facilities_id'
    ];
}
