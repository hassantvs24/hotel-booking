<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomMedia extends Model
{
    use HasFactory;

    protected $fillable = [
        'media_file',
        'media_type',
        'notes'
    ];
}
