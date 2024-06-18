<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'short_name',
        'country_code',
        'currency',
        'currency_code',
        'language',
        'flag'
    ];
}
