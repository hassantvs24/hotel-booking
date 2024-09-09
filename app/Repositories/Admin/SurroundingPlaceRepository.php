<?php

namespace App\Repositories\Admin;

use App\Models\SurroundingPlace;
use App\Repositories\Repository;

class SurroundingPlaceRepository extends Repository
{
    public function __construct()
    {
        $this->model = new SurroundingPlace();
        $this->searchable = [];
    }
}
