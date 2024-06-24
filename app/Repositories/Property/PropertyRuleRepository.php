<?php

namespace App\Repositories\Property;

use App\Models\PropertyRule;
use App\Repositories\Repository;

class PropertyRuleRepository extends Repository
{
    public function __construct()
    {
        $this->model = new PropertyRule();
    }
}