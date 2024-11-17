<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyRulesSetup extends Model
{
    public function propertyRule(): BelongsTo
    {
        return $this->belongsTo(PropertyRule::class);
    }
}
