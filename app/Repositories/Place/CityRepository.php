<?php

namespace App\Repositories\Place;

use App\Models\City;
use App\Repositories\Repository;

class CityRepository extends Repository
{
    public function __construct()
    {
        $this->model = new City();
    }
}
