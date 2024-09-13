<?php

namespace App\Repositories\Admin;

use App\Models\Property;
use App\Repositories\Repository;

class PropertyRepository extends Repository
{
    public function __construct()
    {
        $this->model = new Property();
    }
}
