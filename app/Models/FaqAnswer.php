<?php

namespace App\Models;

class FaqAnswer extends Model
{
    protected $table = 'faq_answers';

    /*
     * Relationships
     */

    public function faq() : \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Faq::class);
    }
}
