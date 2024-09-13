<?php

namespace App\Repositories\Property;

use App\Models\PropertyCategory;
use App\Repositories\Repository;

class PropertyCategoryRepository extends Repository
{
    public function __construct()
    {
        $this->model = new PropertyCategory();
        $this->searchable = [];
    }
}
