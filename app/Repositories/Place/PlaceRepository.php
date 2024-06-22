<?php

namespace App\Repositories\Place;

use App\Models\Place;
use App\Repositories\Repository;

class PlaceRepository extends Repository
{
    public function __construct()
    {
        $this->model = new Place();
    }
}
