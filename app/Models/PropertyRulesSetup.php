<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyRulesSetup extends Model
{
    use HasFactory;

    protected $fillable = [
        'rule_description',
        'is_active',
        'property_rules_id',
        'properties_id'
    ];
}
