<?php

namespace App\Repositories\Surrounding;

use App\Models\SurroundingPlace;
use App\Repositories\Repository;

class SurroundingPlaceRepository extends Repository
{
    public function __construct()
    {
        $this->model = new SurroundingPlace();
    }
}