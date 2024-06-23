<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BedType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'capacity',
        'total_bed',
        'bed_size'
    ];
}
