<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfile extends Model
{
    /*----------------------------------------
    Relations
    ----------------------------------------*/

    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
