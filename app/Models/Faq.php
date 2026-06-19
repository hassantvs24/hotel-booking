<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Faq extends Model
{
    protected $table = 'faqs';

    /*
     * Relationships
     */

    public function property() : BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

     public function answer() : \Illuminate\Database\Eloquent\Relations\HasOne
     {
         return $this->hasOne(FaqAnswer::class);
     }
}
